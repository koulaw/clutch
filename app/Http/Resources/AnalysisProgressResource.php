<?php

namespace App\Http\Resources;

use App\AnalysisStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var AnalysisStatus $status */
        $status = $this->status;

        return [
            'id' => $this->id,
            'demo_id' => $this->demo->public_id,
            'attempt' => $this->attempt,
            'status' => $status->value,
            'progress' => $status->progress(),
            'is_terminal' => $status->isTerminal(),
            'can_retry' => in_array($status, [AnalysisStatus::Failed, AnalysisStatus::Unsupported], true),
            'error' => $this->errorPayload($status),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /** @return array{code: string, message: string, retryable: bool}|null */
    private function errorPayload(AnalysisStatus $status): ?array
    {
        if (! in_array($status, [AnalysisStatus::Failed, AnalysisStatus::Unsupported], true)) {
            return null;
        }

        return [
            'code' => $this->error_code ?? 'analysis_failed',
            'message' => $status === AnalysisStatus::Unsupported
                ? 'This demo map or game version is not supported.'
                : 'The analysis could not be completed. You can try again.',
            'retryable' => true,
        ];
    }
}
