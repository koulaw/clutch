<?php

namespace App\Actions;

use App\Models\Demo;
use Illuminate\Support\Facades\DB;

class ConfirmDemoUpload
{
    public function __construct(
        private ValidateDemoUpload $validator,
        private ManageUserQuota $quotas,
    ) {}

    public function handle(Demo $demo): Demo
    {
        if ($demo->uploaded_at !== null) {
            return $demo;
        }

        $this->validator->handle($demo);

        return DB::transaction(function () use ($demo): Demo {
            $lockedDemo = Demo::query()->whereKey($demo)->lockForUpdate()->firstOrFail();

            if ($lockedDemo->uploaded_at !== null) {
                return $lockedDemo;
            }

            $this->quotas->consumeImport($lockedDemo->user);
            $lockedDemo->update(['uploaded_at' => now()]);

            return $lockedDemo;
        });
    }
}
