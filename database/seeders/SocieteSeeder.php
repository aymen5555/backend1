<?php

namespace Database\Seeders;

use App\Models\Societe;
use Illuminate\Database\Seeder;

class SocieteSeeder extends Seeder
{
    public function run(): void
    {
        Societe::firstOrCreate(
            ['nom_soc' => 'PlaySpace Holding'],
            ['description' => 'Holding principale du groupe PlaySpace', 'telephone' => '+21671123456']
        );
    }
}
