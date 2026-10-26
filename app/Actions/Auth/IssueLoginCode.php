<?php

namespace App\Actions\Auth;

use App\Mail\LoginCodeMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class IssueLoginCode
{
    public const LIFETIME_MINUTES = 10;

    /**
     * Generate a fresh six digit code for the email address and send it.
     */
    public function handle(string $email): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put(
            VerifyLoginCode::cacheKey($email),
            ['hash' => VerifyLoginCode::hash($code), 'attempts' => 0],
            now()->addMinutes(self::LIFETIME_MINUTES),
        );

        Mail::to($email)->send(new LoginCodeMail($code, self::LIFETIME_MINUTES));
    }
}
