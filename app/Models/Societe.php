<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Societe extends Model
{
    protected $table = 'societes';

    protected $fillable = [
        'nom_soc',
        'image',
        'description',
        'telephone',
        'date_de_creation',
    ];

    protected $casts = [
        'date_de_creation' => 'date',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image && ! str_starts_with($this->image, 'http')) {
            return url($this->image);
        }

        return $this->image;
    }

    public function dirigeants(): HasMany
    {
        return $this->hasMany(Dirigeant::class, 'societe_id');
    }

    public function complexes(): HasMany
    {
        return $this->hasMany(Complexe::class, 'societe_id');
    }
}
