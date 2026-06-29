<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CategorieProduit extends Model
{
    protected $table = 'categorie_produits';

    protected $fillable = [
        'nom',
        'description',
        'slug',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($categorie) {
            if (empty($categorie->slug) && ! empty($categorie->nom)) {
                $categorie->slug = Str::slug($categorie->nom);
            }
        });
    }

    public function produits(): HasMany
    {
        return $this->hasMany(Produit::class, 'categorie_id');
    }

    public function produitsActive(): HasMany
    {
        return $this->hasMany(Produit::class, 'categorie_id')->where('actif', true);
    }
}
