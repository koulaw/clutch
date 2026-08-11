<?php

namespace App\Models;

use Database\Factories\GameMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['analysis_id', 'map_radar_id', 'external_id', 'map_name', 'started_at', 'duration_ms', 'tick_rate', 'team_one_name', 'team_two_name', 'team_one_score', 'team_two_score', 'winner_side', 'metadata'])]
class GameMatch extends Model
{
    /** @use HasFactory<GameMatchFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'team_one_score' => 0,
        'team_two_score' => 0,
    ];

    /** @return BelongsTo<Analysis, $this> */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    /** @return BelongsTo<MapRadar, $this> */
    public function mapRadar(): BelongsTo
    {
        return $this->belongsTo(MapRadar::class);
    }

    /** @return HasMany<GameRound, $this> */
    public function rounds(): HasMany
    {
        return $this->hasMany(GameRound::class);
    }

    /** @return BelongsToMany<Player, $this> */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class)
            ->withPivot(['team_name', 'starting_side'])
            ->withTimestamps();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'duration_ms' => 'integer',
            'tick_rate' => 'decimal:3',
            'team_one_score' => 'integer',
            'team_two_score' => 'integer',
            'metadata' => 'array',
        ];
    }
}
