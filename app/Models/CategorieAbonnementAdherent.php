<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieAbonnementAdherent extends Model
{
    protected $table = 'categorie_abonnement_adherents';

    protected $fillable = ['nom_cat_abo_ad', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function typesAbonnements(): HasMany
    {
        return $this->hasMany(TypeAbonnementAdherent::class, 'categorie_abonnement_adherent_id');
    }
}
