<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('invitation:create {email : The invited email address} {--days=7 : Days before expiry, or 0 for no expiry}')]
#[Description('Create a beta invitation')]
class CreateInvitationCommand extends Command
{
    public function handle(): int
    {
        $email = Str::of((string) $this->argument('email'))->trim()->lower()->toString();
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);
        $validator = Validator::make(
            ['email' => $email, 'days' => $days],
            ['email' => ['required', 'email'], 'days' => ['required', 'integer', 'min:0']],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $token = Str::random(64);
        $invitation = Invitation::create([
            'email' => $email,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $days === 0 ? null : now()->addDays($days),
        ]);

        $this->info("Invitation #{$invitation->id} created for {$invitation->email}.");
        $this->line(route('register', ['invitation' => $token]));

        return self::SUCCESS;
    }
}
