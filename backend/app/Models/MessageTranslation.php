<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTranslation extends Model
{
    protected $fillable = [
        'message_id',
        'target_language',
        'translated_text',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
