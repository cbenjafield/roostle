<?php

namespace App\Http\Controllers;

use App\Models\Inbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InboxPasswordController extends Controller
{
    /**
     * Replace the inbox's SMTP password and reveal the new one once.
     */
    public function update(Inbox $inbox): RedirectResponse
    {
        Gate::authorize('manage', $inbox);

        $password = Inbox::generateSmtpPassword();

        $inbox->update(['smtp_password_hash' => Inbox::hashSmtpPassword($password)]);

        Inertia::flash('smtpPassword', $password);

        return to_route('inboxes.show', $inbox);
    }
}
