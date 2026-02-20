<?php

namespace App\Http\Controllers;

use App\Models\TokenTransfer;
use Illuminate\Http\Request;

class TokenHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | 🔥 SUPERADMIN
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'superadmin') {

            $history = TokenTransfer::with(['fromUser', 'toUser'])
                ->orderByDesc('id')
                ->get();

            $currentTokens = $user->demo_tokens + $user->live_tokens;

            $tokenAssigned = TokenTransfer::where('from_user_id', $user->id)
                ->sum('quantity');

            $totalToken = $currentTokens + $tokenAssigned;

            return response()->json([
                'endDate'        => $user->expiry_date,
                'totalToken'     => $totalToken,
                'tokenAssigned'  => $tokenAssigned,
                'tokenLeft'      => $currentTokens,
                'history'        => $history
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🔥 ADMIN
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'admin') {

            $history = TokenTransfer::with(['fromUser', 'toUser'])
                ->where(function ($q) use ($user) {
                    $q->where('from_user_id', $user->id)
                        ->orWhere('to_user_id', $user->id)
                        ->orWhereIn(
                            'to_user_id',
                            function ($sub) use ($user) {
                                $sub->select('id')
                                    ->from('users')
                                    ->where('admin_id', $user->id);
                            }
                        );
                })
                ->orderByDesc('id')
                ->get();

            $currentTokens = $user->demo_tokens + $user->live_tokens;

            $tokenSold = TokenTransfer::where('from_user_id', $user->id)
                ->sum('quantity');

            $totalToken = $currentTokens + $tokenSold;

            return response()->json([
                'endDate'     => $user->expiry_date,
                'totalToken'  => $totalToken,
                'tokenSold'   => $tokenSold,
                'tokenLeft'   => $currentTokens,
                'history'     => $history
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ❌ NORMAL USER
        |--------------------------------------------------------------------------
        */
        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
