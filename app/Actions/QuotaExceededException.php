<?php

namespace App\Actions;

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
}
