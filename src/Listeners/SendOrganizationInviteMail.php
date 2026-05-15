<?php

namespace Guidance\FilamentTenantMembers\Listeners;

use Guidance\FilamentTenantMembers\Events\OrganizationInviteCreated;
use Guidance\FilamentTenantMembers\Mail\OrganizationInviteMail;
use Illuminate\Support\Facades\Mail;

class SendOrganizationInviteMail
{
    public function handle(OrganizationInviteCreated $event): void
    {
        $event->invite->loadMissing(['organization', 'user']);

        Mail::to($event->invite->email)->send(new OrganizationInviteMail($event->invite));
    }
}
