<?php

namespace Database\Factories;

use App\Models\Analysis;
use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMatch>
 */
class GameMatchFactory extends Factory
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
            'external_id' => fake()->uuid(),
            'map_name' => fake()->randomElement(['de_ancient', 'de_dust2', 'de_inferno', 'de_mirage', 'de_nuke']),
            'started_at' => fake()->dateTimeBetween('-1 year'),
            'duration_ms' => fake()->numberBetween(1_200_000, 4_500_000),
            'tick_rate' => '64.000',
            'team_one_name' => fake()->company(),
            'team_two_name' => fake()->company(),
            'team_one_score' => fake()->numberBetween(0, 16),
            'team_two_score' => fake()->numberBetween(0, 16),
            'winner_side' => fake()->randomElement(['ct', 't']),
            'metadata' => [],
        ];
    }
}
