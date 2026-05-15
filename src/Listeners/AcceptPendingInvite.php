<?php

namespace Guidance\FilamentTenantMembers\Listeners;

use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Auth\Events\Login;

class AcceptPendingInvite
{
    public function handle(Login $event): void
    {
        $token = session()->pull('pending_invite_token');

        if (! $token) {
            return;
        }

        if (! OrganizationInvite::byToken($token)->pending()->exists()) {
            return;
        }

        session()->put('url.intended', FilamentTenantMembers::getAcceptInviteUrl($token));
    }
}
