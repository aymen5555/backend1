<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dirigeant extends Model
{
    protected $table = 'dirigeants';

    protected $fillable = [
        'nom_dir',
        'image',
        'societe_id',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image && ! str_starts_with($this->image, 'http')) {
            return url($this->image);
        }

        return $this->image;
    }

    public function societe(): BelongsTo
    {
        return $this->belongsTo(Societe::class, 'societe_id');
    }
}
