<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeDepense extends Model
{
    protected $table = 'type_depenses';

    protected $fillable = [
        'designation_ty_dep',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class, 'type_depense_id');
    }
}
