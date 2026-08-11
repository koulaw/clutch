<?php

namespace Database\Factories;

use App\Models\MapRadar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapRadar>
 */
class MapRadarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'map_name' => 'de_mirage',
            'version' => '17595823',
            'network_protocols' => [14011],
            'image_path' => 'maps/de_mirage/17595823/radar.png',
            'image_width' => 1024,
            'image_height' => 1024,
            'checksum_sha256' => 'af139a92cf929214f7c0ac35e2d8c82bad385d83e71eeaf7b955dac96467a136',
            'coordinate_transform' => [
                'pos_x' => -3230.0,
                'pos_y' => 1713.0,
                'scale' => 5.0,
                'rotate' => 0,
                'lower_level_max_units' => -1000000.0,
            ],
        ];
    }
}
