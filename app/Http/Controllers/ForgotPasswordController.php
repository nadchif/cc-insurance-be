<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;


class ForgotPasswordController extends Controller
{
    // use SendsPasswordResetEmails;
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function requestLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
        ? response()->json(['message' => __($status), 'status'=>$status], 202)
        : response()->json(['error'=>__($status), 'status'=>$status], 503);

    }

    public function resetPasswordToken($token)
    {
        return response()->json(['token' => $token]);
    }

    public function setNewPassword(Request $request){
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:7|confirmed',
        ]);
    
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
    
                $user->setRememberToken(Str::random(60));
    
                event(new PasswordReset($user));
            }
        );
    
        return $status == Password::PASSWORD_RESET
                    ? response()->json(['message' => __($status), 'status'=>$status], 202)
                    : response()->json(['error'=>__($status), 'status'=>$status], 503);
    }

}
