<?php

namespace Guidance\FilamentTenantMembers\Console\Commands;

use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Console\Command;

class CleanupExpiredInvites extends Command
{
    protected $signature = 'filament-tenant-members:cleanup-invites';

    protected $description = 'Delete expired and unaccepted organization invitations';

    public function handle(): int
    {
        $count = OrganizationInvite::query()
            ->where('expires_at', '<', now())
            ->whereNull('accepted_at')
            ->delete();

        $this->info("Deleted {$count} expired invitation(s).");

        return self::SUCCESS;
    }
}
