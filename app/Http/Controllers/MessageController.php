<?php

namespace App\Http\Controllers;

use App\Models\Inbox;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Delete a message and its stored files.
     */
    public function destroy(Inbox $inbox, Message $message): RedirectResponse
    {
        Gate::authorize('manage', $inbox);

        Storage::disk(config('roostle.disk'))->deleteDirectory(dirname($message->raw_path));

        $message->delete();

        return to_route('inboxes.show', $inbox);
    }
}
