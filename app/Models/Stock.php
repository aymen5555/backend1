<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'produit_id',
        'quantite_disponible',
        'quantite_minimale',
    ];

    protected $casts = [
        'quantite_disponible' => 'integer',
        'quantite_minimale' => 'integer',
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function enRupture(): bool
    {
        return $this->quantite_disponible <= 0;
    }

    public function alerteStock(): bool
    {
        return $this->quantite_disponible <= $this->quantite_minimale;
    }
}
