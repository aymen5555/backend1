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

    protected $appends = ['disponible', 'image_url', 'average_rating'];

    public static function generateSlug(string $name): string
    {
        $slug = mb_strtolower($name, 'UTF-8');
        $accents = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i',
            'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'œ' => 'oe', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y',
            'ÿ' => 'y', 'š' => 's', 'ž' => 'z', 'đ' => 'd',
        ];
        $slug = strtr($slug, $accents);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);

        return trim($slug, '-');
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image && ! str_starts_with($this->image, 'http')) {
            return url($this->image);
        }

        return $this->image;
    }

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

    public function ligneBonEntrees(): HasMany
    {
        return $this->hasMany(LigneBonEntree::class, 'produit_id');
    }

    public function ligneBonSorties(): HasMany
    {
        return $this->hasMany(LigneBonSortie::class, 'produit_id');
    }

    public function getDisponibleAttribute(): bool
    {
        return $this->stock && $this->stock->quantite_disponible > 0;
    }

    public function notations(): HasMany
    {
        return $this->hasMany(NotationProduit::class, 'produit_id');
    }

    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->notations()->avg('note');

        return $avg !== null ? round((float) $avg, 1) : null;
    }
}
