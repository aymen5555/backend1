<?php

namespace Database\Seeders;

use App\Models\CategorieProduit;
use App\Models\Complexe;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class ProduitSeeder extends Seeder
{
    private array $imagePool = [
        101 => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400',
        102 => 'https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400',
        103 => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400',
        104 => 'https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400',
        105 => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400',
        106 => 'https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400',
        107 => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400',
        108 => 'https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400',
        109 => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400',
        110 => 'https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400',
        111 => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400',
        112 => 'https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400',
        113 => 'https://images.pexels.com/photos/5698851/pexels-photo-5698851.jpeg?auto=compress&cs=tinysrgb&w=400',
        114 => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400',
        115 => 'https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400',
        116 => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400',
        117 => 'https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400',
        118 => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400',
        119 => 'https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400',
        120 => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?auto=compress&cs=tinysrgb&w=400',
        121 => 'https://images.pexels.com/photos/10340620/pexels-photo-10340620.jpeg?auto=compress&cs=tinysrgb&w=400',
    ];

    public function run(): void
    {
        $catMap = [];
        $cats = [
            'Raquettes & Balles' => 'raquettes-balles',
            'Chaussures de sport' => 'chaussures-sport',
            'Équipement Fitness' => 'equipement-fitness',
            'Vêtements de sport' => 'vetements-sport',
            'Accessoires padel' => 'accessoires-padel',
            'Accessoires tennis' => 'accessoires-tennis',
            'Équipement football' => 'equipement-football',
        ];
        foreach ($cats as $nom => $slug) {
            $c = CategorieProduit::where('slug', $slug)->first();
            if (! $c) {
                $c = CategorieProduit::create(['nom' => $nom, 'slug' => $slug, 'active' => true]);
            }
            $catMap[$slug] = $c->id;
        }

        $complexes = Complexe::all()->keyBy('name');
        $total = 0;
        $imgIndex = 101;

        $produits = [
            'Olympysky Club' => [
                ['nom' => 'Raquette Padel Pro Carbon', 'description' => 'Raquette padel en carbone, idéale pour joueurs intermédiaires et avancés.', 'prix' => 180, 'prix_achat' => 120, 'sport_cible' => 'padel', 'niveau_cible' => 'intermediaire', 'cat' => 'raquettes-balles', 'stock' => 12],
                ['nom' => 'Ballon de Football Taille 5', 'description' => 'Ballon officiel taille 5, cousu main, pour matchs et entraînements.', 'prix' => 55, 'prix_achat' => 35, 'sport_cible' => 'football', 'niveau_cible' => 'tous', 'cat' => 'equipement-football', 'stock' => 20],
                ['nom' => 'Chaussures Football Crampons', 'description' => 'Crampons football moulés, semelle antidérapante, tige synthétique.', 'prix' => 140, 'prix_achat' => 85, 'sport_cible' => 'football', 'niveau_cible' => 'tous', 'cat' => 'chaussures-sport', 'stock' => 8],
                ['nom' => 'Gants de Gardien Pro', 'description' => 'Gants gardien avec paume latex, protection dorsale renforcée. Tailles 6 à 11.', 'prix' => 75, 'prix_achat' => 45, 'sport_cible' => 'football', 'niveau_cible' => 'tous', 'cat' => 'equipement-football', 'stock' => 10],
            ],
            'Padel House Tunisia' => [
                ['nom' => 'Raquette Padel Débutant', 'description' => 'Raquette padel parfaite pour débuter. Légère, maniable.', 'prix' => 95, 'prix_achat' => 60, 'sport_cible' => 'padel', 'niveau_cible' => 'debutant', 'cat' => 'raquettes-balles', 'stock' => 15],
                ['nom' => 'Balles de Padel (tube x3)', 'description' => 'Balles de padel pressurisées, approuvées tournois. Lot de 3 balles.', 'prix' => 14, 'prix_achat' => 8, 'sport_cible' => 'padel', 'niveau_cible' => 'tous', 'cat' => 'raquettes-balles', 'stock' => 50],
                ['nom' => 'Chaussures Padel Indoor', 'description' => 'Chaussures spéciales padel indoor, semelle non marquante.', 'prix' => 155, 'prix_achat' => 95, 'sport_cible' => 'padel', 'niveau_cible' => 'tous', 'cat' => 'chaussures-sport', 'stock' => 12],
                ['nom' => 'Grip Overgrip Padel (x3)', 'description' => 'Surgrip absorbant pour raquette padel, finition antidérapante.', 'prix' => 12, 'prix_achat' => 6, 'sport_cible' => 'padel', 'niveau_cible' => 'tous', 'cat' => 'accessoires-padel', 'stock' => 40],
            ],
            'Tennis Club de Tunis' => [
                ['nom' => 'Raquette Tennis Wilson', 'description' => 'Raquette tennis cadre aluminium renforcée, cordage inclus.', 'prix' => 120, 'prix_achat' => 75, 'sport_cible' => 'tennis', 'niveau_cible' => 'debutant', 'cat' => 'raquettes-balles', 'stock' => 10],
                ['nom' => 'Balles de Tennis (tube x4)', 'description' => 'Balles tennis pressurisées, homologuées ITF. Tube de 4 balles.', 'prix' => 18, 'prix_achat' => 10, 'sport_cible' => 'tennis', 'niveau_cible' => 'tous', 'cat' => 'raquettes-balles', 'stock' => 60],
                ['nom' => 'Chaussures Tennis Terre Battue', 'description' => 'Chaussures tennis semelle chevrons pour terre battue.', 'prix' => 148, 'prix_achat' => 90, 'sport_cible' => 'tennis', 'niveau_cible' => 'tous', 'cat' => 'chaussures-sport', 'stock' => 9],
                ['nom' => 'Cordage Raquette Tennis', 'description' => 'Cordage monofilament 1.25mm, tension recommandée 22-26 kg.', 'prix' => 28, 'prix_achat' => 15, 'sport_cible' => 'tennis', 'niveau_cible' => 'tous', 'cat' => 'accessoires-tennis', 'stock' => 25],
            ],
            'Padel Marsa' => [
                ['nom' => 'Sac de Sport Padel', 'description' => 'Sac raquette padel 2 compartiments, poche chaussures séparée.', 'prix' => 75, 'prix_achat' => 45, 'sport_cible' => 'padel', 'niveau_cible' => 'tous', 'cat' => 'accessoires-padel', 'stock' => 18],
                ['nom' => 'T-Shirt Sport Technique', 'description' => 'T-shirt technique respirant, tissu anti-transpiration DryFit.', 'prix' => 35, 'prix_achat' => 18, 'sport_cible' => 'padel', 'niveau_cible' => 'tous', 'cat' => 'vetements-sport', 'stock' => 30],
                ['nom' => 'Short de Sport', 'description' => 'Short léger élastique, poche zippée. Coupe confortable.', 'prix' => 38, 'prix_achat' => 20, 'sport_cible' => 'padel', 'niveau_cible' => 'tous', 'cat' => 'vetements-sport', 'stock' => 25],
            ],
            'Sassi Padel Club' => [
                ['nom' => 'Protège-poignet Padel', 'description' => 'Bandeau poignet éponge absorbant. Lot de 2. Taille unique.', 'prix' => 16, 'prix_achat' => 8, 'sport_cible' => 'padel', 'niveau_cible' => 'tous', 'cat' => 'accessoires-padel', 'stock' => 35],
                ['nom' => 'Bouteille Thermos Sport 750ml', 'description' => 'Bouteille isotherme inox 750ml, maintien froid 24h chaud 12h.', 'prix' => 42, 'prix_achat' => 22, 'sport_cible' => 'padel', 'niveau_cible' => 'tous', 'cat' => 'accessoires-padel', 'stock' => 20],
                ['nom' => 'Lunettes de Padel', 'description' => 'Lunettes protection padel, monture légère, verres polycarbonate.', 'prix' => 58, 'prix_achat' => 35, 'sport_cible' => 'padel', 'niveau_cible' => 'tous', 'cat' => 'accessoires-padel', 'stock' => 14],
            ],
            'Padel Indoor La Soukra' => [
                ['nom' => 'Tapis de Yoga / Stretching', 'description' => 'Tapis antidérapant 6mm épaisseur, idéal yoga et stretching.', 'prix' => 48, 'prix_achat' => 28, 'sport_cible' => 'fitness', 'niveau_cible' => 'tous', 'cat' => 'equipement-fitness', 'stock' => 22],
                ['nom' => 'Haltères Néoprène 2kg (paire)', 'description' => 'Paire d\'haltères néoprène 2kg. Revêtement antidérapant.', 'prix' => 42, 'prix_achat' => 24, 'sport_cible' => 'fitness', 'niveau_cible' => 'debutant', 'cat' => 'equipement-fitness', 'stock' => 16],
                ['nom' => 'Corde à Sauter Pro', 'description' => 'Corde à sauter roulements à billes, câble acier gainé PVC.', 'prix' => 32, 'prix_achat' => 18, 'sport_cible' => 'fitness', 'niveau_cible' => 'tous', 'cat' => 'equipement-fitness', 'stock' => 28],
            ],
        ];

        foreach ($produits as $nomComplexe => $produitsList) {
            if (! $complexes->has($nomComplexe)) {
                $this->command->warn("⚠ Complexe '$nomComplexe' introuvable, skip.");
                continue;
            }
            $cid = $complexes[$nomComplexe]->id;
            foreach ($produitsList as $p) {
                $produit = Produit::updateOrCreate(
                    ['nom' => $p['nom'], 'complexe_id' => $cid],
                    [
                        'categorie_id' => $catMap[$p['cat']],
                        'complexe_id' => $cid,
                        'nom' => $p['nom'],
                        'description' => $p['description'],
                        'prix' => $p['prix'],
                        'prix_achat' => $p['prix_achat'],
                        'sport_cible' => $p['sport_cible'],
                        'niveau_cible' => $p['niveau_cible'],
                        'image' => $this->imagePool[$imgIndex],
                        'reference' => 'REF-'.strtoupper(substr($p['sport_cible'], 0, 3)).'-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                        'actif' => true,
                    ]
                );

                Stock::updateOrCreate(
                    ['produit_id' => $produit->id],
                    ['quantite_disponible' => $p['stock'], 'quantite_minimale' => 5]
                );

                $total++;
                $imgIndex++;
            }
        }

        $this->command->info("✓ $total produits créés/mis à jour avec stock.");
    }
}