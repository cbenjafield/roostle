<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Inbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidateCredentialsController extends Controller
{
    /**
     * Check SMTP credentials so the SMTP service can accept or reject authentication.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $inbox = Inbox::query()->where('smtp_username', $credentials['username'])->first();

        abort_unless($inbox?->verifySmtpPassword($credentials['password']), 401);

        return response()->json(['inbox_id' => $inbox->id]);
    }
}
