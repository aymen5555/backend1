<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeAbonnementAdherent extends Model
{
    use HasFactory;

    protected $table = 'type_abonnement_adherent';

    protected $fillable = [
        'complexe_id',
        'nom',
        'description',
        'nb_mois',
        'tarif',
        'prix_unitaire',
        'niveau_sportif_cible',
        'sport_cible',
        'avantages',
        'active',
        'categorie_abonnement_adherent_id',
    ];

    protected $casts = [
        'avantages' => 'array',
        'active' => 'boolean',
        'tarif' => 'float',
        'prix_unitaire' => 'float',
        'nb_mois' => 'integer',
    ];

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }

    public function abonnements(): HasMany
    {
        return $this->hasMany(AbonnementAdherent::class, 'type_abonnement_id');
    }

    public function categorieAbonnementAdherent(): BelongsTo
    {
        return $this->belongsTo(CategorieAbonnementAdherent::class, 'categorie_abonnement_adherent_id');
    }

    public function detailsAbonnements(): HasMany
    {
        return $this->hasMany(DetailAbonnement::class, 'type_abonnement_adherent_id');
    }
}
