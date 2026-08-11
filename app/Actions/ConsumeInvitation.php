<?php

namespace App\Actions;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsumeInvitation
{
    /** @param array{name: string, email: string, password: string, invitation: string} $attributes */
    public function handle(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $invitation = Invitation::query()
                ->where('token_hash', hash('sha256', $attributes['invitation']))
                ->lockForUpdate()
                ->first();

            if ($invitation === null || ! $invitation->isAvailable() || $invitation->email !== $attributes['email']) {
                throw ValidationException::withMessages([
                    'invitation' => __('validation.invitation'),
                ]);
            }

            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
            ]);

            $invitation->update(['used_at' => now(), 'used_by_id' => $user->id]);

            return $user;
        }, 3);
    }
}
