<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserQuota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserQuota>
 */
class UserQuotaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'daily_imports' => 0,
            'imports_on' => null,
            'stored_analyses' => 0,
        ];
    }
}
