<?php

namespace App;

enum AnalysisStatus: string
{
    case Uploaded = 'uploaded';
    case Queued = 'queued';
    case Parsing = 'parsing';
    case Analyzing = 'analyzing';
    case Ready = 'ready';
    case Failed = 'failed';
    case Unsupported = 'unsupported';

    public function progress(): int
    {
        return match ($this) {
            self::Uploaded => 0,
            self::Queued => 10,
            self::Parsing => 35,
            self::Analyzing => 75,
            self::Ready, self::Failed, self::Unsupported => 100,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Ready, self::Failed, self::Unsupported], true);
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Uploaded => in_array($status, [self::Queued, self::Failed, self::Unsupported], true),
            self::Queued => in_array($status, [self::Parsing, self::Failed, self::Unsupported], true),
            self::Parsing => in_array($status, [self::Analyzing, self::Failed, self::Unsupported], true),
            self::Analyzing => in_array($status, [self::Ready, self::Failed, self::Unsupported], true),
            self::Failed => $status === self::Queued,
            self::Ready, self::Unsupported => false,
        };
    }
}
