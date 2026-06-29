<?php

namespace Database\Seeders;

use App\Models\CategorieProduit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorieProduitSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Raquettes & Balles',
            'Chaussures de sport',
            'Équipement Fitness',
            'Vêtements de sport',
            'Accessoires padel',
            'Accessoires tennis',
            'Équipement football',
        ];

        foreach ($categories as $nom) {
            CategorieProduit::firstOrCreate(['nom' => $nom], ['slug' => Str::slug($nom)]);
        }

        $this->command->info('✓ 7 catégories de produits créées.');
    }
}
