<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonEntree extends Model
{
    protected $fillable = [
        'reference',
        'date_bon_ent',
        'total_ttc_bon_ent',
        'fournisseur_interne_id',
        'complexe_id',
        'created_by',
        'montant_paye',
        'reference_paiement',
        'statut_paiement',
    ];

    protected $casts = [
        'date_bon_ent' => 'date',
        'total_ttc_bon_ent' => 'decimal:2',
        'montant_paye' => 'decimal:2',
    ];

    public function fournisseurInterne(): BelongsTo
    {
        return $this->belongsTo(FournisseurInterne::class, 'fournisseur_interne_id');
    }

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
        return $this->hasMany(LigneBonEntree::class, 'bon_entree_id');
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementBonEntree::class, 'bon_entree_id');
    }
}
