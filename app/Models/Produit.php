<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Produit extends Model
{
    protected $fillable = [
        'categorie_id',
        'complexe_id',
        'nom',
        'description',
        'prix',
        'prix_achat',
        'sport_cible',
        'niveau_cible',
        'image',
        'reference',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    protected $appends = ['disponible'];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieProduit::class, 'categorie_id');
    }

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class, 'produit_id');
    }

    public function ligneCommandes(): HasMany
    {
        return $this->hasMany(LigneCommande::class, 'produit_id');
    }

    public function ventesDirectes(): HasMany
    {
        return $this->hasMany(VenteDirecte::class, 'produit_id');
    }

    public function getDisponibleAttribute(): bool
    {
        return $this->stock && $this->stock->quantite_disponible > 0;
    }
}
