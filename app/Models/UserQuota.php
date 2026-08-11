<?php

namespace App\Models;

use Database\Factories\UserQuotaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'daily_imports', 'imports_on', 'stored_analyses'])]
class UserQuota extends Model
{
    /** @use HasFactory<UserQuotaFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'imports_on' => 'date',
            'daily_imports' => 'integer',
            'stored_analyses' => 'integer',
        ];
    }
}
