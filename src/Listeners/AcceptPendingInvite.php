<?php

namespace Guidance\FilamentTenantMembers\Listeners;

use Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages\Auth\Register;
use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Auth\Events\Login;

class AcceptPendingInvite
{
    public function handle(Login $event): void
    {
        // Whatever brought them here, the registration form's email lock is spent
        // once they are authenticated.
        session()->forget(Register::INVITE_EMAIL_SESSION_KEY);

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
