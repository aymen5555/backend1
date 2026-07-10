<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commande extends Model
{
    protected $fillable = [
        'user_id',
        'complexe_id',
        'statut',
        'statut_paiement',
        'modalite_paiement',
        'montant_total',
        'montant_paye',
        'reference_paiement',
        'notes',
        'stripe_payment_intent_id',
        'refund_status',
        'refund_reference',
    ];

    protected $casts = [
        'montant_total' => 'float',
        'montant_paye' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'commande_id');
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementCommande::class, 'commande_id');
    }

    public function calculerTotal(): void
    {
        $total = $this->lignes()->sum('sous_total');
        $this->update(['montant_total' => $total]);
    }
}
