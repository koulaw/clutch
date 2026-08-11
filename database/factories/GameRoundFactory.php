<?php

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\GameRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameRound>
 */
class GameRoundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_match_id' => GameMatch::factory(),
            'number' => 1,
            'start_tick' => 1_000,
            'freeze_end_tick' => 2_280,
            'end_tick' => 8_680,
            'winner_side' => fake()->randomElement(['ct', 't']),
            'win_reason' => fake()->randomElement(['target_bombed', 'bomb_defused', 'ct_win', 't_win']),
            'team_one_score' => 1,
            'team_two_score' => 0,
        ];
    }
}
