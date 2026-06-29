<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FournisseurInterne extends Model
{
    protected $table = 'fournisseurs_internes';

    protected $fillable = [
        'complexe_id',
        'nom_f_int',
        'raison_sociale_f_int',
        'contact_f_int',
        'tel_f_int',
        'email_f_int',
        'adresse_f_int',
        'matricule_fiscale_f_int',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function bonEntrees(): HasMany
    {
        return $this->hasMany(BonEntree::class, 'fournisseur_interne_id');
    }

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }
}
