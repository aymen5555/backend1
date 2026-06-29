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
        'categorie_fournisseur_id',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }

    public function categorieFournisseur(): BelongsTo
    {
        return $this->belongsTo(CategorieFournisseur::class, 'categorie_fournisseur_id');
    }
}
