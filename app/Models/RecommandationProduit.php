<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommandationProduit extends Model
{
    protected $table = 'recommandation_produits';

    protected $fillable = [
        'user_id',
        'produit_id',
        'score',
        'rang',
        'explication',
    ];

    protected $casts = [
        'score' => 'integer',
        'rang' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }
}
