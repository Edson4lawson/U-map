<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'ip_address',
        'user_agent',
        'payload',
        'old_values',
        'new_values',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the user that owns the audit log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include logs for a specific action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs for a specific resource.
     */
    public function scopeResource($query, string $resourceType, $resourceId = null)
    {
        $query->where('resource_type', $resourceType);
        if ($resourceId !== null) {
            $query->where('resource_id', $resourceId);
        }
        return $query;
    }

    /**
     * Scope a query to only include logs for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include logs within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include failed logs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failure');
    }

    /**
     * Scope a query to only include blocked logs.
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    /**
     * Scope a query to only include successful logs.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }
}
