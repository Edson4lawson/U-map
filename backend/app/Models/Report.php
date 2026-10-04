<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'priority', // low, medium, high, urgent
        'status', // pending, in_progress, resolved, dismissed
        'admin_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function message()
    {
        return $this->belongsTo(Message::class, 'reportable_id');
    }

    public function place()
    {
        return $this->belongsTo(Place::class, 'reportable_id');
    }
}
