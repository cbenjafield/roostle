<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Inbox;
use App\Models\Message;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Download an attachment.
     */
    public function show(Inbox $inbox, Message $message, Attachment $attachment): StreamedResponse
    {
        Gate::authorize('manage', $inbox);

        return Storage::disk(config('roostle.disk'))->download($attachment->path, $attachment->filename, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
