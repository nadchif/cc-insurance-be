<?php

namespace App\Notifications;

use Config;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;

class CustomVerifyEmail extends VerifyEmailBase
{
    //    use Queueable;

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        // $prefix = env('FRONTEND_EMAIL_VERIFY_URL', 'http://localhost:8000/verify/');
        $prefix = config('customenv.frontend_api_url');

        $temporarySignedURL = URL::temporarySignedRoute(
            'verification.verify', Carbon::now()->addMinutes(60), ['id' => $notifiable->getKey()]
        );

        // verification url to pass to my frontend. Chif
        $split_url = explode('user/verify/', $temporarySignedURL);
        return $prefix . $split_url[1];
    }
}
