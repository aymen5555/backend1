<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotationComplexe extends Model
{
    protected $table = 'notation_complexes';

    protected $fillable = [
        'user_id',
        'complexe_id',
        'note',
        'commentaire',
    ];

    protected $casts = [
        'note' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class);
    }
}
