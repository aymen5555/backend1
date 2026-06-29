<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilFitness extends Model
{
    protected $table = 'profil_fitness';

    protected $fillable = [
        'user_id',
        'taille',
        'poids',
        'objectif_sportif',
        'niveau_sportif',
        'sport_prefere',
        'poids_cible',
        'budget_mensuel_min',
        'budget_mensuel_max',
        'imc',
        'verif_fitness',
    ];

    protected $casts = [
        'taille' => 'integer',
        'poids' => 'float',
        'poids_cible' => 'float',
        'budget_mensuel_min' => 'float',
        'budget_mensuel_max' => 'float',
        'imc' => 'float',
        'verif_fitness' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate IMC from taille (cm) and poids (kg).
     * Returns null if either value is missing.
     */
    public static function calculerImc(?int $taille, ?float $poids): ?float
    {
        if (! $taille || ! $poids || $taille <= 0) {
            return null;
        }
        $tailleM = $taille / 100;

        return round($poids / ($tailleM * $tailleM), 1);
    }
}
