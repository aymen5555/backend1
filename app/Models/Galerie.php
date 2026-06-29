<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Galerie extends Model
{
    protected $table = 'galeries';

    protected $fillable = [
        'complexe_id',
        'image_g',
        'imageKit_file_id_g',
        'ordre',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image_g && ! str_starts_with($this->image_g, 'http')) {
            return url($this->image_g);
        }

        return $this->image_g;
    }

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }
}
