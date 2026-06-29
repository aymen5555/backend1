<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommandationComplexe extends Model
{
    protected $table = 'recommandation_complexes';

    protected $fillable = [
        'user_id',
        'complexe_id',
        'score',
        'rang',
        'explication',
    ];

    protected $casts = [
        'score' => 'integer',
        'rang' => 'integer',
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
