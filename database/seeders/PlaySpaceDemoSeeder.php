<?php

namespace Database\Seeders;

use App\Models\Complexe;
use App\Models\Terrain;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlaySpaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'aymencharf55@gmail.com')->first();

        if (!$admin) {
            return;
        }

        // Complexe 1: Olympysky Club
        $complexe1 = Complexe::firstOrCreate(
            ['owner_id' => $admin->id, 'name' => 'Olympysky Club'],
            [
                'description'       => 'Demo padel complex in Tunis – courts available for booking.',
                'address'           => 'Avenue Beji Caid Essebsi, Les Berges du Lac 2, Tunis',
                'city'              => 'Tunis',
                'phone'             => '+216 29 623 010',
                'is_active'         => true,
                'email_c'           => 'gym@olympysky.com',
                'horaire_c'         => '07:00 - 23:00',
                'latitude_c'        => 36.8378,
                'longitude_c'       => 10.2306,
                'moyenne_notation_c' => 4.7,
                'website_c'         => 'https://olympysky.com',
                'facebook_c'        => 'https://www.facebook.com/olympyskygym',
                'instagram_c'       => 'https://www.instagram.com/olympysky_club',
                'image_c'           => 'https://images.pexels.com/photos/61135/pexels-photo-61135.jpeg',
                'description_c'     => 'Tunisia\'s ultimate sports hub. Padel, Football, Gym, Indoor Pool, Spa, Coffee Corner, Kids Area. Le premier complexe sportif luxueux en Tunisie.',
            ]
        );

        $terrains1 = [
            ['name' => 'Court Padel A - Olympysky', 'sport_type' => 'padel', 'price_per_hour' => 55.00, 'description_t' => 'Court de padel professionnel en plein air avec eclairage LED', 'capacite_t' => 4, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/32474981/pexels-photo-32474981.jpeg'],
            ['name' => 'Court Padel B - Olympysky', 'sport_type' => 'padel', 'price_per_hour' => 55.00, 'description_t' => 'Court couvert climatise, ideal pour toute saison', 'capacite_t' => 4, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/32897040/pexels-photo-32897040.jpeg'],
            ['name' => 'Court Padel C - Olympysky', 'sport_type' => 'padel', 'price_per_hour' => 55.00, 'description_t' => 'Court panoramique avec vue sur le lac', 'capacite_t' => 4, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/32897038/pexels-photo-32897038.jpeg'],
            ['name' => 'Terrain Football 5 - Olympysky', 'sport_type' => 'football', 'price_per_hour' => 80.00, 'description_t' => 'Terrain de football en gazon synthetique derniere generation', 'capacite_t' => 10, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/46798/the-ball-stadion-football-the-pitch-46798.jpeg'],
            ['name' => 'Terrain Football 7 - Olympysky', 'sport_type' => 'football', 'price_per_hour' => 90.00, 'description_t' => 'Grand terrain gazon synthetique pour matchs officiels', 'capacite_t' => 14, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/399187/pexels-photo-399187.jpeg'],
        ];

        foreach ($terrains1 as $terrain) {
            Terrain::firstOrCreate(
                ['complexe_id' => $complexe1->id, 'name' => $terrain['name']],
                [
                    'sport_type'      => $terrain['sport_type'],
                    'price_per_hour'  => $terrain['price_per_hour'],
                    'is_active'       => true,
                    'description_t'   => $terrain['description_t'],
                    'capacite_t'      => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t'         => $terrain['image_t'],
                ]
            );
        }

        // Complexe 2: Padel House Tunisia
        $complexe2 = Complexe::firstOrCreate(
            ['owner_id' => $admin->id, 'name' => 'Padel House Tunisia'],
            [
                'description'        => 'Demo padel complex in Tunis – courts available for booking.',
                'address'            => '676 Sidi Amor, Ariana, Tunis',
                'city'               => 'Ariana',
                'phone'              => '+216 22 383 383',
                'is_active'          => true,
                'email_c'            => 'contact@padelhouse.tn',
                'horaire_c'          => '08:00 - 22:00',
                'latitude_c'         => 36.8625,
                'longitude_c'        => 10.1956,
                'moyenne_notation_c' => 4.5,
                'website_c'          => 'https://padellands.com/en/pistas-de-padel/otros-paises/tunisia',
                'facebook_c'         => 'https://www.facebook.com/PadelHouseTunisia',
                'instagram_c'        => 'https://www.instagram.com/padelhousetn',
                'image_c'            => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg',
                'description_c'      => 'Premier club de padel indoor en Ariana. 3 courts couverts climatisés, vestiaires modernes, boutique et café.',
            ]
        );

        $terrains2 = [
            ['name' => 'Court Padel Indoor 1', 'sport_type' => 'padel', 'price_per_hour' => 60.00, 'description_t' => 'Court indoor premium avec murs en verre trempé', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/35248286/pexels-photo-35248286.jpeg'],
            ['name' => 'Court Padel Indoor 2', 'sport_type' => 'padel', 'price_per_hour' => 60.00, 'description_t' => 'Court climatisé avec système d eclairage LED professionnel', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/35248374/pexels-photo-35248374.jpeg'],
            ['name' => 'Court Padel Indoor 3', 'sport_type' => 'padel', 'price_per_hour' => 65.00, 'description_t' => 'Court VIP avec vue panoramique sur le club', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/35248501/pexels-photo-35248501.jpeg'],
        ];

        foreach ($terrains2 as $terrain) {
            Terrain::firstOrCreate(
                ['complexe_id' => $complexe2->id, 'name' => $terrain['name']],
                [
                    'sport_type'      => $terrain['sport_type'],
                    'price_per_hour'  => $terrain['price_per_hour'],
                    'is_active'       => true,
                    'description_t'   => $terrain['description_t'],
                    'capacite_t'      => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t'         => $terrain['image_t'],
                ]
            );
        }

        // Complexe 3: Tennis Club de Tunis
        $complexe3 = Complexe::firstOrCreate(
            ['owner_id' => $admin->id, 'name' => 'Tennis Club de Tunis'],
            [
                'description'        => 'Demo padel complex in Tunis – courts available for booking.',
                'address'            => '20bis Avenue Alain-Savary, Belvédère, Tunis',
                'city'               => 'Tunis',
                'phone'              => '+216 71 785 155',
                'is_active'          => true,
                'email_c'            => 'contact@tct.tn',
                'horaire_c'          => '07:00 - 21:00',
                'latitude_c'         => 36.8189,
                'longitude_c'        => 10.1658,
                'moyenne_notation_c' => 4.8,
                'website_c'          => 'https://tct.tn',
                'facebook_c'         => 'https://www.facebook.com/TennisClubDeTunis',
                'instagram_c'        => 'https://www.instagram.com/tennisclubtunis',
                'image_c'            => 'https://images.pexels.com/photos/1784798/pexels-photo-1784798.jpeg',
                'description_c'      => 'Le plus ancien et prestigieux club de tennis en Tunisie. Courts terre battue et dur, académie jeunes, restaurant gastronomique.',
            ]
        );

        $terrains3 = [
            ['name' => 'Court Tennis A - Terre Battue', 'sport_type' => 'tennis', 'price_per_hour' => 45.00, 'description_t' => 'Court en terre battue homologué ITF, ideal pour perfectionner sa technique', 'capacite_t' => 2, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '21:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/6010286/pexels-photo-6010286.jpeg'],
            ['name' => 'Court Tennis B - Dur', 'sport_type' => 'tennis', 'price_per_hour' => 45.00, 'description_t' => 'Court en surface dure pour matchs competitifs', 'capacite_t' => 2, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '21:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/13425628/pexels-photo-13425628.jpeg'],
            ['name' => 'Court Tennis C - Couvert', 'sport_type' => 'tennis', 'price_per_hour' => 50.00, 'description_t' => 'Court couvert disponible par tous les temps', 'capacite_t' => 2, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '21:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/10926534/pexels-photo-10926534.jpeg'],
            ['name' => 'Court Padel - TCT', 'sport_type' => 'padel', 'price_per_hour' => 50.00, 'description_t' => 'Nouveau court padel dans le cadre exclusif du TCT', 'capacite_t' => 4, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '21:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/35525977/pexels-photo-35525977.jpeg'],
        ];

        foreach ($terrains3 as $terrain) {
            Terrain::firstOrCreate(
                ['complexe_id' => $complexe3->id, 'name' => $terrain['name']],
                [
                    'sport_type'      => $terrain['sport_type'],
                    'price_per_hour'  => $terrain['price_per_hour'],
                    'is_active'       => true,
                    'description_t'   => $terrain['description_t'],
                    'capacite_t'      => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t'         => $terrain['image_t'],
                ]
            );
        }

        // Complexe 4: Padel Marsa
        $complexe4 = Complexe::firstOrCreate(
            ['owner_id' => $admin->id, 'name' => 'Padel Marsa'],
            [
                'description'        => 'Demo padel complex in Tunis – courts available for booking.',
                'address'            => 'Site archeologique de Carthage, La Marsa, Tunis',
                'city'               => 'La Marsa',
                'phone'              => '+216 24 000 050',
                'is_active'          => true,
                'email_c'            => 'contact@padelmarsa.tn',
                'horaire_c'          => '08:00 - 22:00',
                'latitude_c'         => 36.8781,
                'longitude_c'        => 10.3247,
                'moyenne_notation_c' => 4.6,
                'website_c'          => 'https://padellands.com/en/pistas-de-padel/otros-paises/tunisia',
                'facebook_c'         => 'https://www.facebook.com/PadelMarsa',
                'instagram_c'        => 'https://www.instagram.com/padelmarsa',
                'image_c'            => 'https://images.pexels.com/photos/36014317/pexels-photo-36014317.jpeg',
                'description_c'      => 'Club de padel etabli pres du site archeologique de Carthage. 3 courts outdoor avec vue sur mer.',
            ]
        );

        $terrains4 = [
            ['name' => 'Court Padel Vue Mer 1', 'sport_type' => 'padel', 'price_per_hour' => 65.00, 'description_t' => 'Court avec vue imprenable sur la mer et Carthage', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/34079414/pexels-photo-34079414.jpeg'],
            ['name' => 'Court Padel Vue Mer 2', 'sport_type' => 'padel', 'price_per_hour' => 65.00, 'description_t' => 'Ambiance unique entre mer et vestiges antiques', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/32474981/pexels-photo-32474981.jpeg?w=800'],
            ['name' => 'Court Padel Couvert', 'sport_type' => 'padel', 'price_per_hour' => 60.00, 'description_t' => 'Court indoor disponible meme en cas de pluie', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/32897040/pexels-photo-32897040.jpeg?w=800'],
        ];

        foreach ($terrains4 as $terrain) {
            Terrain::firstOrCreate(
                ['complexe_id' => $complexe4->id, 'name' => $terrain['name']],
                [
                    'sport_type'      => $terrain['sport_type'],
                    'price_per_hour'  => $terrain['price_per_hour'],
                    'is_active'       => true,
                    'description_t'   => $terrain['description_t'],
                    'capacite_t'      => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t'         => $terrain['image_t'],
                ]
            );
        }

        // Complexe 5: Sassi Padel Club
        $complexe5 = Complexe::firstOrCreate(
            ['owner_id' => $admin->id, 'name' => 'Sassi Padel Club'],
            [
                'description'        => 'Demo padel complex in Tunis – courts available for booking.',
                'address'            => 'Sidi Hssine, Tunis',
                'city'               => 'Tunis',
                'phone'              => '+216 71 000 006',
                'is_active'          => true,
                'email_c'            => 'contact@sassipadel.tn',
                'horaire_c'          => '09:00 - 22:00',
                'latitude_c'         => 36.8012,
                'longitude_c'        => 10.1123,
                'moyenne_notation_c' => 4.3,
                'website_c'          => 'https://padeltunisia.com',
                'facebook_c'         => 'https://www.facebook.com/SassiPadelClub',
                'instagram_c'        => 'https://www.instagram.com/sassipadel',
                'image_c'            => 'https://images.pexels.com/photos/32897038/pexels-photo-32897038.jpeg?w=800',
                'description_c'      => 'Nouveau club de padel a Sidi Hssine. Ouvert en 2024, installations modernes, tarifs accessibles.',
            ]
        );

        $terrains5 = [
            ['name' => 'Court Padel A', 'sport_type' => 'padel', 'price_per_hour' => 45.00, 'description_t' => 'Court moderne avec surface WPT homologuee', 'capacite_t' => 4, 'heure_ouverture' => '09:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/31012869/pexels-photo-31012869.jpeg?w=800'],
            ['name' => 'Court Padel B', 'sport_type' => 'padel', 'price_per_hour' => 45.00, 'description_t' => 'Court exterieur avec eclairage nocturne', 'capacite_t' => 4, 'heure_ouverture' => '09:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/35248286/pexels-photo-35248286.jpeg?w=800'],
            ['name' => 'Terrain Football 7', 'sport_type' => 'football', 'price_per_hour' => 90.00, 'description_t' => 'Grand terrain football synthetique avec vestiaires', 'capacite_t' => 14, 'heure_ouverture' => '09:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/3621104/pexels-photo-3621104.jpeg'],
        ];

        foreach ($terrains5 as $terrain) {
            Terrain::firstOrCreate(
                ['complexe_id' => $complexe5->id, 'name' => $terrain['name']],
                [
                    'sport_type'      => $terrain['sport_type'],
                    'price_per_hour'  => $terrain['price_per_hour'],
                    'is_active'       => true,
                    'description_t'   => $terrain['description_t'],
                    'capacite_t'      => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t'         => $terrain['image_t'],
                ]
            );
        }

        // Complexe 6: Padel Indoor La Soukra
        $complexe6 = Complexe::firstOrCreate(
            ['owner_id' => $admin->id, 'name' => 'Padel Indoor La Soukra'],
            [
                'description'        => 'Demo padel complex in Tunis – courts available for booking.',
                'address'            => 'La Soukra, Ariana, Tunis',
                'city'               => 'Ariana',
                'phone'              => '+216 24 722 000',
                'is_active'          => true,
                'email_c'            => 'contact@padelsoukra.tn',
                'horaire_c'          => '08:00 - 23:00',
                'latitude_c'         => 36.8756,
                'longitude_c'        => 10.1834,
                'moyenne_notation_c' => 4.5,
                'website_c'          => 'https://padellands.com/en/pistas-de-padel/otros-paises/tunisia',
                'facebook_c'         => 'https://www.facebook.com/PadelIndoorSoukra',
                'instagram_c'        => 'https://www.instagram.com/padelsoukra',
                'image_c'            => 'https://images.pexels.com/photos/35248374/pexels-photo-35248374.jpeg?w=800',
                'description_c'      => 'Premier club de padel 100% indoor en Tunisie. Deux courts entierement couverts, tournois P100 P250 P1000 reguliers.',
            ]
        );

        $terrains6 = [
            ['name' => 'Court Indoor Premium 1', 'sport_type' => 'padel', 'price_per_hour' => 55.00, 'description_t' => 'Court vitre 360 climatise, parfait toute l annee', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/35248501/pexels-photo-35248501.jpeg?w=800'],
            ['name' => 'Court Indoor Premium 2', 'sport_type' => 'padel', 'price_per_hour' => 55.00, 'description_t' => 'Court de competition homologue pour tournois officiels', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => 'https://images.pexels.com/photos/35525977/pexels-photo-35525977.jpeg?w=800'],
        ];

        foreach ($terrains6 as $terrain) {
            Terrain::firstOrCreate(
                ['complexe_id' => $complexe6->id, 'name' => $terrain['name']],
                [
                    'sport_type'      => $terrain['sport_type'],
                    'price_per_hour'  => $terrain['price_per_hour'],
                    'is_active'       => true,
                    'description_t'   => $terrain['description_t'],
                    'capacite_t'      => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t'         => $terrain['image_t'],
                ]
            );
        }
    }
}