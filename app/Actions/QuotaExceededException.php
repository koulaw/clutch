<?php

namespace App\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class QuotaExceededException extends RuntimeException
{
    public function __construct(
        public readonly string $quota,
        public readonly int $limit,
    ) {
        $message = match ($quota) {
            'daily_imports' => "The daily import limit of {$limit} has been reached.",
            'stored_analyses' => "The stored analysis limit of {$limit} has been reached.",
            default => "The {$quota} limit of {$limit} has been reached.",
        };

        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse|false
    {
        if (! $request->is('api/*')) {
            return false;
        }

        return response()->json([
            'message' => $this->getMessage(),
            'code' => 'quota_exceeded',
            'quota' => $this->quota,
            'limit' => $this->limit,
        ], 429);
    }
}
