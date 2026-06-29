<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PlaySpaceDemoSeeder::class,
            ProduitSeeder::class,
            TypeDepenseSeeder::class,
            SocieteSeeder::class,
            EquipementSeeder::class,
            CategorieSeeder::class,
            TypeAbonnementAdherentSeeder::class,
        ]);
    }
}
