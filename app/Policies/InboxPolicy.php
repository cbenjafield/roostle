<?php

namespace App\Policies;

use App\Models\Inbox;
use App\Models\User;

class InboxPolicy
{
    /**
     * Determine whether the user owns the inbox.
     */
    public function manage(User $user, Inbox $inbox): bool
    {
        return $inbox->user_id === $user->id;
    }
}
