<?php

namespace App\Actions;

use InvalidArgumentException;

class WorkerContract
{
    public const SchemaVersion = '1.0.0';

    /** @var list<string> */
    private const ErrorCodes = [
        'storage_error',
        'checksum_mismatch',
        'unsupported_demo',
        'corrupt_demo',
        'invalid_input',
        'internal_error',
    ];

    /**
     * @return array{
     *     schema_version: string,
     *     source: array{bucket: string, object_key: string, checksum_sha256: ?string},
     *     output: array{directory: string}
     * }
     */
    public function input(string $bucket, string $objectKey, string $outputDirectory, ?string $checksumSha256): array
    {
        $payload = [
            'schema_version' => self::SchemaVersion,
            'source' => [
                'bucket' => $bucket,
                'object_key' => $objectKey,
                'checksum_sha256' => $checksumSha256,
            ],
            'output' => ['directory' => $outputDirectory],
        ];

        $this->assertValid('input', $payload);

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    public function assertValid(string $kind, array $payload): void
    {
        if (! $this->validate($kind, $payload)) {
            throw new InvalidArgumentException("The worker {$kind} payload does not match schema version ".self::SchemaVersion.'.');
        }
    }

    /** @param array<string, mixed> $payload */
    public function validate(string $kind, array $payload): bool
    {
        return match ($kind) {
            'input' => $this->validInput($payload),
            'output' => $this->validOutput($payload),
            'error' => $this->validError($payload),
            default => false,
        };
    }

    /** @param array<string, mixed> $payload */
    private function validInput(array $payload): bool
    {
        if (! $this->hasExactKeys($payload, ['schema_version', 'source', 'output']) || $payload['schema_version'] !== self::SchemaVersion) {
            return false;
        }

        $source = $payload['source'];
        $output = $payload['output'];

        if (! is_array($source) || ! is_array($output)) {
            return false;
        }

        $sourceKeys = array_keys($source);
        sort($sourceKeys);
        if (! in_array($sourceKeys, [['bucket', 'object_key'], ['bucket', 'checksum_sha256', 'object_key']], true)) {
            return false;
        }

        if (! $this->hasExactKeys($output, ['directory'])) {
            return false;
        }

        $checksum = $source['checksum_sha256'] ?? null;

        return $this->isNonEmptyString($source['bucket'])
            && $this->isNonEmptyString($source['object_key'])
            && ($checksum === null || $this->isSha256($checksum))
            && $this->isNonEmptyString($output['directory']);
    }

    /** @param array<string, mixed> $payload */
    private function validOutput(array $payload): bool
    {
        if (! $this->hasExactKeys($payload, ['ok', 'schema_version', 'parser_version', 'output_directory', 'manifest'])) {
            return false;
        }

        if ($payload['ok'] !== true || $payload['schema_version'] !== self::SchemaVersion) {
            return false;
        }

        if (! $this->isNonEmptyString($payload['parser_version']) || ! $this->isNonEmptyString($payload['output_directory'])) {
            return false;
        }

        $manifest = $payload['manifest'];
        if (! is_array($manifest) || ! $this->hasExactKeys($manifest, [
            'schema_version', 'parser_version', 'match', 'rounds', 'players', 'events', 'ticks',
        ])) {
            return false;
        }

        if ($manifest['schema_version'] !== self::SchemaVersion || ! $this->isNonEmptyString($manifest['parser_version'])) {
            return false;
        }

        if (! $this->validJsonArtifact($manifest['match'])) {
            return false;
        }

        foreach (['rounds', 'players', 'ticks'] as $dataset) {
            if (! $this->validTabularArtifact($manifest[$dataset])) {
                return false;
            }
        }

        if (! is_array($manifest['events'])) {
            return false;
        }

        foreach ($manifest['events'] as $name => $artifact) {
            if (! $this->isNonEmptyString($name) || ! $this->validTabularArtifact($artifact)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $payload */
    private function validError(array $payload): bool
    {
        if (! $this->hasExactKeys($payload, ['ok', 'schema_version', 'parser_version', 'error'])) {
            return false;
        }

        if ($payload['ok'] !== false || $payload['schema_version'] !== self::SchemaVersion || ! $this->isNonEmptyString($payload['parser_version'])) {
            return false;
        }

        $error = $payload['error'];

        return is_array($error)
            && $this->hasExactKeys($error, ['code', 'message', 'retryable', 'details'])
            && in_array($error['code'], self::ErrorCodes, true)
            && $this->isNonEmptyString($error['message'])
            && is_bool($error['retryable'])
            && is_array($error['details']);
    }

    private function validJsonArtifact(mixed $artifact): bool
    {
        return is_array($artifact)
            && $this->hasExactKeys($artifact, ['path'])
            && $this->isNonEmptyString($artifact['path']);
    }

    private function validTabularArtifact(mixed $artifact): bool
    {
        return is_array($artifact)
            && $this->hasExactKeys($artifact, ['path', 'rows'])
            && $this->isNonEmptyString($artifact['path'])
            && is_int($artifact['rows'])
            && $artifact['rows'] >= 0;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $expectedKeys
     */
    private function hasExactKeys(array $payload, array $expectedKeys): bool
    {
        $keys = array_keys($payload);
        sort($keys);
        sort($expectedKeys);

        return $keys === $expectedKeys;
    }

    private function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }

    private function isSha256(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[a-f0-9]{64}\z/', $value) === 1;
    }
}
