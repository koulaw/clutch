<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('demo-uploads', function (Request $request): Limit {
            return Limit::perMinute((int) config('demo_upload.rate_limit_per_minute'))
                ->by((string) $request->user()->getAuthIdentifier())
                ->response(fn (Request $request, array $headers) => response()->json([
                    'message' => 'Too many upload requests. Please try again later.',
                    'code' => 'upload_rate_limit_exceeded',
                ], 429, $headers));
        });
    }
}
