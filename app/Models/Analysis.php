<?php

namespace App\Models;

use App\AnalysisStatus;
use Database\Factories\AnalysisFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['demo_id', 'attempt', 'status', 'schema_version', 'parser_version', 'started_at', 'completed_at', 'failed_at', 'error_code', 'error_message', 'error_context'])]
class Analysis extends Model
{
    /** @use HasFactory<AnalysisFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'attempt' => 1,
        'status' => AnalysisStatus::Queued->value,
    ];

    /** @return BelongsTo<Demo, $this> */
    public function demo(): BelongsTo
    {
        return $this->belongsTo(Demo::class);
    }

    /** @return HasOne<GameMatch, $this> */
    public function gameMatch(): HasOne
    {
        return $this->hasOne(GameMatch::class);
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
            'status' => AnalysisStatus::class,
            'attempt' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
            'error_context' => 'array',
        ];
    }
}
