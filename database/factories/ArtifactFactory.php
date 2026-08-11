<?php

namespace Database\Factories;

use App\Models\Analysis;
use App\Models\Artifact;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Artifact>
 */
class ArtifactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analysis_id' => Analysis::factory(),
            'game_round_id' => null,
            'type' => fake()->randomElement(['replay', 'analytics', 'report']),
            'storage_disk' => 's3',
            'storage_path' => 'artifacts/'.Str::ulid().'.json.gz',
            'size_bytes' => fake()->numberBetween(1_000, 50_000_000),
            'checksum_sha256' => hash('sha256', fake()->uuid()),
            'version' => '1.0.0',
            'metadata' => [],
        ];
    }
}
