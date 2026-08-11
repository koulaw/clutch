<?php

namespace Database\Factories;

use App\AnalysisStatus;
use App\Models\Demo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Demo>
 */
class DemoFactory extends Factory
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
            'public_id' => (string) Str::ulid(),
            'storage_disk' => 's3',
            'storage_path' => 'demos/'.Str::ulid().'.dem',
            'checksum_sha256' => hash('sha256', fake()->uuid()),
            'size_bytes' => fake()->numberBetween(1_000_000, 500_000_000),
            'status' => AnalysisStatus::Uploaded,
            'uploaded_at' => now(),
        ];
    }

    public function status(AnalysisStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
