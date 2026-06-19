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
        'notes',
    ];

    protected $casts = [
        'montant_total' => 'float',
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

    public function calculerTotal(): void
    {
        $total = $this->lignes()->sum('sous_total');
        $this->update(['montant_total' => $total]);
    }
}
