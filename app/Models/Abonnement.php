<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Abonnement extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'payment_method',
        'payment_status',
        'price',
        'start_at',
        'expires_at',
        'reference',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'start_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
