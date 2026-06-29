<?php

namespace Database\Seeders;

use App\Models\Equipement;
use Illuminate\Database\Seeder;

class EquipementSeeder extends Seeder
{
    public function run(): void
    {
        $eqs = ['Vestiaires', 'Parking', 'Wifi', 'Climatisation', 'Douches', 'Cafétéria', 'Salle de repos', 'Toilettes PMR'];
        foreach ($eqs as $e) {
            Equipement::firstOrCreate(['nom_eq' => $e]);
        }
    }
}
