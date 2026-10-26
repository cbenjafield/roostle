<?php

namespace App\Http\Controllers;

use App\Models\Inbox;
use App\Models\Message;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageRawController extends Controller
{
    /**
     * Download the raw source of a message.
     */
    public function show(Inbox $inbox, Message $message): StreamedResponse
    {
        Gate::authorize('manage', $inbox);

        return Storage::disk(config('roostle.disk'))->download($message->raw_path, 'message-'.$message->id.'.eml');
    }
}
