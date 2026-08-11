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
        app(ResolveMapRadar::class)->handle([
            'map_name' => 'de_mirage',
            'network_protocol' => 14011,
        ]);
    }
}
