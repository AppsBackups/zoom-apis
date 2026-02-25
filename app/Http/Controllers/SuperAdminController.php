<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Event;
use App\Models\TokenTransfer;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    // Create admin with tokens
    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users',
            'phone' => 'required',
            'password' => 'required|min:6',
            'demo_tokens' => 'nullable|integer|min:0',
            'live_tokens' => 'nullable|integer|min:0'
        ]);


        $superadmin = Auth::user();

        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $demo = $request->demo_tokens ?? 0;
        $live = $request->live_tokens ?? 0;

        if (
            $superadmin->demo_tokens < $demo ||
            $superadmin->live_tokens < $live
        ) {
            return response()->json(['message' => 'Insufficient tokens'], 400);
        }

        $admin = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'role' => 'admin',
            'demo_tokens' => $request->demo_tokens ?? 0,
            'live_tokens' => $request->live_tokens ?? 0,

        ]);

        // Token transfers
        TokenTransfer::create([
            'from_user_id' => $superadmin->id,
            'to_user_id'   => $admin->id,
            'token_type'   => 'demo',
            'quantity'     => $request->demo_tokens,
            'action'       => 'assign_to_admin',
            'description'  => 'Superadmin assigned demo tokens to admin'
        ]);

        TokenTransfer::create([
            'from_user_id' => $superadmin->id,
            'to_user_id'   => $admin->id,
            'token_type'   => 'live',
            'quantity'     => $request->live_tokens,
            'action'       => 'assign_to_admin',
            'description'  => 'Superadmin assigned live tokens to admin'
        ]);

        // Deduct from superadmin
        $superadmin->decrement('demo_tokens', $request->demo_tokens);
        $superadmin->decrement('live_tokens', $request->live_tokens);

        return response()->json($admin);
    }

    // Superadmin creates user under admin
    public function createUser(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:users,id',
            'user_type' => 'required|in:demo,live',
            'name' => 'required',
            'username' => 'required|unique:users',
            'phone' => 'required',
            'password' => 'required|min:6',
            'level' => 'required',
            'expiry_date' => 'date|after:today',
        ]);

        $superadmin = Auth::user();

        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $tokenType  = $request->user_type === 'demo' ? 'demo' : 'live';
        $tokenField = $tokenType . '_tokens';

        if (!$request->expiry_date) {
            if ($request->user_type === 'demo') {
                $expiry_date = Carbon::now()->addDays(1); // Demo users expire in 7 days
            } else {
                $expiry_date = Carbon::now()->addDays(30); // Live users expire in 30 days
            }
        } else {
            $expiry_date = Carbon::parse($request->expiry_date);
        }

        $admin = User::findOrFail($request->admin_id);

        if ($admin->$tokenField <= 0) {
            return response()->json(['message' => 'Admin has no tokens'], 400);
        }


        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'role' => 'user',
            'user_type' => $request->user_type,
            'admin_id' => $request->admin_id,
            'is_active' => 1,
            'level' => $request->level,
            'period' => $request->period,
            'expiry_date' => $expiry_date,
        ]);

        TokenTransfer::create([
            'from_user_id' => $superadmin->id,
            'to_user_id' => $user->id,
            'token_type' => $tokenType,
            'quantity' => 1,
            'action' => 'superadmin_create_user',
            'description' => 'Superadmin created user under admin'
        ]);

        $admin->decrement($tokenField);

        return response()->json($user);
    }

    // Assign extra tokens to admin separately
    public function assignTokensToAdmin(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:users,id',
            'demo_tokens' => 'integer|min:0',
            'live_tokens' => 'integer|min:0'
        ]);

        $superadmin = Auth::user();

        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $admin = User::find($request->admin_id);

        // Assign demo tokens
        if ($request->demo_tokens > 0) {
            if ($superadmin->demo_tokens < $request->demo_tokens) {
                return response()->json(['message' => 'Insufficient demo tokens'], 400);
            }
            $admin->increment('demo_tokens', $request->demo_tokens);
            $superadmin->decrement('demo_tokens', $request->demo_tokens);

            TokenTransfer::create([
                'from_user_id' => $superadmin->id,
                'to_user_id' => $admin->id,
                'token_type' => 'demo',
                'quantity' => $request->demo_tokens,
                'action' => 'assign_to_admin',
                'description' => 'Superadmin added demo tokens to admin'
            ]);
        }

        // Assign live tokens
        if ($request->live_tokens > 0) {
            if ($superadmin->live_tokens < $request->live_tokens) {
                return response()->json(['message' => 'Insufficient live tokens'], 400);
            }
            $admin->increment('live_tokens', $request->live_tokens);

            $superadmin->decrement('live_tokens', $request->live_tokens);

            TokenTransfer::create([
                'from_user_id' => $superadmin->id,
                'to_user_id' => $admin->id,
                'token_type' => 'live',
                'quantity' => $request->live_tokens,
                'action' => 'assign_to_admin',
                'description' => 'Superadmin added live tokens to admin'
            ]);
        }

        return response()->json($admin);
    }

    public function getAdminById(Request $request)
    {
        $superadmin = Auth::user();

        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'admin_id' => 'required|exists:users,id'
        ]);

        $admin = User::where('id', $request->admin_id)
            ->where('role', 'admin')
            ->first();

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $demoUsed = User::where('admin_id', $admin->id)
            ->where('user_type', 'demo')
            ->count();

        $liveUsed = User::where('admin_id', $admin->id)
            ->where('user_type', 'live')
            ->count();

        $totalUsers = User::where('admin_id', $admin->id)->count();

        return response()->json([
            'admin' => $admin,
            'stats' => [
                'demo_tokens_left' => $admin->demo_tokens,
                'live_tokens_left' => $admin->live_tokens,
                'demo_tokens_used' => $demoUsed,
                'live_tokens_used' => $liveUsed,
                'total_users'      => $totalUsers
            ]
        ]);
    }



    // Remove tokens from admin
    public function removeTokensFromAdmin(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:users,id',
            'demo_tokens' => 'integer|min:0',
            'live_tokens' => 'integer|min:0'
        ]);

        $superadmin = Auth::user();
        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $admin = User::find($request->admin_id);

        // Remove demo tokens
        if ($request->demo_tokens > 0) {
            if ($admin->demo_tokens < $request->demo_tokens) {
                return response()->json(['message' => 'Admin does not have enough demo tokens'], 400);
            }
            $admin->decrement('demo_tokens', $request->demo_tokens);

            TokenTransfer::create([
                'from_user_id' => $admin->id,
                'to_user_id' => $superadmin->id,
                'token_type' => 'demo',
                'quantity' => $request->demo_tokens,
                'action' => 'remove_from_admin',
                'description' => 'Superadmin removed demo tokens from admin'
            ]);
        }

        // Remove live tokens
        if ($request->live_tokens > 0) {
            if ($admin->live_tokens < $request->live_tokens) {
                return response()->json(['message' => 'Admin does not have enough live tokens'], 400);
            }
            $admin->decrement('live_tokens', $request->live_tokens);

            TokenTransfer::create([
                'from_user_id' => $admin->id,
                'to_user_id' => $superadmin->id,
                'token_type' => 'live',
                'quantity' => $request->live_tokens,
                'action' => 'remove_from_admin',
                'description' => 'Superadmin removed live tokens from admin'
            ]);
        }

        return response()->json($admin);
    }



    public function listAdmins()
    {
        $superadmin = Auth::user();

        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $admins = User::where('role', 'admin')->get();

        $result = $admins->map(function ($admin) {
            $totalUsers = User::where('admin_id', $admin->id)
                ->where('role', 'user')
                ->count();

            $totalEvents = 0; // Placeholder

            return [
                'id'             => $admin->id,
                'name'           => $admin->name,
                'username'       => $admin->username,
                'phone'          => $admin->phone,
                'demo_tokens'    => $admin->demo_tokens,
                'live_tokens'    => $admin->live_tokens,
                'is_active'      => $admin->is_active,
                'total_users'    => $totalUsers,
                'total_events'   => $totalEvents,
                'created_at'     => $admin->created_at,
            ];
        });

        return response()->json($result);
    }


    // 2️⃣ View all users under a specific admin
    public function listUsersUnderAdmin($admin_id)
    {
        $superadmin = Auth::user();
        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $admin = User::where('id', $admin_id)->where('role', 'admin')->first();
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $users = User::where('role', 'user')->where('admin_id', $admin->id)->get();
        return response()->json($users);
    }

    // 3️⃣ Edit an admin
    public function editAdmin(Request $request, $admin_id)
    {
        $superadmin = Auth::user();
        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $admin = User::where('id', $admin_id)->where('role', 'admin')->first();
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required',
            'username' => 'sometimes|required|unique:users,username,' . $admin->id,
            'phone' => 'sometimes|required',
            'password' => 'sometimes|min:6'
        ]);
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $admin->update($request->only(['name', 'username', 'phone'] + (isset($data) ? ['password' => $data['password']] : [])));


        return response()->json($admin);
    }

    // 4️⃣ Edit a user under an admin
    public function editUser(Request $request, $user_id)
    {
        $superadmin = Auth::user();
        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::where('id', $user_id)->where('role', 'user')->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required',
            'username' => 'sometimes|required|unique:users,username,' . $user->id,
            'phone' => 'sometimes|required',
            'password' => 'sometimes|min:6',
            'level' => 'sometimes|required',
            'expiry_date' => 'sometimes|date|after:today',
        ]);

        $data = $request->only(['name', 'username', 'phone', 'level', 'expiry_date']);
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    // 5️⃣ Delete an admin (and optionally all users under them)
    public function deleteAdmin($admin_id)
    {
        $superadmin = Auth::user();
        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $admin = User::where('id', $admin_id)->where('role', 'admin')->first();
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        // Delete all users under this admin
        User::where('role', 'user')->where('admin_id', $admin->id)->delete();

        // Optionally, record token transfers if you want
        // ...

        $admin->delete();

        return response()->json(['message' => 'Admin and their users deleted successfully']);
    }

    // 6️⃣ Delete a user under an admin
    public function deleteUser($user_id)
    {
        $superadmin = Auth::user();
        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::where('id', $user_id)->where('role', 'user')->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }


        $admin = User::find($user->admin_id);
        $tokenField = $user->user_type === 'demo' ? 'demo_tokens' : 'live_tokens';
        $admin->increment($tokenField);

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function updateAdminStatus(Request $request)
    {
        $superadmin = Auth::user();

        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'admin_id' => 'required|exists:users,id',
            'status'   => 'required|boolean'
        ]);

        $admin = User::where('id', $request->admin_id)
            ->where('role', 'admin')
            ->first();

        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $admin->is_active = $request->status;
        $admin->save();

        return response()->json([
            'message'   => $request->status ? 'Admin unblocked successfully' : 'Admin blocked successfully',
            'admin_id'  => $admin->id,
            'is_active' => (bool) $admin->is_active
        ]);
    }


    public function allusers()
    {
        $superadmin = Auth::user();

        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::where('role', 'user')
            ->with('admin') // eager load admin relationship
            ->get()
            ->map(function ($user) {
                return [
                    'id'             => $user->id,
                    'name'           => $user->name,
                    'username'       => $user->username,
                    'phone'          => $user->phone,
                    'user_type'      => $user->user_type,
                    'is_active'      => $user->is_active,
                    'level'          => $user->level,
                    'period'        => $user->period,
                    'demo_tokens'   => $user->demo_tokens,
                    'live_tokens'   => $user->live_tokens,
                    'userExpiryDate' => $user->expiry_date,
                    'isExpired'      => $user->expiry_date ? Carbon::parse($user->expiry_date)->isPast() : false,
                    'adminName'      => $user->admin ? $user->admin->name : null,
                    'getWatching'    => $user->getWatching ?? null, // replace with real column if exists
                ];
            });

        return response()->json($users);
    }





    public function myusers()
    {
        $superadmin = Auth::user();

        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::where('role', 'user')->where('admin_id', $superadmin->id)->get();
        return response()->json($users);
    }



    public function data()
    {
        $superadmin = Auth::user();

        if ($superadmin->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 🔢 TOKENS
        $tokenHave = $superadmin->demo_tokens + $superadmin->live_tokens;

        // Total tokens sent to admins
        $tokenDistributed = TokenTransfer::where('from_user_id', $superadmin->id)
            ->where('action', 'assign_to_admin')
            ->sum('quantity');

        // Tokens sold = number of users superadmin created
        $tokensSold = TokenTransfer::where('from_user_id', $superadmin->id)
            ->where('action', 'superadmin_create_user')
            ->count();

        // Exotic tokens (placeholder)
        $exoticToken = 0;

        // 👥 USERS
        $myAdmin = User::where('role', 'admin')->count();
        $allUser = User::where('role', 'user')->count();

        // ✅ Users directly created by superadmin
        $myUser = $tokensSold;

        // 📊 EVENTS
        $allEvent  = Event::count();

        return response()->json([
            "tokenhave" => (string) $tokenHave,
            "tokendistributed" => (string) $tokenDistributed,
            "tokensold" => (string) $tokensSold,
            "exotictoken" => (string) $exoticToken,

            "myadmin" => (string) $myAdmin,
            "alluser" => (string) $allUser,
            "myuser" => (string) $myUser,

            "allevent" => (string) $allEvent,
        ]);
    }
}
