<?php

namespace App\Http\Controllers\Auth;

use App\Actions\ConsumeInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('auth/register', [
            'invitation' => $request->string('invitation')->toString(),
        ]);
    }

    public function store(RegisterRequest $request, ConsumeInvitation $consumeInvitation): RedirectResponse
    {
        $user = $consumeInvitation->handle($request->validated());

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
