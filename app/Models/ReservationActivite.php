<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationActivite extends Model
{
    protected $fillable = [
        'activite_id',
        'user_id',
        'date_seance',
        'statut',
        'statut_paiement',
        'modalite_paiement',
        'notes',
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
}
