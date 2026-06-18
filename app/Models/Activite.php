<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activite extends Model
{
    protected $fillable = [
        'complexe_id',
        'nom',
        'description',
        'sport',
        'niveau',
        'capacite',
        'prix',
        'heure_debut',
        'heure_fin',
        'jours',
        'image',
        'active',
    ];

    protected $casts = [
        'jours'    => 'array',
        'active'   => 'boolean',
        'capacite' => 'integer',
        'prix'     => 'float',
    ];

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ReservationActivite::class, 'activite_id');
    }
}
