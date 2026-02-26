<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Event;
use App\Models\TokenTransfer;
use Carbon\Carbon;

class AdminController extends Controller
{
    // 1️⃣ Create user (existing)
    public function createUser(Request $request)
    {
        $request->validate([
            'user_type' => 'required|in:demo,live',

            'name' => 'required',
            'username' => 'required|unique:users',
            'phone' => 'required',
            'password' => 'required|min:4',
            'level' => 'required',
        ]);

        $admin = Auth::user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tokenField = $request->user_type === 'demo' ? 'demo_tokens' : 'live_tokens';
        if ($admin->$tokenField <= 0) {
            return response()->json(['message' => 'No tokens left'], 400);
        }


        if (!$request->expiry_date) {
            if ($request->user_type === 'demo') {
                $expiry_date = Carbon::now()->addDays(1); // Demo users expire in 7 days
            } else {
                $expiry_date = Carbon::now()->addDays(30); // Live users expire in 30 days
            }
        } else {
            $expiry_date = Carbon::parse($request->expiry_date);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'phone' => $request->phone,
            'password' => $request->password,
            'role' => 'user',
            'user_type' => $request->user_type,
            'admin_id' => $admin->id,
            'is_active' => 1,
            'level' => $request->level,
            'period' => $request->period,
            'expiry_date' => $expiry_date
        ]);

        TokenTransfer::create([
            'from_user_id' => $admin->id,
            'to_user_id'   => $user->id,
            'token_type'   => $request->user_type === 'demo' ? 'demo' : 'live',
            'quantity'     => 1,
            'action'       => 'admin_create_user',
            'description'  => 'Admin created user'
        ]);


        $admin->decrement($tokenField, 1);

        return response()->json($user);
    }

    // 2️⃣ View all users under this admin
    public function listUsers()
    {
        $admin = Auth::user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::where('role', 'user')->where('admin_id', $admin->id)->get();
        return response()->json($users);
    }

    public function expiredUsers()
    {
        $admin = Auth::user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::where('role', 'user')
            ->where('admin_id', $admin->id)
            ->where('is_active', 0)
            ->get();

        return response()->json($users);
    }

    // 3️⃣ Edit a user
    public function editUser(Request $request, $user_id)
    {
        $admin = Auth::user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::where('id', $user_id)
            ->where('role', 'user')
            ->where('admin_id', $admin->id)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|required',
            'username' => 'sometimes|required|unique:users,username,' . $user->id,
            'phone' => 'sometimes|required',
            'password' => 'sometimes|min:6',
            'level' => 'sometimes|required',
            'expiry_date' => 'sometimes|date|after:today'
        ]);

        $data = $request->only(['name', 'username', 'phone', 'level', 'expiry_date']);
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return response()->json($user);
    }

    // 4️⃣ Delete a user
    public function deleteUser($user_id)
    {
        $admin = Auth::user();
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::where('id', $user_id)
            ->where('role', 'user')
            ->where('admin_id', $admin->id)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Restore token to admin when user is deleted
        $tokenField = $user->user_type === 'demo' ? 'demo_tokens' : 'live_tokens';
        $admin->increment($tokenField);

        TokenTransfer::create([
            'from_user_id' => $user->id,
            'to_user_id'   => $admin->id,
            'token_type'   => $user->user_type === 'demo' ? 'demo' : 'live',
            'quantity'     => 1,
            'action'       => 'admin_delete_user',
            'description'  => 'Admin deleted user, token restored'
        ]);


        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }


    // 5️⃣ Admin Dashboard Stats
    public function dashboardStats()
    {
        $admin = Auth::user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($admin->is_active == 0) {
            return response()->json(['message' => 'Admin is Blocked'], 403);
        }

        // Total tokens remaining
        $totalDemoTokens = $admin->demo_tokens;
        $totalLiveTokens = $admin->live_tokens;

        // Used tokens (based on created users)
        $demoUsed = User::where('role', 'user')
            ->where('admin_id', $admin->id)
            ->where('user_type', 'demo')
            ->count();

        $liveUsed = User::where('role', 'user')
            ->where('admin_id', $admin->id)
            ->where('user_type', 'live')
            ->count();

        // Total users created by this admin
        $totalUsers = User::where('role', 'user')
            ->where('admin_id', $admin->id)
            ->count();
        $expiredUsers = User::where('role', 'user')
            ->where('admin_id', $admin->id)
            ->where('is_active', 0)
            ->count();
        $totalevents = Event::count();

        return response()->json([
            'admin_id'          => $admin->id,

            // remaining tokens
            'demo_tokens_left'  => $totalDemoTokens,
            'live_tokens_left'  => $totalLiveTokens,

            // used tokens
            'demo_tokens_used'  => $demoUsed,
            'live_tokens_used'  => $liveUsed,

            // totals
            'total_users'       => $totalUsers,
            'expired_users'    =>  $expiredUsers,

            //Events
            'total_events'     => $totalevents,
            'active_events'    => Event::where('estatus', true)->count(),


        ]);
    }


    public function updateUserStatus(Request $request)
    {
        $admin = Auth::user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status'   => 'required|boolean'
        ]);

        $user = User::where('id', $request->user_id)
            ->where('role', 'user')
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->is_active = $request->status;
        $user->save();

        return response()->json([
            'message'   => $request->status ? 'User unblocked successfully' : 'User blocked successfully',
            'user_id'  => $admin->id,
            'is_active' => (bool) $admin->is_active
        ]);
    }

    public function getUserById(Request $request)
    {
        $admin = Auth::user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::where('id', $request->user_id)
            ->where('role', 'user')
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function updateUserLevel(Request $request)
    {
        $admin = Auth::user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'level'   => 'required|integer|min:1'
        ]);

        $user = User::where('id', $request->user_id)
            ->where('role', 'user')
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->level = $request->level;
        $user->save();

        return response()->json([
            'message'   => 'User level updated successfully',
            'user_id'  => $user->id,
            'level'     => $user->level
        ]);
    }

    public function eventList(Request $request)
    {
        $admin = Auth::user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $events = Event::all();
        return response()->json($events);
    }
}
