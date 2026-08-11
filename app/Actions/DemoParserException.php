<?php

namespace App\Actions;

use RuntimeException;
use Throwable;

class DemoParserException extends RuntimeException
{
    /** @param array<string, mixed> $context */
    public static function fromWorker(string $code, string $message, array $context): self
    {
        $exception = new self($message);
        $exception->workerCode = $code;
        $exception->workerContext = $context;

        return $exception;
    }

    public static function fromThrowable(Throwable $throwable): self
    {
        $exception = new self('The demo parser process failed.', previous: $throwable);
        $exception->workerCode = 'parser_process_failed';
        $exception->workerContext = ['exception' => $throwable::class];

        return $exception;
    }

    public string $workerCode = 'parser_failed';

    /** @var array<string, mixed> */
    public array $workerContext = [];
}
