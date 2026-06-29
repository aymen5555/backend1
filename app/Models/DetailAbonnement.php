<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailAbonnement extends Model
{
    protected $table = 'details_abonnements';

    protected $fillable = [
        'type_abonnement_adherent_id',
        'jour_seance',
        'heure_debut_de_abo',
        'heure_fin_de_abo',
    ];

    public function typeAbonnement(): BelongsTo
    {
        return $this->belongsTo(TypeAbonnementAdherent::class, 'type_abonnement_adherent_id');
    }
}
