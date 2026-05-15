<?php

namespace Guidance\FilamentTenantMembers\Http\Controllers;

use Filament\Facades\Filament;
use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcceptInviteController
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $invite = OrganizationInvite::query()
            ->where('token', $token)
            ->pending()
            ->first();

        if (! $invite) {
            abort(404, 'This invitation is invalid or has expired.');
        }

        if (auth()->check()) {
            return redirect()->to(FilamentTenantMembers::getAcceptInviteUrl($token));
        }

        session()->put('pending_invite_token', $token);

        $panel = Filament::getPanel(FilamentTenantMembers::getPanelId());
        $userModel = FilamentTenantMembers::getUserModel();

        if ($userModel::where('email', $invite->email)->exists()) {
            return redirect()->to($panel->getLoginUrl());
        }

        session()->put('pending_invite_email', $invite->email);

        return redirect()->to($panel->getRegistrationUrl());
    }
}
