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
        $networkProtocol = filter_var($demoHeader['network_protocol'] ?? null, FILTER_VALIDATE_INT);
        $versions = is_string($mapName) ? $this->config->get("map_radars.maps.{$mapName}", []) : [];

        foreach ($versions as $version => $definition) {
            if ($networkProtocol === false || ! in_array($networkProtocol, $definition['network_protocols'], true)) {
                continue;
            }

            $imagePath = resource_path($definition['image_path']);
            $dimensions = is_file($imagePath) ? getimagesize($imagePath) : false;

            if ($dimensions === false
                || $dimensions[0] !== $definition['width']
                || $dimensions[1] !== $definition['height']
                || hash_file('sha256', $imagePath) !== $definition['checksum_sha256']) {
                throw DemoParserException::fromWorker('unsupported_demo', 'The configured map radar asset is invalid.', [
                    'retryable' => false,
                    'details' => ['map_name' => $mapName, 'radar_version' => (string) $version],
                ]);
            }

            return MapRadar::query()->updateOrCreate(
                ['map_name' => $mapName, 'version' => (string) $version],
                [
                    'network_protocols' => $definition['network_protocols'],
                    'image_path' => $definition['image_path'],
                    'image_width' => $definition['width'],
                    'image_height' => $definition['height'],
                    'checksum_sha256' => $definition['checksum_sha256'],
                    'coordinate_transform' => $definition['transform'],
                ],
            );
        }

        throw DemoParserException::fromWorker('unsupported_demo', 'The demo map or game version is not supported.', [
            'retryable' => false,
            'details' => ['map_name' => $mapName, 'network_protocol' => $networkProtocol],
        ]);
    }
}
