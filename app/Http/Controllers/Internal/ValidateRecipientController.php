<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Inbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ValidateRecipientController extends Controller
{
    /**
     * Resolve a catch address to its inbox so the SMTP service can accept or reject the recipient.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $address = Str::lower((string) $request->validate(['address' => ['required', 'string', 'max:320']])['address']);

        [$token, $domain] = array_pad(explode('@', $address, 2), 2, null);

        $inbox = $domain === Str::lower(config('roostle.catch_domain'))
            ? Inbox::query()->where('catch_token', $token)->first()
            : null;

        abort_if($inbox === null, 404);

        return response()->json(['inbox_id' => $inbox->id]);
    }
}
