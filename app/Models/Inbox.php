<?php

namespace App\Models;

use Database\Factories\InboxFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $catch_token
 * @property string $smtp_username
 * @property string $smtp_password_hash
 * @property-read string $catch_address
 * @property-read int|null $unread_messages_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'catch_token', 'smtp_username', 'smtp_password_hash'])]
#[Hidden(['smtp_password_hash'])]
class Inbox extends Model
{
    /** @use HasFactory<InboxFactory> */
    use HasFactory;

    /**
     * Generate a new random SMTP password.
     */
    public static function generateSmtpPassword(): string
    {
        return Str::random(32);
    }

    /**
     * Hash an SMTP password for storage. The passwords are long random
     * strings, so a fast hash is sufficient and keeps lookups cheap.
     */
    public static function hashSmtpPassword(string $password): string
    {
        return hash('sha256', $password);
    }

    /**
     * Get the full address that delivers to this inbox via the catch domain.
     */
    public function getCatchAddressAttribute(): string
    {
        return $this->catch_token.'@'.config('roostle.catch_domain');
    }

    public function verifySmtpPassword(string $password): bool
    {
        return hash_equals($this->smtp_password_hash, self::hashSmtpPassword($password));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
