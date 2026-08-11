<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invitation:revoke {invitation : The invitation ID}')]
#[Description('Revoke a beta invitation')]
class RevokeInvitationCommand extends Command
{
    public function handle(): int
    {
        $invitation = Invitation::find($this->argument('invitation'));

        if ($invitation === null) {
            $this->error('Invitation not found.');

            return self::FAILURE;
        }

        if (! $invitation->isAvailable()) {
            $this->error('Only an available invitation can be revoked.');

            return self::FAILURE;
        }

        $invitation->update(['revoked_at' => now()]);
        $this->info("Invitation #{$invitation->id} revoked.");

        return self::SUCCESS;
    }
}
