<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserQuota;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;

class ManageUserQuota
{
    public function __construct(private Repository $config) {}

    /**
     * @return array{imports: array{used: int, limit: int}, analyses: array{used: int, limit: int}}
     */
    public function usage(User $user): array
    {
        $quota = UserQuota::query()
            ->whereBelongsTo($user)
            ->first(['daily_imports', 'imports_on', 'stored_analyses']);

        return [
            'imports' => [
                'used' => $quota?->imports_on?->isToday() ? $quota->daily_imports : 0,
                'limit' => $this->dailyImportLimit(),
            ],
            'analyses' => [
                'used' => $quota?->stored_analyses ?? 0,
                'limit' => $this->storedAnalysisLimit(),
            ],
        ];
    }

    public function consumeImport(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $quota = $this->quotaForUpdate($user);

            if (! $quota->imports_on?->isToday()) {
                $quota->daily_imports = 0;
                $quota->imports_on = today();
            }

            $limit = $this->dailyImportLimit();

            if ($quota->daily_imports >= $limit) {
                throw new QuotaExceededException('daily_imports', $limit);
            }

            $quota->daily_imports++;
            $quota->save();
        });
    }

    public function storeAnalysis(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $quota = $this->quotaForUpdate($user);
            $limit = $this->storedAnalysisLimit();

            if ($quota->stored_analyses >= $limit) {
                throw new QuotaExceededException('stored_analyses', $limit);
            }

            $quota->stored_analyses++;
            $quota->save();
        });
    }

    public function releaseAnalysis(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $quota = $this->quotaForUpdate($user);

            if ($quota->stored_analyses > 0) {
                $quota->stored_analyses--;
                $quota->save();
            }
        });
    }

    private function quotaForUpdate(User $user): UserQuota
    {
        User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

        return UserQuota::query()->firstOrCreate(['user_id' => $user->getKey()]);
    }

    private function dailyImportLimit(): int
    {
        return max(0, (int) $this->config->get('quotas.daily_imports'));
    }

    private function storedAnalysisLimit(): int
    {
        return max(0, (int) $this->config->get('quotas.stored_analyses'));
    }
}
