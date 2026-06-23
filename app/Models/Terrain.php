<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Terrain extends Model
{
    protected $fillable = [
        'complexe_id',
        'name',
        'sport_type',
        'price_per_hour',
        'is_active',
        'image_t',
        'description_t',
        'capacite_t',
        'heure_ouverture',
        'heure_fermeture',
        'nbheures_seance',
        'nbminute_seance',
    ];

    protected $casts = [
        'price_per_hour' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image_t && !str_starts_with($this->image_t, 'http')) {
            return url($this->image_t);
        }
        return $this->image_t;
    }

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'terrain_id');
    }
}
