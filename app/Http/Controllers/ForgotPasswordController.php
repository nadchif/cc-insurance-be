<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

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
        : response()->json(['message' => __($status), 'error'=>$status], 503);

    }

    public function resetPasswordToken($token)
    {
        return response()->json(['token' => $token]);
    }

}
