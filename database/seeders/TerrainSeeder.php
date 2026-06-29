<?php

namespace Database\Seeders;

use App\Models\Complexe;
use App\Models\Terrain;
use Illuminate\Database\Seeder;

class TerrainSeeder extends Seeder
{
    private array $photos = [];

    public function run(): void
    {
        $this->photos = [
             1 => 'https://images.pexels.com/photos/163409/padel-court-sport-163409.jpeg?auto=compress&cs=tinysrgb&w=600',
             2 => 'https://images.pexels.com/photos/3768916/pexels-photo-3768916.jpeg?auto=compress&cs=tinysrgb&w=600',
             3 => 'https://images.pexels.com/photos/5449616/pexels-photo-5449616.jpeg?auto=compress&cs=tinysrgb&w=600',
             4 => 'https://images.pexels.com/photos/3756925/pexels-photo-3756925.jpeg?auto=compress&cs=tinysrgb&w=600',
             5 => 'https://images.pexels.com/photos/3777905/pexels-photo-3777905.jpeg?auto=compress&cs=tinysrgb&w=600',
             6 => 'https://images.pexels.com/photos/5341635/pexels-photo-5341635.jpeg?auto=compress&cs=tinysrgb&w=600',
             7 => 'https://images.pexels.com/photos/5341647/pexels-photo-5341647.jpeg?auto=compress&cs=tinysrgb&w=600',
             8 => 'https://images.pexels.com/photos/5341660/pexels-photo-5341660.jpeg?auto=compress&cs=tinysrgb&w=600',
             9 => 'https://images.pexels.com/photos/3887985/pexels-photo-3887985.jpeg?auto=compress&cs=tinysrgb&w=600',
             10 => 'https://images.pexels.com/photos/36246829/pexels-photo-36246829.jpeg?auto=compress&cs=tinysrgb&w=600',
             11 => 'https://images.pexels.com/photos/35248400/pexels-photo-35248400.jpeg?auto=compress&cs=tinysrgb&w=600',
             12 => 'https://images.pexels.com/photos/35248254/pexels-photo-35248254.jpeg?auto=compress&cs=tinysrgb&w=600',
             13 => 'https://images.pexels.com/photos/35248259/pexels-photo-35248259.jpeg?auto=compress&cs=tinysrgb&w=600',
             14 => 'https://images.pexels.com/photos/32524250/pexels-photo-32524250.jpeg?auto=compress&cs=tinysrgb&w=600',
             15 => 'https://images.pexels.com/photos/8007409/pexels-photo-8007409.jpeg?auto=compress&cs=tinysrgb&w=600',
             16 => 'https://images.pexels.com/photos/3274370/pexels-photo-3274370.jpeg?auto=compress&cs=tinysrgb&w=600',
             17 => 'https://images.pexels.com/photos/5381950/pexels-photo-5381950.jpeg?auto=compress&cs=tinysrgb&w=600',
             18 => 'https://images.pexels.com/photos/3074529/pexels-photo-3074529.jpeg?auto=compress&cs=tinysrgb&w=600',
             19 => 'https://images.pexels.com/photos/4792269/pexels-photo-4792269.jpeg?auto=compress&cs=tinysrgb&w=600',
             20 => 'https://images.pexels.com/photos/5732609/pexels-photo-5732609.jpeg?auto=compress&cs=tinysrgb&w=600',
             21 => 'https://images.pexels.com/photos/163409/padel-court-sport-163409.jpeg?auto=compress&cs=tinysrgb&w=600',
             22 => 'https://images.pexels.com/photos/3768916/pexels-photo-3768916.jpeg?auto=compress&cs=tinysrgb&w=600',
        ];

        $terrains = [
            'Olympysky Club' => [
                ['name' => 'Court Padel A - Olympysky', 'sport_type' => 'padel', 'price_per_hour' => 55, 'image_t' => $this->photos[1]],
                ['name' => 'Court Padel B - Olympysky', 'sport_type' => 'padel', 'price_per_hour' => 55, 'image_t' => $this->photos[2]],
                ['name' => 'Court Padel C - Olympysky', 'sport_type' => 'padel', 'price_per_hour' => 55, 'image_t' => $this->photos[3]],
                ['name' => 'Terrain Football 5 - Olympysky', 'sport_type' => 'football', 'price_per_hour' => 80, 'image_t' => $this->photos[4]],
                ['name' => 'Terrain Football 7 - Olympysky', 'sport_type' => 'football', 'price_per_hour' => 90, 'image_t' => $this->photos[5]],
            ],
            'Padel House Tunisia' => [
                ['name' => 'Court Padel Indoor 1', 'sport_type' => 'padel', 'price_per_hour' => 60, 'image_t' => $this->photos[6]],
                ['name' => 'Court Padel Indoor 2', 'sport_type' => 'padel', 'price_per_hour' => 60, 'image_t' => $this->photos[7]],
                ['name' => 'Court Padel Indoor 3', 'sport_type' => 'padel', 'price_per_hour' => 60, 'image_t' => $this->photos[8]],
            ],
            'Tennis Club de Tunis' => [
                ['name' => 'Court Tennis A - Terre Battue', 'sport_type' => 'tennis', 'price_per_hour' => 45, 'image_t' => $this->photos[9]],
                ['name' => 'Court Tennis B - Dur', 'sport_type' => 'tennis', 'price_per_hour' => 45, 'image_t' => $this->photos[10]],
                ['name' => 'Court Tennis C', 'sport_type' => 'tennis', 'price_per_hour' => 45, 'image_t' => $this->photos[11]],
                ['name' => 'Court Padel - TCT', 'sport_type' => 'padel', 'price_per_hour' => 50, 'image_t' => $this->photos[12]],
            ],
            'Padel Marsa' => [
                ['name' => 'Court Padel Marsa 1', 'sport_type' => 'padel', 'price_per_hour' => 50, 'image_t' => $this->photos[13]],
                ['name' => 'Court Padel Marsa 2', 'sport_type' => 'padel', 'price_per_hour' => 50, 'image_t' => $this->photos[14]],
                ['name' => 'Court Padel Marsa 3', 'sport_type' => 'padel', 'price_per_hour' => 50, 'image_t' => $this->photos[15]],
            ],
            'Sassi Padel Club' => [
                ['name' => 'Court Sassi 1', 'sport_type' => 'padel', 'price_per_hour' => 48, 'image_t' => $this->photos[16]],
                ['name' => 'Court Sassi 2', 'sport_type' => 'padel', 'price_per_hour' => 48, 'image_t' => $this->photos[17]],
                ['name' => 'Court Padel Sassi 3', 'sport_type' => 'padel', 'price_per_hour' => 48, 'image_t' => $this->photos[18]],
            ],
            'Padel Indoor La Soukra' => [
                ['name' => 'Court Soukra Indoor 1', 'sport_type' => 'padel', 'price_per_hour' => 52, 'image_t' => $this->photos[19]],
                ['name' => 'Court Soukra Indoor 2', 'sport_type' => 'padel', 'price_per_hour' => 52, 'image_t' => $this->photos[20]],
            ],
        ];

        $complexes = Complexe::all()->keyBy('name');
        $total = 0;

        foreach ($terrains as $nomComplexe => $terrainList) {
            if (! $complexes->has($nomComplexe)) {
                $this->command->warn("⚠ Complexe '$nomComplexe' introuvable, skip.");
                continue;
            }
            $cid = $complexes[$nomComplexe]->id;
            foreach ($terrainList as $t) {
                Terrain::updateOrCreate(
                    ['name' => $t['name'], 'complexe_id' => $cid],
                    array_merge($t, ['complexe_id' => $cid, 'is_active' => true])
                );
                $total++;
            }
        }

        $this->command->info("✓ $total terrains créés/mis à jour avec succès.");
    }
}