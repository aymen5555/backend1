<?php

namespace Database\Seeders;

use App\Models\Complexe;
use Illuminate\Database\Seeder;

class ComplexeSeeder extends Seeder
{
    public function run(): void
    {
        $photos = [
            'Olympysky Club' => 'https://images.pexels.com/photos/5449616/pexels-photo-5449616.jpeg?auto=compress&cs=tinysrgb&w=600',
            'Padel House Tunisia' => 'https://images.pexels.com/photos/3756925/pexels-photo-3756925.jpeg?auto=compress&cs=tinysrgb&w=600',
            'Tennis Club de Tunis' => 'https://images.pexels.com/photos/3777905/pexels-photo-3777905.jpeg?auto=compress&cs=tinysrgb&w=600',
            'Padel Marsa' => 'https://images.pexels.com/photos/5341635/pexels-photo-5341635.jpeg?auto=compress&cs=tinysrgb&w=600',
            'Sassi Padel Club' => 'https://images.pexels.com/photos/5341647/pexels-photo-5341647.jpeg?auto=compress&cs=tinysrgb&w=600',
            'Padel Indoor La Soukra' => 'https://images.pexels.com/photos/5341660/pexels-photo-5341660.jpeg?auto=compress&cs=tinysrgb&w=600',
        ];

        $complexes = [
            [
                'name' => 'Olympysky Club',
                'description' => 'Complexe sportif haut de gamme au cœur des Berges du Lac. Padel, football et bien plus.',
                'address' => 'Avenue Beji Caid Essebsi, Les Berges du Lac 2',
                'city' => 'Tunis',
                'phone' => '+216 29 623 010',
                'email_c' => 'contact@olympysky.tn',
                'image_c' => 'https://images.pexels.com/photos/36293742/pexels-photo-36293742.jpeg?auto=compress&cs=tinysrgb&w=800',
                'website_c' => 'https://olympysky.tn',
                'facebook_c' => 'https://facebook.com/olympyskyclub',
                'instagram_c' => 'https://instagram.com/olympyskyclub',
                'is_active' => true,
            ],
            [
                'name' => 'Padel House Tunisia',
                'description' => 'Spécialiste du padel en Tunisie. Terrains couverts climatisés, coaching professionnel.',
                'address' => '676 Sidi Amor',
                'city' => 'Ariana',
                'phone' => '+216 71 234 567',
                'email_c' => 'contact@padelhouse.tn',
                'image_c' => 'https://images.pexels.com/photos/12427044/pexels-photo-12427044.jpeg?auto=compress&cs=tinysrgb&w=800',
                'website_c' => 'https://padelhouse.tn',
                'instagram_c' => 'https://instagram.com/padelhousetunisia',
                'is_active' => true,
            ],
            [
                'name' => 'Tennis Club de Tunis',
                'description' => 'Le club de tennis historique de Tunis. Courts terre battue et dur, padel, café-restaurant.',
                'address' => '20bis Avenue Alain-Savary, Belvédère',
                'city' => 'Tunis',
                'phone' => '+216 71 890 123',
                'email_c' => 'info@tct.tn',
                'image_c' => 'https://images.pexels.com/photos/36293741/pexels-photo-36293741.jpeg?auto=compress&cs=tinysrgb&w=800',
                'website_c' => 'https://tct.tn',
                'facebook_c' => 'https://facebook.com/tennisclubdetunis',
                'instagram_c' => 'https://instagram.com/tennisclubtunis',
                'is_active' => true,
            ],
            [
                'name' => 'Padel Marsa',
                'description' => 'Complexe padel vue mer à La Marsa. Ambiance conviviale, terrains éclairés pour jouer le soir.',
                'address' => 'Avenue Tahar Haddad, La Marsa',
                'city' => 'La Marsa',
                'phone' => '+216 71 745 678',
                'email_c' => 'contact@padelmarsa.tn',
                'image_c' => 'https://images.pexels.com/photos/30747154/pexels-photo-30747154.jpeg?auto=compress&cs=tinysrgb&w=800',
                'website_c' => 'https://padelmarsa.tn',
                'instagram_c' => 'https://instagram.com/padelmarsa',
                'is_active' => true,
            ],
            [
                'name' => 'Sassi Padel Club',
                'description' => 'Club padel moderne au Lac 1. 4 terrains couverts, vestiaires, pro shop.',
                'address' => 'Rue du Lac Windermere, Les Berges du Lac 1',
                'city' => 'Tunis',
                'phone' => '+216 71 960 234',
                'email_c' => 'contact@sassipadelclub.tn',
                'image_c' => 'https://images.pexels.com/photos/917503/pexels-photo-917503.jpeg?auto=compress&cs=tinysrgb&w=800',
                'website_c' => 'https://sassipadelclub.tn',
                'instagram_c' => 'https://instagram.com/sassipadelclub',
                'is_active' => true,
            ],
            [
                'name' => 'Padel Indoor La Soukra',
                'description' => 'Complexe indoor entièrement climatisé. Idéal pour jouer toute l\'année quelle que soit la météo.',
                'address' => 'Route de La Soukra Km 4',
                'city' => 'Ariana',
                'phone' => '+216 71 866 789',
                'email_c' => 'contact@padelsoukra.tn',
                'image_c' => 'https://images.pexels.com/photos/33977832/pexels-photo-33977832.jpeg?auto=compress&cs=tinysrgb&w=800',
                'website_c' => 'https://padelsoukra.tn',
                'instagram_c' => 'https://instagram.com/padelsoukra',
                'is_active' => true,
            ],
        ];

        foreach ($complexes as $data) {
            $c = Complexe::where('name', $data['name'])->first();
            if ($c) {
                $c->update($data);
            } else {
                Complexe::create(array_merge($data, ['owner_id' => null]));
            }
        }

        $this->command->info('✓ 6 complexes mis à jour/créés avec succès.');
    }
}