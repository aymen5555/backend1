<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneBonSortie extends Model
{
    protected $fillable = [
        'bon_sortie_id',
        'produit_id',
        'quantite_entree_lig_bon_sor',
        'prix_unitaire_constate',
    ];

    protected $casts = [
        'prix_unitaire_constate' => 'decimal:2',
    ];

    public function bonSortie(): BelongsTo
    {
        return $this->belongsTo(BonSortie::class, 'bon_sortie_id');
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    protected $appends = ['sous_total'];

    public function getSousTotalAttribute(): float
    {
        $qty = (float) ($this->quantite_entree_lig_bon_sor ?? 0);
        $prix = (float) ($this->prix_unitaire_constate ?? 0);

        return round($qty * $prix, 2);
    }
}
