<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fournisseur extends Model
{
    protected $fillable = [
        'complexe_id',
        'nom',
        'contact',
        'telephone',
        'email',
        'adresse',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }
}
