<?php

namespace App\Http\Controllers;

use App\Actions\Inbox\CreateInbox;
use App\Http\Requests\StoreInboxRequest;
use App\Models\Inbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InboxController extends Controller
{
    /**
     * List the user's inboxes.
     */
    public function index(Request $request): Response
    {
        $inboxes = $request->user()->inboxes()
            ->withCount([
                'messages',
                'messages as unread_messages_count' => fn ($query) => $query->whereNull('read_at'),
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (Inbox $inbox) => [
                'id' => $inbox->id,
                'name' => $inbox->name,
                'catch_address' => $inbox->catch_address,
                'messages_count' => $inbox->messages_count,
                'unread_messages_count' => $inbox->unread_messages_count,
            ]);

        return Inertia::render('inboxes/Index', ['inboxes' => $inboxes]);
    }

    /**
     * Create an inbox and reveal its SMTP password once.
     */
    public function store(StoreInboxRequest $request, CreateInbox $createInbox): RedirectResponse
    {
        ['inbox' => $inbox, 'password' => $password] = $createInbox->handle(
            $request->user(),
            $request->validated('name'),
        );

        Inertia::flash('smtpPassword', $password);

        return to_route('inboxes.show', $inbox);
    }

    /**
     * Show an inbox with its messages and, optionally, one opened message.
     */
    public function show(Request $request, Inbox $inbox): Response
    {
        Gate::authorize('manage', $inbox);

        $messages = $inbox->messages()
            ->select(['id', 'inbox_id', 'from_address', 'from_name', 'subject', 'received_at', 'read_at'])
            ->withCount('attachments')
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn ($message) => [
                'id' => $message->id,
                'from_address' => $message->from_address,
                'from_name' => $message->from_name,
                'subject' => $message->subject,
                'received_at' => $message->received_at,
                'is_read' => $message->read_at !== null,
                'attachments_count' => $message->attachments_count,
            ]);

        $message = null;

        if ($request->filled('message')) {
            $opened = $inbox->messages()->with('attachments')->findOrFail($request->integer('message'));

            if ($opened->read_at === null) {
                $opened->forceFill(['read_at' => now()])->save();
            }

            $message = [
                'id' => $opened->id,
                'message_id' => $opened->message_id,
                'from_address' => $opened->from_address,
                'from_name' => $opened->from_name,
                'to' => $opened->to,
                'cc' => $opened->cc,
                'subject' => $opened->subject,
                'text_body' => $opened->text_body,
                'html_body' => $opened->html_body,
                'headers' => $opened->headers,
                'size' => $opened->size,
                'received_at' => $opened->received_at,
                'attachments' => $opened->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'content_type' => $attachment->content_type,
                    'size' => $attachment->size,
                ]),
            ];
        }

        return Inertia::render('inboxes/Show', [
            'inbox' => [
                'id' => $inbox->id,
                'name' => $inbox->name,
                'catch_address' => $inbox->catch_address,
                'smtp_host' => config('roostle.smtp.host'),
                'smtp_port' => config('roostle.smtp.port'),
                'smtp_username' => $inbox->smtp_username,
            ],
            'messages' => $messages,
            'message' => $message,
        ]);
    }

    /**
     * Delete an inbox and its messages.
     */
    public function destroy(Inbox $inbox): RedirectResponse
    {
        Gate::authorize('manage', $inbox);

        $inbox->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inbox deleted.')]);

        return to_route('inboxes.index');
    }
}
