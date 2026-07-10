<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenteDirecte extends Model
{
    protected $fillable = [
        'produit_id',
        'complexe_id',
        'reference',
        'quantite',
        'prix_unitaire',
        'montant_total',
        'modalite_paiement',
        'stripe_payment_intent_id',
        'client_nom',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'float',
        'montant_total' => 'float',
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
