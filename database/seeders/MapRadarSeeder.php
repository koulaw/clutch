<?php

namespace Database\Seeders;

use App\Actions\ResolveMapRadar;
use Illuminate\Database\Seeder;

class MapRadarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('map_radars.maps') as $mapName => $versions) {
            $definition = $versions[array_key_first($versions)];

            app(ResolveMapRadar::class)->handle([
                'map_name' => $mapName,
                'patch_version' => $definition['patch_versions'][0],
            ]);
        }
    }
}
