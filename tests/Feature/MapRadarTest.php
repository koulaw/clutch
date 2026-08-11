<?php

use App\Actions\DemoParserException;
use App\Actions\ResolveMapRadar;
use App\Models\MapRadar;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('resolves every supported map with verified radar layers', function (string $mapName) {
    $radar = app(ResolveMapRadar::class)->handle([
        'map_name' => $mapName,
        'patch_version' => '14174',
    ]);

    expect($radar->map_name)->toBe($mapName)
        ->and($radar->version)->toBe('84adbb9dca5a')
        ->and($radar->patch_versions)->toBe([14174])
        ->and([$radar->image_width, $radar->image_height])->toBe([1024, 1024])
        ->and(hash_file('sha256', resource_path($radar->image_path)))->toBe($radar->checksum_sha256);

    foreach ($radar->image_layers as $layer) {
        $dimensions = getimagesize(resource_path($layer['image_path']));

        expect([$dimensions[0], $dimensions[1]])->toBe([1024, 1024])
            ->and(hash_file('sha256', resource_path($layer['image_path'])))->toBe($layer['checksum_sha256']);
    }

    app(ResolveMapRadar::class)->handle([
        'map_name' => $mapName,
        'patch_version' => 14174,
    ]);

    expect(MapRadar::query()->count())->toBe(1);
})->with([
    'Ancient' => 'de_ancient',
    'Anubis' => 'de_anubis',
    'Cache' => 'de_cache',
    'Dust II' => 'de_dust2',
    'Inferno' => 'de_inferno',
    'Mirage' => 'de_mirage',
    'Nuke' => 'de_nuke',
    'Overpass' => 'de_overpass',
    'Train' => 'de_train',
    'Vertigo' => 'de_vertigo',
]);

it('transforms Mirage coordinates from world units to radar pixels', function () {
    $radar = app(ResolveMapRadar::class)->handle(['map_name' => 'de_mirage', 'patch_version' => 14174]);

    expect($radar->worldToRadar(-3230, 1713))->toBe(['x' => 0.0, 'y' => 0.0])
        ->and($radar->worldToRadar(1890, -3407))->toBe(['x' => 1024.0, 'y' => 1024.0]);
});

it('selects the correct radar layer from player altitude', function (string $mapName, float $upperAltitude, float $lowerAltitude) {
    $radar = app(ResolveMapRadar::class)->handle(['map_name' => $mapName, 'patch_version' => 14174]);

    expect($radar->imagePathForAltitude($upperAltitude))->toEndWith('/radar.png')
        ->and($radar->imagePathForAltitude($lowerAltitude))->toEndWith('/radar-lower.png');
})->with([
    'Nuke' => ['de_nuke', 0.0, -496.0],
    'Train' => ['de_train', 0.0, -51.0],
    'Vertigo' => ['de_vertigo', 12000.0, 11699.0],
]);

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
    'unsupported map' => [['map_name' => 'de_cbble', 'patch_version' => 14174]],
    'unsupported version' => [['map_name' => 'de_mirage', 'patch_version' => 99999]],
    'missing version' => [['map_name' => 'de_mirage']],
]);
