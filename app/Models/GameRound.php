<?php

namespace App\Models;

use Database\Factories\GameRoundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['game_match_id', 'number', 'start_tick', 'freeze_end_tick', 'end_tick', 'winner_side', 'win_reason', 'team_one_score', 'team_two_score'])]
class GameRound extends Model
{
    /** @use HasFactory<GameRoundFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'team_one_score' => 0,
        'team_two_score' => 0,
    ];

    /** @return BelongsTo<GameMatch, $this> */
    public function gameMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class);
    }

    /** @return HasMany<Artifact, $this> */
    public function artifacts(): HasMany
    {
        return $this->hasMany(Artifact::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'start_tick' => 'integer',
            'freeze_end_tick' => 'integer',
            'end_tick' => 'integer',
            'team_one_score' => 'integer',
            'team_two_score' => 'integer',
        ];
    }
}
