<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\MessageEncryptionService;

/**
 * App\Models\Message
 *
 * @property int $id
 * @property int $sender_id
 * @property int $receiver_id
 * @property string $content
 * @property string|null $encrypted_content
 * @property bool $is_read
 * @property bool $is_encrypted
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $decrypted_content
 * @property-read \App\Models\User $sender
 * @property-read \App\Models\User $receiver
 */
class Message extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'encrypted_content', 'is_read', 'is_encrypted'];

    protected $hidden = ['encrypted_content', 'is_encrypted'];

    // Only expose 'content' in JSON — it returns the decrypted value via getContentAttribute.
    // 'decrypted_content' is accessible internally but not serialized to avoid duplication.
    protected $appends = ['content'];

    /**
     * Get virtual content attribute (returns decrypted content).
     */
    public function getContentAttribute(): string
    {
        return $this->decrypted_content;
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the decrypted content.
     * Reads exclusively from encrypted_content — the plaintext content column no longer exists.
     */
    public function getDecryptedContentAttribute(): string
    {
        if ($this->is_encrypted && $this->encrypted_content) {
            try {
                $encryptionService = new MessageEncryptionService();
                return $encryptionService->decrypt($this->encrypted_content);
            } catch (\Exception $e) {
                \Log::error('Failed to decrypt message content', [
                    'message_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
                return '[Decryption Error]';
            }
        }

        return '';
    }

    /**
     * Set the content attribute with encryption.
     */
    public function setContentAttribute($value): void
    {
        try {
            $encryptionService = new MessageEncryptionService();
            $this->attributes['encrypted_content'] = $encryptionService->encrypt($value);
            $this->attributes['is_encrypted'] = true;
        } catch (\Exception $e) {
            \Log::error('Failed to encrypt message content', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to encrypt message content');
        }
    }

    /**
     * Scope to get messages for a conversation.
     */
    public function scopeConversation($query, $userId, $otherUserId)
    {
        return $query->where(function ($q) use ($userId, $otherUserId) {
            $q->where('sender_id', $userId)->where('receiver_id', $otherUserId);
        })->orWhere(function ($q) use ($userId, $otherUserId) {
            $q->where('sender_id', $otherUserId)->where('receiver_id', $userId);
        });
    }
}
