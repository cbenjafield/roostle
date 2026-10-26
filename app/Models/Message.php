<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $inbox_id
 * @property string|null $message_id
 * @property string $from_address
 * @property string|null $from_name
 * @property array<int, array{address: string, name: string|null}> $to
 * @property array<int, array{address: string, name: string|null}> $cc
 * @property string|null $subject
 * @property string|null $text_body
 * @property string|null $html_body
 * @property array<int, array{name: string, value: string}> $headers
 * @property string $raw_path
 * @property int $size
 * @property Carbon $received_at
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'message_id', 'from_address', 'from_name', 'to', 'cc', 'subject',
    'text_body', 'html_body', 'headers', 'raw_path', 'size', 'received_at',
])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'to' => 'array',
            'cc' => 'array',
            'headers' => 'array',
            'received_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Inbox, $this>
     */
    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
