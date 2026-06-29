<?php

namespace App\Models;

use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'terrain_id',
        'start_at',
        'end_at',
        'status',
        'notes',
        'type',
        'modalite_paiement',
        'statut_paiement',
        'montant_paye',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected $appends = ['heure_debut', 'heure_fin', 'paid', 'tarif_calcule'];

    public function getHeureDebutAttribute(): string
    {
        return $this->start_at->format('H:i');
    }

    public function getHeureFinAttribute(): string
    {
        return $this->end_at->format('H:i');
    }

    public function getPaidAttribute(): bool
    {
        return $this->statut_paiement === 'paye';
    }

    public function getTarifCalculeAttribute(): float
    {
        if ($this->montant_paye > 0) {
            return (float) $this->montant_paye;
        }

        if ($this->terrain && $this->start_at && $this->end_at && $this->user) {
            return (float) app(PricingService::class)->calculate(
                $this->terrain->price_per_hour ?? 0,
                $this->start_at,
                $this->end_at,
                $this->user,
                $this->terrain->complexe_id
            );
        }

        return (float) ($this->terrain->price_per_hour ?? 0);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function terrain(): BelongsTo
    {
        return $this->belongsTo(Terrain::class);
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementReservation::class);
    }

    public static function updateExpiredStatus(): void
    {
        $now = Carbon::now('Africa/Tunis');
        // Cancel card reservations that have been pending for more than 5 minutes
        $cutoff = $now->copy()->subMinutes(5);
        Reservation::where('status', 'pending')
            ->where('modalite_paiement', 'carte')
            ->where('created_at', '<', $cutoff)
            ->update(['status' => 'cancelled']);

        // Mark pending reservations whose start time has passed as expired
        Reservation::where('status', 'pending')
            ->where('start_at', '<', $now)
            ->update(['status' => 'expired']);

        // Mark confirmed reservations whose start time has passed as played
        Reservation::where('status', 'confirmed')
            ->where('start_at', '<', $now)
            ->update(['status' => 'played']);
    }

    public function scopeUpdateExpiredStatus($query): void
    {
        // Alias the static updater so controllers can call the scope before queries
        self::updateExpiredStatus();
    }
}
