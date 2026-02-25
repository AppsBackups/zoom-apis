<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);


        $user = User::where('username', $request->username)->first();


        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is inactive'], 403);
        }
        if ($user->expiry_date && now()->greaterThan($user->expiry_date)) {
            return response()->json(['message' => 'Account has expired'], 403);
        }


        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $user
        ]);
    }
}
