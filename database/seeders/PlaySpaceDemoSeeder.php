<?php

namespace Database\Seeders;

use App\Models\Complexe;
use App\Models\Terrain;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlaySpaceDemoSeeder extends Seeder
{
    private array $photos = [];

    public function run(): void
    {
        $this->photos = [
            1 => 'https://images.pexels.com/photos/8007409/pexels-photo-8007409.jpeg?auto=compress&cs=tinysrgb&w=600',
            2 => 'https://images.pexels.com/photos/3274370/pexels-photo-3274370.jpeg?auto=compress&cs=tinysrgb&w=600',
            3 => 'https://images.pexels.com/photos/5381950/pexels-photo-5381950.jpeg?auto=compress&cs=tinysrgb&w=600',
            4 => 'https://images.pexels.com/photos/3074529/pexels-photo-3074529.jpeg?auto=compress&cs=tinysrgb&w=600',
            5 => 'https://images.pexels.com/photos/4792269/pexels-photo-4792269.jpeg?auto=compress&cs=tinysrgb&w=600',
            6 => 'https://images.pexels.com/photos/5732609/pexels-photo-5732609.jpeg?auto=compress&cs=tinysrgb&w=600',
            7 => 'https://images.pexels.com/photos/163409/padel-court-sport-163409.jpeg?auto=compress&cs=tinysrgb&w=600',
            8 => 'https://images.pexels.com/photos/3768916/pexels-photo-3768916.jpeg?auto=compress&cs=tinysrgb&w=600',
            9 => 'https://images.pexels.com/photos/5449616/pexels-photo-5449616.jpeg?auto=compress&cs=tinysrgb&w=600',
            10 => 'https://images.pexels.com/photos/3756925/pexels-photo-3756925.jpeg?auto=compress&cs=tinysrgb&w=600',
            11 => 'https://images.pexels.com/photos/3777905/pexels-photo-3777905.jpeg?auto=compress&cs=tinysrgb&w=600',
            12 => 'https://images.pexels.com/photos/5341635/pexels-photo-5341635.jpeg?auto=compress&cs=tinysrgb&w=600',
            13 => 'https://images.pexels.com/photos/5341647/pexels-photo-5341647.jpeg?auto=compress&cs=tinysrgb&w=600',
            14 => 'https://images.pexels.com/photos/5341660/pexels-photo-5341660.jpeg?auto=compress&cs=tinysrgb&w=600',
            15 => 'https://images.pexels.com/photos/3887985/pexels-photo-3887985.jpeg?auto=compress&cs=tinysrgb&w=600',
            16 => 'https://images.pexels.com/photos/36246829/pexels-photo-36246829.jpeg?auto=compress&cs=tinysrgb&w=600',
            17 => 'https://images.pexels.com/photos/35248400/pexels-photo-35248400.jpeg?auto=compress&cs=tinysrgb&w=600',
            18 => 'https://images.pexels.com/photos/35248254/pexels-photo-35248254.jpeg?auto=compress&cs=tinysrgb&w=600',
            19 => 'https://images.pexels.com/photos/35248259/pexels-photo-35248259.jpeg?auto=compress&cs=tinysrgb&w=600',
            20 => 'https://images.pexels.com/photos/32524250/pexels-photo-32524250.jpeg?auto=compress&cs=tinysrgb&w=600',
            21 => 'https://images.pexels.com/photos/8007409/pexels-photo-8007409.jpeg?auto=compress&cs=tinysrgb&w=600',
            22 => 'https://images.pexels.com/photos/3274370/pexels-photo-3274370.jpeg?auto=compress&cs=tinysrgb&w=600',
            23 => 'https://images.pexels.com/photos/5381950/pexels-photo-5381950.jpeg?auto=compress&cs=tinysrgb&w=600',
            24 => 'https://images.pexels.com/photos/3074529/pexels-photo-3074529.jpeg?auto=compress&cs=tinysrgb&w=600',
            25 => 'https://images.pexels.com/photos/4792269/pexels-photo-4792269.jpeg?auto=compress&cs=tinysrgb&w=600',
            26 => 'https://images.pexels.com/photos/5732609/pexels-photo-5732609.jpeg?auto=compress&cs=tinysrgb&w=600',
        ];

        $admin = User::where('email', 'aymencharf55@gmail.com')->first();

        if (! $admin) {
            return;
        }

        $complexe1 = Complexe::updateOrCreate(
            ['name' => 'Olympysky Club'],
            [
                'owner_id' => $admin->id,
                'description' => 'Demo padel complex in Tunis – courts available for booking.',
                'address' => 'Avenue Beji Caid Essebsi, Les Berges du Lac 2, Tunis',
                'city' => 'Tunis',
                'phone' => '+216 29 623 010',
                'is_active' => true,
                'email_c' => 'gym@olympysky.com',
                'horaire_c' => '07:00 - 23:00',
                'latitude_c' => 36.8378,
                'longitude_c' => 10.2306,
                'moyenne_notation_c' => 4.7,
                'website_c' => 'https://olympysky.com',
                'facebook_c' => 'https://www.facebook.com/olympyskygym',
                'instagram_c' => 'https://www.instagram.com/olympysky_club',
                'image_c' => $this->photos[1],
                'description_c' => 'Tunisia\'s ultimate sports hub. Padel, Football, Gym, Indoor Pool, Spa, Coffee Corner, Kids Area.',
            ]
        );

        $terrains1 = [
            ['name' => 'Court Padel A - Olympysky', 'sport_type' => 'padel', 'price_per_hour' => 55.00, 'description_t' => 'Court de padel professionnel en plein air avec eclairage LED', 'capacite_t' => 4, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[7]],
            ['name' => 'Court Padel B - Olympysky', 'sport_type' => 'padel', 'price_per_hour' => 55.00, 'description_t' => 'Court couvert climatise, ideal pour toute saison', 'capacite_t' => 4, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[8]],
            ['name' => 'Court Padel C - Olympysky', 'sport_type' => 'padel', 'price_per_hour' => 55.00, 'description_t' => 'Court panoramique avec vue sur le lac', 'capacite_t' => 4, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[9]],
            ['name' => 'Terrain Football 5 - Olympysky', 'sport_type' => 'football', 'price_per_hour' => 80.00, 'description_t' => 'Terrain de football en gazon synthetique derniere generation', 'capacite_t' => 10, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[10]],
            ['name' => 'Terrain Football 7 - Olympysky', 'sport_type' => 'football', 'price_per_hour' => 90.00, 'description_t' => 'Grand terrain gazon synthetique pour matchs officiels', 'capacite_t' => 14, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[11]],
        ];

        foreach ($terrains1 as $terrain) {
            Terrain::updateOrCreate(
                ['complexe_id' => $complexe1->id, 'name' => $terrain['name']],
                [
                    'sport_type' => $terrain['sport_type'],
                    'price_per_hour' => $terrain['price_per_hour'],
                    'is_active' => true,
                    'description_t' => $terrain['description_t'],
                    'capacite_t' => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t' => $terrain['image_t'],
                ]
            );
        }

        $complexe2 = Complexe::updateOrCreate(
            ['name' => 'Padel House Tunisia'],
            [
                'owner_id' => $admin->id,
                'description' => 'Demo padel complex in Tunis – courts available for booking.',
                'address' => '676 Sidi Amor, Ariana, Tunis',
                'city' => 'Ariana',
                'phone' => '+216 22 383 383',
                'is_active' => true,
                'email_c' => 'contact@padelhouse.tn',
                'horaire_c' => '08:00 - 22:00',
                'latitude_c' => 36.8625,
                'longitude_c' => 10.1956,
                'moyenne_notation_c' => 4.5,
                'website_c' => 'https://padellands.com/en/pistas-de-padel/otros-paises/tunisia',
                'facebook_c' => 'https://www.facebook.com/PadelHouseTunisia',
                'instagram_c' => 'https://www.instagram.com/padelhousetn',
                'image_c' => $this->photos[2],
                'description_c' => 'Premier club de padel indoor en Ariana. 3 courts couverts climatisés, vestiaires modernes.',
            ]
        );

        $terrains2 = [
            ['name' => 'Court Padel Indoor 1', 'sport_type' => 'padel', 'price_per_hour' => 60.00, 'description_t' => 'Court indoor premium avec murs en verre trempé', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[12]],
            ['name' => 'Court Padel Indoor 2', 'sport_type' => 'padel', 'price_per_hour' => 60.00, 'description_t' => 'Court climatisé avec système d eclairage LED professionnel', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[13]],
            ['name' => 'Court Padel Indoor 3', 'sport_type' => 'padel', 'price_per_hour' => 65.00, 'description_t' => 'Court VIP avec vue panoramique sur le club', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[14]],
        ];

        foreach ($terrains2 as $terrain) {
            Terrain::updateOrCreate(
                ['complexe_id' => $complexe2->id, 'name' => $terrain['name']],
                [
                    'sport_type' => $terrain['sport_type'],
                    'price_per_hour' => $terrain['price_per_hour'],
                    'is_active' => true,
                    'description_t' => $terrain['description_t'],
                    'capacite_t' => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t' => $terrain['image_t'],
                ]
            );
        }

        $complexe3 = Complexe::updateOrCreate(
            ['name' => 'Tennis Club de Tunis'],
            [
                'owner_id' => $admin->id,
                'description' => 'Demo padel complex in Tunis – courts available for booking.',
                'address' => '20bis Avenue Alain-Savary, Belvédère, Tunis',
                'city' => 'Tunis',
                'phone' => '+216 71 785 155',
                'is_active' => true,
                'email_c' => 'contact@tct.tn',
                'horaire_c' => '07:00 - 21:00',
                'latitude_c' => 36.8189,
                'longitude_c' => 10.1658,
                'moyenne_notation_c' => 4.8,
                'website_c' => 'https://tct.tn',
                'facebook_c' => 'https://www.facebook.com/TennisClubDeTunis',
                'instagram_c' => 'https://www.instagram.com/tennisclubtunis',
                'image_c' => $this->photos[3],
                'description_c' => 'Le plus ancien et prestigieux club de tennis en Tunisie. Courts terre battue et dur.',
            ]
        );

        $terrains3 = [
            ['name' => 'Court Tennis A - Terre Battue', 'sport_type' => 'tennis', 'price_per_hour' => 45.00, 'description_t' => 'Court en terre battue homologué ITF, ideal pour perfectionner sa technique', 'capacite_t' => 2, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '21:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[15]],
            ['name' => 'Court Tennis B - Dur', 'sport_type' => 'tennis', 'price_per_hour' => 45.00, 'description_t' => 'Court en surface dure pour matchs competitifs', 'capacite_t' => 2, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '21:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[16]],
            ['name' => 'Court Tennis C - Couvert', 'sport_type' => 'tennis', 'price_per_hour' => 50.00, 'description_t' => 'Court couvert disponible par tous les temps', 'capacite_t' => 2, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '21:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[17]],
            ['name' => 'Court Padel - TCT', 'sport_type' => 'padel', 'price_per_hour' => 50.00, 'description_t' => 'Nouveau court padel dans le cadre exclusif du TCT', 'capacite_t' => 4, 'heure_ouverture' => '07:00:00', 'heure_fermeture' => '21:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[18]],
        ];

        foreach ($terrains3 as $terrain) {
            Terrain::updateOrCreate(
                ['complexe_id' => $complexe3->id, 'name' => $terrain['name']],
                [
                    'sport_type' => $terrain['sport_type'],
                    'price_per_hour' => $terrain['price_per_hour'],
                    'is_active' => true,
                    'description_t' => $terrain['description_t'],
                    'capacite_t' => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t' => $terrain['image_t'],
                ]
            );
        }

        $complexe4 = Complexe::updateOrCreate(
            ['name' => 'Padel Marsa'],
            [
                'owner_id' => $admin->id,
                'description' => 'Demo padel complex in Tunis – courts available for booking.',
                'address' => 'Site archeologique de Carthage, La Marsa, Tunis',
                'city' => 'La Marsa',
                'phone' => '+216 24 000 050',
                'is_active' => true,
                'email_c' => 'contact@padelmarsa.tn',
                'horaire_c' => '08:00 - 22:00',
                'latitude_c' => 36.8781,
                'longitude_c' => 10.3247,
                'moyenne_notation_c' => 4.6,
                'website_c' => 'https://padellands.com/en/pistas-de-padel/otros-paises/tunisia',
                'facebook_c' => 'https://www.facebook.com/PadelMarsa',
                'instagram_c' => 'https://www.instagram.com/padelmarsa',
                'image_c' => $this->photos[4],
                'description_c' => 'Club de padel etabli pres du site archeologique de Carthage. 3 courts outdoor.',
            ]
        );

        $terrains4 = [
            ['name' => 'Court Padel Vue Mer 1', 'sport_type' => 'padel', 'price_per_hour' => 65.00, 'description_t' => 'Court avec vue imprenable sur la mer et Carthage', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[19]],
            ['name' => 'Court Padel Vue Mer 2', 'sport_type' => 'padel', 'price_per_hour' => 65.00, 'description_t' => 'Ambiance unique entre mer et vestiges antiques', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[20]],
            ['name' => 'Court Padel Couvert', 'sport_type' => 'padel', 'price_per_hour' => 60.00, 'description_t' => 'Court indoor disponible meme en cas de pluie', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[21]],
        ];

        foreach ($terrains4 as $terrain) {
            Terrain::updateOrCreate(
                ['complexe_id' => $complexe4->id, 'name' => $terrain['name']],
                [
                    'sport_type' => $terrain['sport_type'],
                    'price_per_hour' => $terrain['price_per_hour'],
                    'is_active' => true,
                    'description_t' => $terrain['description_t'],
                    'capacite_t' => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t' => $terrain['image_t'],
                ]
            );
        }

        $complexe5 = Complexe::updateOrCreate(
            ['name' => 'Sassi Padel Club'],
            [
                'owner_id' => $admin->id,
                'description' => 'Demo padel complex in Tunis – courts available for booking.',
                'address' => 'Sidi Hssine, Tunis',
                'city' => 'Tunis',
                'phone' => '+216 71 000 006',
                'is_active' => true,
                'email_c' => 'contact@sassipadel.tn',
                'horaire_c' => '09:00 - 22:00',
                'latitude_c' => 36.8012,
                'longitude_c' => 10.1123,
                'moyenne_notation_c' => 4.3,
                'website_c' => 'https://padeltunisia.com',
                'facebook_c' => 'https://www.facebook.com/SassiPadelClub',
                'instagram_c' => 'https://www.instagram.com/sassipadel',
                'image_c' => $this->photos[5],
                'description_c' => 'Nouveau club de padel a Sidi Hssine. Ouvert en 2024, installations modernes.',
            ]
        );

        $terrains5 = [
            ['name' => 'Court Padel A', 'sport_type' => 'padel', 'price_per_hour' => 45.00, 'description_t' => 'Court moderne avec surface WPT homologuee', 'capacite_t' => 4, 'heure_ouverture' => '09:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[22]],
            ['name' => 'Court Padel B', 'sport_type' => 'padel', 'price_per_hour' => 45.00, 'description_t' => 'Court exterieur avec eclairage nocturne', 'capacite_t' => 4, 'heure_ouverture' => '09:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[23]],
            ['name' => 'Terrain Football 7', 'sport_type' => 'football', 'price_per_hour' => 90.00, 'description_t' => 'Grand terrain football synthetique avec vestiaires', 'capacite_t' => 14, 'heure_ouverture' => '09:00:00', 'heure_fermeture' => '22:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[24]],
        ];

        foreach ($terrains5 as $terrain) {
            Terrain::updateOrCreate(
                ['complexe_id' => $complexe5->id, 'name' => $terrain['name']],
                [
                    'sport_type' => $terrain['sport_type'],
                    'price_per_hour' => $terrain['price_per_hour'],
                    'is_active' => true,
                    'description_t' => $terrain['description_t'],
                    'capacite_t' => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t' => $terrain['image_t'],
                ]
            );
        }

        $complexe6 = Complexe::updateOrCreate(
            ['name' => 'Padel Indoor La Soukra'],
            [
                'owner_id' => $admin->id,
                'description' => 'Demo padel complex in Tunis – courts available for booking.',
                'address' => 'La Soukra, Ariana, Tunis',
                'city' => 'Ariana',
                'phone' => '+216 24 722 000',
                'is_active' => true,
                'email_c' => 'contact@padelsoukra.tn',
                'horaire_c' => '08:00 - 23:00',
                'latitude_c' => 36.8756,
                'longitude_c' => 10.1834,
                'moyenne_notation_c' => 4.5,
                'website_c' => 'https://padellands.com/en/pistas-de-padel/otros-paises/tunisia',
                'facebook_c' => 'https://www.facebook.com/PadelIndoorSoukra',
                'instagram_c' => 'https://www.instagram.com/padelsoukra',
                'image_c' => $this->photos[6],
                'description_c' => 'Premier club de padel 100% indoor en Tunisie. Deux courts entierement couverts.',
            ]
        );

        $terrains6 = [
            ['name' => 'Court Indoor Premium 1', 'sport_type' => 'padel', 'price_per_hour' => 55.00, 'description_t' => 'Court vitre 360 climatise, parfait toute l annee', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[25]],
            ['name' => 'Court Indoor Premium 2', 'sport_type' => 'padel', 'price_per_hour' => 55.00, 'description_t' => 'Court de competition homologue pour tournois officiels', 'capacite_t' => 4, 'heure_ouverture' => '08:00:00', 'heure_fermeture' => '23:00:00', 'nbheures_seance' => 1, 'nbminute_seance' => 0, 'image_t' => $this->photos[26]],
        ];

        foreach ($terrains6 as $terrain) {
            Terrain::updateOrCreate(
                ['complexe_id' => $complexe6->id, 'name' => $terrain['name']],
                [
                    'sport_type' => $terrain['sport_type'],
                    'price_per_hour' => $terrain['price_per_hour'],
                    'is_active' => true,
                    'description_t' => $terrain['description_t'],
                    'capacite_t' => $terrain['capacite_t'],
                    'heure_ouverture' => $terrain['heure_ouverture'],
                    'heure_fermeture' => $terrain['heure_fermeture'],
                    'nbheures_seance' => $terrain['nbheures_seance'],
                    'nbminute_seance' => $terrain['nbminute_seance'],
                    'image_t' => $terrain['image_t'],
                ]
            );
        }
    }
}