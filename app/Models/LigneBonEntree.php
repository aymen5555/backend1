<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LigneBonEntree extends Model
{
    protected $fillable = [
        'bon_entree_id',
        'produit_id',
        'quantite_entree_lig_bon_ent',
        'prix_unitaire_dachat_lig_bon_ent',
        'sous_total',
    ];

    protected $casts = [
        'prix_unitaire_dachat_lig_bon_ent' => 'decimal:2',
        'sous_total' => 'decimal:2',
    ];

    public function bonEntree(): BelongsTo
    {
        return $this->belongsTo(BonEntree::class, 'bon_entree_id');
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}
