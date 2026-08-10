<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response|RedirectResponse
    {
        return request()->user()->hasVerifiedEmail()
            ? redirect()->route('dashboard')
            : Inertia::render('auth/verify-email');
    }
}
