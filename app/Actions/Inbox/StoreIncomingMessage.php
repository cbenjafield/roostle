<?php

namespace App\Actions\Inbox;

use App\Models\Inbox;
use App\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreIncomingMessage
{
    /**
     * Store a parsed message, its raw source and its attachments.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $attachments
     */
    public function handle(Inbox $inbox, array $data, UploadedFile $raw, array $attachments = []): Message
    {
        $disk = Storage::disk(config('roostle.disk'));
        $directory = 'messages/'.$inbox->id.'/'.Str::uuid();

        $message = DB::transaction(function () use ($inbox, $data, $raw, $attachments, $disk, $directory) {
            $message = $inbox->messages()->create([
                'message_id' => $data['message_id'] ?? null,
                'from_address' => $data['from_address'],
                'from_name' => $data['from_name'] ?? null,
                'to' => $data['to'] ?? [],
                'cc' => $data['cc'] ?? [],
                'subject' => $data['subject'] ?? null,
                'text_body' => $data['text_body'] ?? null,
                'html_body' => $data['html_body'] ?? null,
                'headers' => $data['headers'] ?? [],
                'raw_path' => $disk->putFileAs($directory, $raw, 'message.eml'),
                'size' => $raw->getSize(),
                'received_at' => $data['received_at'] ?? now(),
            ]);

            foreach ($attachments as $index => $file) {
                $message->attachments()->create([
                    'filename' => $file->getClientOriginalName(),
                    'content_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'path' => $disk->putFileAs($directory.'/attachments', $file, $index.'-'.Str::random(8)),
                ]);
            }

            return $message;
        });

        return $message;
    }
}
