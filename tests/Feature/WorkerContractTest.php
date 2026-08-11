<?php

use App\Actions\WorkerContract;

it('validates every shared contract fixture on the Laravel side', function () {
    $cases = json_decode(
        file_get_contents(base_path('worker/contracts/fixtures/cases.json')),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    $contract = new WorkerContract;

    foreach ($cases as $case) {
        expect($contract->validate($case['kind'], $case['payload']))
            ->toBe($case['valid'], $case['name']);
    }
});

it('builds a valid worker input payload', function () {
    $contract = new WorkerContract;

    $payload = $contract->input(
        bucket: 'clutch',
        objectKey: 'demos/1/reference.dem.zst',
        outputDirectory: '/work/output',
        checksumSha256: str_repeat('a', 64),
    );

    expect($payload['schema_version'])->toBe(WorkerContract::SchemaVersion)
        ->and($payload['source']['object_key'])->toEndWith('.dem.zst');
});

it('keeps the JSON schemas on the supported version', function (string $filename) {
    $schema = json_decode(
        file_get_contents(base_path("worker/contracts/{$filename}")),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($schema['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema')
        ->and($schema['properties']['schema_version']['const'])->toBe(WorkerContract::SchemaVersion);
})->with(['input.schema.json', 'output.schema.json', 'error.schema.json']);
