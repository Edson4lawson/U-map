<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Conversation
 *
 * @property int $id
 * @property int $user_one_id
 * @property int $user_two_id
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $userOne
 * @property-read \App\Models\User $userTwo
 */
class Conversation extends Model
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * Find or create conversation between two users with normalized order.
     */
    public static function findOrCreateBetween(int $userId1, int $userId2): self
    {
        $userOne = min($userId1, $userId2);
        $userTwo = max($userId1, $userId2);

        return static::firstOrCreate([
            'user_one_id' => $userOne,
            'user_two_id' => $userTwo,
        ]);
    }

    /**
     * Get the other user in the conversation.
     */
    public function getOtherUserId(int $currentUserId): int
    {
        return $this->user_one_id === $currentUserId ? $this->user_two_id : $this->user_one_id;
    }
}
