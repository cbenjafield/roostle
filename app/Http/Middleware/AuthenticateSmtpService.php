<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSmtpService
{
    /**
     * Only allow requests carrying the shared SMTP service token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('roostle.smtp.service_token');
        $provided = (string) $request->bearerToken();

        abort_unless(
            is_string($expected) && $expected !== '' && hash_equals($expected, $provided),
            401,
        );

        return $next($request);
    }
}
