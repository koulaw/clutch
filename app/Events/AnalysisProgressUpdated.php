<?php

namespace App\Events;

use App\Http\Resources\AnalysisProgressResource;
use App\Models\Analysis;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class AnalysisProgressUpdated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public int $userId;

    /** @var array<string, mixed> */
    public array $analysis;

    public function __construct(Analysis $analysis)
    {
        $analysis->loadMissing('demo');
        $this->userId = $analysis->demo->user_id;
        $this->analysis = AnalysisProgressResource::make($analysis)->resolve();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("users.{$this->userId}.analyses");
    }

    public function broadcastAs(): string
    {
        return 'analysis.progress.updated';
    }

    /** @return array{analysis: array<string, mixed>} */
    public function broadcastWith(): array
    {
        return ['analysis' => $this->analysis];
    }
}
