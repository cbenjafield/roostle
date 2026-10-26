<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Cache;

class VerifyLoginCode
{
    public const MAX_ATTEMPTS = 5;

    /**
     * Check the submitted code, consuming it on success and after too many failures.
     */
    public function handle(string $email, string $code): bool
    {
        $key = self::cacheKey($email);
        $entry = Cache::get($key);

        if ($entry === null) {
            return false;
        }

        if (hash_equals($entry['hash'], self::hash($code))) {
            Cache::forget($key);

            return true;
        }

        if (++$entry['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);
        } else {
            Cache::put($key, $entry, now()->addMinutes(IssueLoginCode::LIFETIME_MINUTES));
        }

        return false;
    }

    public static function cacheKey(string $email): string
    {
        return 'login-code:'.sha1($email);
    }

    public static function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
