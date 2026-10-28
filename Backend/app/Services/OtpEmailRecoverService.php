<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class OtpEmailRecoverService
{
    public function send(string $email, string $otp): void
    {
        Mail::send(
            'emails.recoverotp',
            [
                'otp' => $otp,
            ],
            function ($message) use ($email) {
                $message->to($email);
                $message->subject('Password Recovery OTP');
            }
        );
    }
}