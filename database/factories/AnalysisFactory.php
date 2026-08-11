<?php

namespace Database\Factories;

use App\AnalysisStatus;
use App\Models\Analysis;
use App\Models\Demo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Analysis>
 */
class AnalysisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'demo_id' => Demo::factory(),
            'attempt' => 1,
            'status' => AnalysisStatus::Queued,
            'schema_version' => '1.0.0',
            'parser_version' => '1.0.0',
        ];
    }

    public function status(AnalysisStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => AnalysisStatus::Failed,
            'failed_at' => now(),
            'error_code' => 'parser_failed',
            'error_message' => 'The demo could not be parsed.',
            'error_context' => ['stage' => 'parsing'],
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (): array => [
            'status' => AnalysisStatus::Ready,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
    }
}
