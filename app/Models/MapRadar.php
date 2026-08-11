<?php

namespace App\Models;

use Database\Factories\MapRadarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['map_name', 'version', 'network_protocols', 'image_path', 'image_width', 'image_height', 'checksum_sha256', 'coordinate_transform'])]
class MapRadar extends Model
{
    /** @use HasFactory<MapRadarFactory> */
    use HasFactory;

    /** @return HasMany<GameMatch, $this> */
    public function gameMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    /** @return array{x: float, y: float} */
    public function worldToRadar(float $worldX, float $worldY): array
    {
        $transform = $this->coordinate_transform;

        return [
            'x' => ($worldX - $transform['pos_x']) / $transform['scale'],
            'y' => ($transform['pos_y'] - $worldY) / $transform['scale'],
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'network_protocols' => 'array',
            'image_width' => 'integer',
            'image_height' => 'integer',
            'coordinate_transform' => 'array',
        ];
    }
}
