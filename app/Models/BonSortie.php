<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonSortie extends Model
{
    protected $fillable = [
        'reference',
        'date_bon_sor',
        'total_ttc_bon_sor',
        'complexe_id',
        'motif',
        'created_by',
        'montant_paye',
        'reference_paiement',
        'statut_paiement',
    ];

    protected $casts = [
        'date_bon_sor' => 'date',
        'total_ttc_bon_sor' => 'decimal:2',
        'montant_paye' => 'decimal:2',
    ];

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneBonSortie::class, 'bon_sortie_id');
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementBonSortie::class, 'bon_sortie_id');
    }
}
