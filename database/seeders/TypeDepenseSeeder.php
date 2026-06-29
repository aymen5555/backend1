<?php

namespace Database\Seeders;

use App\Models\TypeDepense;
use Illuminate\Database\Seeder;

class TypeDepenseSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Loyer', 'Électricité', 'Eau', 'Maintenance', 'Salaires', 'Marketing', 'Fournitures', 'Autre'];
        foreach ($types as $t) {
            TypeDepense::firstOrCreate(['designation_ty_dep' => $t], ['active' => true]);
        }
    }
}
