<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglementBonSortie extends Model
{
    protected $table = 'reglement_bon_sorties';

    protected $fillable = [
        'bon_sortie_id',
        'type',
        'montant',
        'reference',
        'created_by',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function bonSortie(): BelongsTo
    {
        return $this->belongsTo(BonSortie::class, 'bon_sortie_id');
    }
}
