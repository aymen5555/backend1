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
}
