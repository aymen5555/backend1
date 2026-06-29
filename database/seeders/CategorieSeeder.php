<?php

namespace Database\Seeders;

use App\Models\CategorieAbonnementAdherent;
use App\Models\CategorieFournisseur;
use App\Models\CategorieRessource;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Solo', 'Duo', 'Famille', 'Entreprise'] as $c) {
            CategorieAbonnementAdherent::firstOrCreate(['nom_cat_abo_ad' => $c]);
        }
        foreach (['Matériel', 'Service', 'Consommable'] as $c) {
            CategorieFournisseur::firstOrCreate(['nom_cat_four' => $c]);
        }
        foreach (['Indoor', 'Outdoor', 'Premium', 'Standard'] as $c) {
            CategorieRessource::firstOrCreate(['nom_cat_res' => $c]);
        }
    }
}
