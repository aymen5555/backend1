<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationActivite extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'activite_id',
        'user_id',
        'date_seance',
        'statut',
        'statut_paiement',
        'modalite_paiement',
        'notes',
        'montant_paye',
        'reference_paiement',
        'stripe_payment_intent_id',
        'refund_status',
        'refund_reference',
    ];

    protected $casts = [
        'date_seance' => 'date:Y-m-d',
    ];

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class, 'activite_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function updateExpiredStatus(): void
    {
        $now = Carbon::now('Africa/Tunis');
        $todayStr = $now->toDateString();
        $timeStr = $now->toTimeString();

        // Activity reservations do not currently support a separate expired status.
        // Past reserved sessions remain in their original status for display logic.
        self::where('statut', 'reservee')
            ->where('date_seance', '<', $todayStr)
            ->update(['statut' => 'reservee']);

        self::where('statut', 'reservee')
            ->where('date_seance', '=', $todayStr)
            ->whereHas('activite', function ($query) use ($timeStr) {
                $query->where('heure_debut', '<', $timeStr);
            })
            ->update(['statut' => 'reservee']);
    }
}
