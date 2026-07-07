<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depense extends Model
{
    use SoftDeletes;

    protected $table = 'depenses';

    protected $fillable = [
        'date_depense',
        'montant_dep',
        'commentaire_dep',
        'type_depense_id',
        'complexe_id',
        'created_by',
    ];

    protected $casts = [
        'date_depense' => 'date',
        'montant_dep' => 'decimal:3',
    ];

    public function typeDepense(): BelongsTo
    {
        return $this->belongsTo(TypeDepense::class, 'type_depense_id');
    }

    public function complexe(): BelongsTo
    {
        return $this->belongsTo(Complexe::class, 'complexe_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
