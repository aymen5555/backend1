<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_role',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'timestamp',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'timestamp' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to find audits for a specific model
     */
    public function scopeForModel($query, string $modelType, int $modelId)
    {
        return $query->where('model_type', $modelType)->where('model_id', $modelId);
    }

    /**
     * Scope to find audits by a specific user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to find critical actions (refunds, cancellations, deletes)
     */
    public function scopeCritical($query)
    {
        return $query->whereIn('action', ['refund', 'cancel', 'delete', 'payment']);
    }

    /**
     * Scope to find recent audits (last N minutes)
     */
    public function scopeRecent($query, int $minutes = 30)
    {
        return $query->where('timestamp', '>=', now()->subMinutes($minutes));
    }
}
