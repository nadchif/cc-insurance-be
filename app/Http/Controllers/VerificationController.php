<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function verify($user_id, Request $request)
    {
        if (!$request->hasValidSignature()) {
            return response()->json([
                'error' => 'Invalid/expired verification signature',
            ], 401);
        }
        $user = User::findOrFail($user_id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            return response()->json([
                'result' => 'success',
                'error' => null,
            ], 200);

        }
        return response()->json([
            'error' => 'User already verified',
        ], 400);
    }

    public function resend(Request $request)
    {
        $request->validate([
            'email'=> 'required|string|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if($user == null){
            // for security reasons spoof success to prevent fishing
            return response()->json([
                'result' => 'success',
                'error' => null,
            ], 200);

            // return response()->json([
            //     'error' => 'User non existent',
            // ], 404);
        }
        
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'error' => 'User already verified',
            ], 400);
        }
        
        $user->sendEmailVerificationNotification();

        return response()->json([
            'result' => 'success',
            'error' => null,
        ], 200);

    }
}
