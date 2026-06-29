<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipement extends Model
{
    protected $table = 'equipements';

    protected $fillable = [
        'nom_eq',
        'icone_eq',
    ];

    public function complexes(): BelongsToMany
    {
        return $this->belongsToMany(Complexe::class, 'complexe_equipement', 'equipement_id', 'complexe_id');
    }
}
