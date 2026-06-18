<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglementReservation extends Model
{
    protected $fillable = [
        "reservation_id",
        "type",
        "montant",
        "reference",
    ];

    protected $casts = [
        "montant" => "decimal:2",
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}

