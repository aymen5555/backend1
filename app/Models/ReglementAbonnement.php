<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglementAbonnement extends Model
{
    protected $table = 'reglements_abonnement';

    protected $fillable = [
        'abonnement_id',
        'montant',
        'date_reglement',
        'modalite',
        'reference',
        'encaisse',
    ];

    protected $casts = [
        'montant' => 'float',
        'date_reglement' => 'date',
        'encaisse' => 'boolean',
    ];

    public function abonnement(): BelongsTo
    {
        return $this->belongsTo(AbonnementAdherent::class, 'abonnement_id');
    }
}
