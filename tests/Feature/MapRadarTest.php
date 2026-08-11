<?php

use App\Actions\DemoParserException;
use App\Actions\ResolveMapRadar;
use App\Models\MapRadar;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('resolves the versioned Mirage radar from the demo header', function () {
    $radar = app(ResolveMapRadar::class)->handle([
        'map_name' => 'de_mirage',
        'network_protocol' => 14011,
    ]);

    expect($radar->map_name)->toBe('de_mirage')
        ->and($radar->version)->toBe('17595823')
        ->and($radar->network_protocols)->toBe([14011])
        ->and([$radar->image_width, $radar->image_height])->toBe([1024, 1024])
        ->and(hash_file('sha256', resource_path($radar->image_path)))->toBe($radar->checksum_sha256)
        ->and($radar->worldToRadar(-3230, 1713))->toBe(['x' => 0.0, 'y' => 0.0])
        ->and($radar->worldToRadar(1890, -3407))->toBe(['x' => 1024.0, 'y' => 1024.0]);

    app(ResolveMapRadar::class)->handle([
        'map_name' => 'de_mirage',
        'network_protocol' => 14011,
    ]);

    expect(MapRadar::query()->count())->toBe(1);
});

it('rejects an unsupported demo map or version', function (array $header) {
    try {
        app(ResolveMapRadar::class)->handle($header);
    } catch (DemoParserException $exception) {
        expect($exception->workerCode)->toBe('unsupported_demo')
            ->and($exception->workerContext['retryable'])->toBeFalse()
            ->and($exception->workerContext['details'])->toMatchArray($header);

        return;
    }

    $this->fail('An unsupported demo must be rejected.');
})->with([
    'unsupported map' => [['map_name' => 'de_inferno', 'network_protocol' => 14011]],
    'unsupported version' => [['map_name' => 'de_mirage', 'network_protocol' => 99999]],
]);
