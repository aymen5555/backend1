<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglementBonEntree extends Model
{
    protected $table = 'reglement_bon_entrees';

    protected $fillable = [
        'bon_entree_id',
        'type',
        'montant',
        'reference',
        'created_by',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function bonEntree(): BelongsTo
    {
        return $this->belongsTo(BonEntree::class, 'bon_entree_id');
    }
}
