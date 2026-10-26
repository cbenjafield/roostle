<?php

namespace App\Actions\Inbox;

use App\Models\Inbox;
use App\Models\User;
use Illuminate\Support\Str;

class CreateInbox
{
    /**
     * Create an inbox with a catch address and SMTP credentials.
     *
     * The plain password is only available from the return value.
     *
     * @return array{inbox: Inbox, password: string}
     */
    public function handle(User $user, string $name): array
    {
        $password = Inbox::generateSmtpPassword();

        $inbox = $user->inboxes()->create([
            'name' => $name,
            'catch_token' => Str::lower(Str::random(16)),
            'smtp_username' => 'rst'.Str::lower(Str::random(16)),
            'smtp_password_hash' => Inbox::hashSmtpPassword($password),
        ]);

        return ['inbox' => $inbox, 'password' => $password];
    }
}
