<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class AbonnementAdherent extends Model
{
    use HasFactory;

    protected $table = 'abonnements_adherent';

    protected $fillable = [
        'user_id',
        'complexe_id',
        'type_abonnement_id',
        'date_debut',
        'date_fin',
        'montant_vente',
        'remise',
        'montant_apres_remise',
        'statut',
        'paye',
        'reste_a_payer',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant_vente' => 'float',
        'montant_apres_remise' => 'float',
        'reste_a_payer' => 'float',
        'remise' => 'integer',
        'paye' => 'boolean',
    ];

    public function isActif(): bool
    {
        return $this->statut === 'actif' && Carbon::parse($this->date_fin)->gte(Carbon::today());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }

    public function typeAbonnement(): BelongsTo
    {
        return $this->belongsTo(TypeAbonnementAdherent::class, 'type_abonnement_id');
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementAbonnement::class, 'abonnement_id');
    }
}
