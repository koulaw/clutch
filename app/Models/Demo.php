<?php

namespace App\Models;

use App\AnalysisStatus;
use Database\Factories\DemoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'public_id', 'storage_disk', 'storage_path', 'checksum_sha256', 'size_bytes', 'status', 'uploaded_at'])]
class Demo extends Model
{
    /** @use HasFactory<DemoFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => AnalysisStatus::Uploaded->value,
    ];

    protected static function booted(): void
    {
        static::creating(function (Demo $demo): void {
            $demo->public_id ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Analysis, $this> */
    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => AnalysisStatus::class,
            'size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }
}
