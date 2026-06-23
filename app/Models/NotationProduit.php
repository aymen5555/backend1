<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotationProduit extends Model
{
    protected $table = 'notation_produits';

    protected $fillable = [
        'user_id',
        'produit_id',
        'note',
        'commentaire',
    ];

    protected $casts = [
        'note' => 'integer',
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
