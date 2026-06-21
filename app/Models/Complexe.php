<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complexe extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'address',
        'city',
        'phone',
        'is_active',
        'image_c',
        'website_c',
        'facebook_c',
        'instagram_c',
        'description_c',
        'email_c',
        'horaire_c',
        'latitude_c',
        'longitude_c',
        'moyenne_notation_c',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_url', 'facebook_url', 'instagram_url', 'website_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_c;
    }

    public function getFacebookUrlAttribute(): ?string
    {
        return $this->facebook_c;
    }

    public function getInstagramUrlAttribute(): ?string
    {
        return $this->instagram_c;
    }

    public function getWebsiteUrlAttribute(): ?string
    {
        return $this->website_c;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function terrains(): HasMany
    {
        return $this->hasMany(Terrain::class, 'complexe_id');
    }

    public function activites(): HasMany
    {
        return $this->hasMany(Activite::class, 'complexe_id');
    }
}
