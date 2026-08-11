<?php

namespace App\Actions;

use App\Models\MapRadar;
use Illuminate\Contracts\Config\Repository;

class ResolveMapRadar
{
    public function __construct(private Repository $config) {}

    /** @param array<string, mixed> $demoHeader */
    public function handle(array $demoHeader): MapRadar
    {
        $mapName = $demoHeader['map_name'] ?? null;
        $patchVersion = filter_var($demoHeader['patch_version'] ?? null, FILTER_VALIDATE_INT);
        $versions = is_string($mapName) ? $this->config->get("map_radars.maps.{$mapName}", []) : [];

        foreach ($versions as $version => $definition) {
            if ($patchVersion === false || ! in_array($patchVersion, $definition['patch_versions'], true)) {
                continue;
            }

            $layers = $definition['layers'] ?? [];
            $defaultLayer = $layers['default'] ?? null;

            if (! is_array($defaultLayer) || $layers === [] || ! $this->validLayers($layers)) {
                throw DemoParserException::fromWorker('unsupported_demo', 'The configured map radar asset is invalid.', [
                    'retryable' => false,
                    'details' => ['map_name' => $mapName, 'radar_version' => (string) $version],
                ]);
            }

            return MapRadar::query()->updateOrCreate(
                ['map_name' => $mapName, 'version' => (string) $version],
                [
                    'patch_versions' => $definition['patch_versions'],
                    'image_path' => $defaultLayer['image_path'],
                    'image_width' => $defaultLayer['width'],
                    'image_height' => $defaultLayer['height'],
                    'checksum_sha256' => $defaultLayer['checksum_sha256'],
                    'coordinate_transform' => $definition['transform'],
                    'image_layers' => $layers,
                ],
            );
        }

        throw DemoParserException::fromWorker('unsupported_demo', 'The demo map or game version is not supported.', [
            'retryable' => false,
            'details' => ['map_name' => $mapName, 'patch_version' => $patchVersion],
        ]);
    }

    /** @param array<string, array<string, mixed>> $layers */
    private function validLayers(array $layers): bool
    {
        foreach ($layers as $layer) {
            $imagePath = isset($layer['image_path']) ? resource_path($layer['image_path']) : null;
            $dimensions = is_string($imagePath) && is_file($imagePath) ? getimagesize($imagePath) : false;

            if ($dimensions === false
                || $dimensions[0] !== ($layer['width'] ?? null)
                || $dimensions[1] !== ($layer['height'] ?? null)
                || hash_file('sha256', $imagePath) !== ($layer['checksum_sha256'] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
