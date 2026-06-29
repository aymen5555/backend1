<?php

namespace Database\Seeders;

use App\Models\Complexe;
use App\Models\TypeAbonnementAdherent;
use Illuminate\Database\Seeder;

class TypeAbonnementAdherentSeeder extends Seeder
{
    public function run(): void
    {
        $complexes = Complexe::all();
        if ($complexes->isEmpty()) {
            return;
        }

        $formulas = [
            ['nom' => 'Solo', 'description' => 'Abonnement individuel 1 mois', 'nb_mois' => 1, 'tarif' => 120, 'prix_unitaire' => 120, 'niveau_sportif_cible' => 'tous', 'active' => true],
            ['nom' => 'Duo', 'description' => 'Abonnement couple 1 mois', 'nb_mois' => 1, 'tarif' => 200, 'prix_unitaire' => 100, 'niveau_sportif_cible' => 'tous', 'active' => true],
            ['nom' => 'Famille', 'description' => 'Abonnement familial 1 mois', 'nb_mois' => 1, 'tarif' => 280, 'prix_unitaire' => 70, 'niveau_sportif_cible' => 'tous', 'active' => true],
            ['nom' => 'Entreprise', 'description' => 'Abonnement entreprise 1 mois', 'nb_mois' => 1, 'tarif' => 600, 'prix_unitaire' => 60, 'niveau_sportif_cible' => 'tous', 'active' => true],
            ['nom' => 'Mensuel Illimité', 'description' => 'Accès illimité 1 mois', 'nb_mois' => 1, 'tarif' => 180, 'prix_unitaire' => 180, 'niveau_sportif_cible' => 'tous', 'active' => true],
            ['nom' => 'Annuel Premium', 'description' => 'Abonnement annuel avec avantages', 'nb_mois' => 12, 'tarif' => 1500, 'prix_unitaire' => 125, 'niveau_sportif_cible' => 'tous', 'active' => true],
        ];

        foreach ($complexes as $complexe) {
            foreach ($formulas as $f) {
                TypeAbonnementAdherent::firstOrCreate([
                    'complexe_id' => $complexe->id,
                    'nom' => $f['nom'],
                    'nb_mois' => $f['nb_mois'],
                ], $f);
            }
        }
    }
}
