<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invitation:list')]
#[Description('List beta invitations')]
class ListInvitationsCommand extends Command
{
    public function handle(): int
    {
        $invitations = Invitation::query()->latest()->get();

        $this->table(
            ['ID', 'Email', 'Status', 'Expires at', 'Created at'],
            $invitations->map(fn (Invitation $invitation): array => [
                $invitation->id,
                $invitation->email,
                $this->status($invitation),
                $invitation->expires_at?->toDateTimeString() ?? 'Never',
                $invitation->created_at->toDateTimeString(),
            ])->all(),
        );

        return self::SUCCESS;
    }

    private function status(Invitation $invitation): string
    {
        if ($invitation->used_at !== null) {
            return 'Used';
        }

        if ($invitation->revoked_at !== null) {
            return 'Revoked';
        }

        if ($invitation->expires_at?->isPast()) {
            return 'Expired';
        }

        return 'Available';
    }
}
