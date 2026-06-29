<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieRessource extends Model
{
    protected $table = 'categorie_ressources';

    protected $fillable = ['nom_cat_res', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function terrains(): HasMany
    {
        return $this->hasMany(Terrain::class, 'categorie_ressource_id');
    }
}
