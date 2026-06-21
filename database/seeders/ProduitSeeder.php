<?php

namespace Database\Seeders;

use App\Models\CategorieProduit;
use App\Models\Complexe;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class ProduitSeeder extends Seeder
{
    public function run(): void
    {
        $complexeIds = Complexe::pluck('id')->toArray();
        if (empty($complexeIds)) {
            $complexeIds = [1]; // fallback if no complexes exist yet
        }
        $n = count($complexeIds);

        $categories = [
            ['nom' => 'Raquettes & Balles', 'description' => 'Équipements de raquettes et balles pour sports de raquette'],
            ['nom' => 'Équipement Fitness', 'description' => 'Matériel et accessoires pour musculation et fitness'],
            ['nom' => 'Tenues & Accessoires', 'description' => 'Vêtements et accessoires sportifs'],
        ];

        foreach ($categories as $cat) {
            CategorieProduit::firstOrCreate(
                ['nom' => $cat['nom']],
                $cat
            );
        }

        $raquettesEtBalles = CategorieProduit::where('nom', 'Raquettes & Balles')->first();
        $equipementFitness = CategorieProduit::where('nom', 'Équipement Fitness')->first();
        $tenuesEtAccessoires = CategorieProduit::where('nom', 'Tenues & Accessoires')->first();

        $produits = [
            [
                'categorie_id' => $raquettesEtBalles->id,
                'complexe_id' => $complexeIds[0 % $n],
                'nom' => 'Raquette Padel Pro',
                'description' => 'Raquette de padel professionnelle, idéale pour tous niveaux',
                'prix' => 180.00,
                'sport_cible' => 'padel',
                'niveau_cible' => 'tous',
                'quantite_initiale' => 12,
            ],
            [
                'categorie_id' => $raquettesEtBalles->id,
                'complexe_id' => $complexeIds[1 % $n],
                'nom' => 'Balles de Tennis (tube x3)',
                'description' => 'Tube de 3 balles de tennis bonne qualité',
                'prix' => 18.00,
                'sport_cible' => 'tennis',
                'niveau_cible' => 'tous',
                'quantite_initiale' => 45,
            ],
            [
                'categorie_id' => $equipementFitness->id,
                'complexe_id' => $complexeIds[2 % $n],
                'nom' => 'Tapis de Yoga',
                'description' => 'Tapis de yoga antidérapant, épaisseur optimale',
                'prix' => 65.00,
                'sport_cible' => 'yoga',
                'niveau_cible' => 'tous',
                'quantite_initiale' => 20,
            ],
            [
                'categorie_id' => $equipementFitness->id,
                'complexe_id' => $complexeIds[3 % $n],
                'nom' => 'Gants de Musculation',
                'description' => 'Gants de musculation pour un bon maintien',
                'prix' => 35.00,
                'sport_cible' => 'musculation',
                'niveau_cible' => 'tous',
                'quantite_initiale' => 30,
            ],
            [
                'categorie_id' => $tenuesEtAccessoires->id,
                'complexe_id' => $complexeIds[4 % $n],
                'nom' => 'Ballon de Football Taille 5',
                'description' => 'Ballon de football officiel Taille 5 pour matchs et entraînements',
                'prix' => 55.00,
                'sport_cible' => 'football',
                'niveau_cible' => 'tous',
                'quantite_initiale' => 15,
            ],
            [
                'categorie_id' => $tenuesEtAccessoires->id,
                'complexe_id' => $complexeIds[5 % $n],
                'nom' => 'Filet de Volleyball',
                'description' => 'Filet de volleyball professionnel pour terrain',
                'prix' => 120.00,
                'sport_cible' => 'volleyball',
                'niveau_cible' => 'tous',
                'quantite_initiale' => 8,
            ],
        ];

        foreach ($produits as $produitData) {
            $quantiteInitiale = $produitData['quantite_initiale'];
            unset($produitData['quantite_initiale']);

            // Explicitly generate the Picsum placeholder URL for seeded products
            $slug = Produit::generateSlug($produitData['nom']);
            $produitData['image'] = "https://picsum.photos/seed/{$slug}/400/400";

            $produit = Produit::create($produitData);

            Stock::create([
                'produit_id' => $produit->id,
                'quantite_disponible' => $quantiteInitiale,
                'quantite_minimale' => 5,
            ]);
        }
    }
}