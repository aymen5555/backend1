<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieFournisseur extends Model
{
    protected $table = 'categorie_fournisseurs';

    protected $fillable = ['nom_cat_four', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function fournisseurs(): HasMany
    {
        return $this->hasMany(Fournisseur::class, 'categorie_fournisseur_id');
    }
}
