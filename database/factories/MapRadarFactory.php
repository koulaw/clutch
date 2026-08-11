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
            'version' => '84adbb9dca5a',
            'patch_versions' => [14174],
            'image_path' => 'maps/de_mirage/84adbb9dca5a/radar.png',
            'image_width' => 1024,
            'image_height' => 1024,
            'checksum_sha256' => '5de1cc16362e538dc5f4561a24661cb1fb1c15c77ec289e32b44c0fc07bfb85b',
            'coordinate_transform' => [
                'pos_x' => -3230.0,
                'pos_y' => 1713.0,
                'scale' => 5.0,
                'rotate' => 0,
            ],
            'image_layers' => [
                'default' => [
                    'image_path' => 'maps/de_mirage/84adbb9dca5a/radar.png',
                    'width' => 1024,
                    'height' => 1024,
                    'checksum_sha256' => '5de1cc16362e538dc5f4561a24661cb1fb1c15c77ec289e32b44c0fc07bfb85b',
                    'altitude_min' => -1000000.0,
                    'altitude_max' => 1000000.0,
                ],
            ],
        ];
    }
}
