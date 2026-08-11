<?php

use App\Actions\ManageUserQuota;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DemoAnalysisController;
use App\Http\Controllers\DemoUploadController;
use App\Http\Controllers\LocaleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/', fn (): Response => Inertia::render('home'))->name('home');
Route::post('/locale', LocaleController::class)->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::get('/dashboard', fn (Request $request, ManageUserQuota $quotas): Response => Inertia::render('dashboard', [
    'quotas' => $quotas->usage($request->user()),
]))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::prefix('api/v1')->middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/analyses', [DemoAnalysisController::class, 'index'])
        ->middleware('throttle:120,1')
        ->name('api.analyses.index');

    Route::middleware('throttle:demo-uploads')->group(function (): void {
        Route::post('/demos/upload-url', [DemoUploadController::class, 'store'])->name('api.demos.upload.store');
        Route::post('/demos/{demo}/confirm', [DemoUploadController::class, 'confirm'])->name('api.demos.upload.confirm');
        Route::post('/demos/{demo}/analysis/retry', [DemoUploadController::class, 'retry'])->name('api.demos.analysis.retry');
    });
});
