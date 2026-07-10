<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglementCommande extends Model
{
    protected $table = 'reglement_commandes';

    protected $fillable = [
        'commande_id',
        'type',
        'montant',
        'reference',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }
}
