<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommandationActivite extends Model
{
    protected $table = 'recommandation_activites';

    protected $fillable = [
        'user_id',
        'activite_id',
        'score',
        'rang',
        'explication',
    ];

    protected $casts = [
        'score' => 'integer',
        'rang'  => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }
}
