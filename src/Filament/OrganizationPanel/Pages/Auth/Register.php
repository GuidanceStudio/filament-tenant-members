<?php

namespace Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class Register extends BaseRegister
{
    /**
     * Session key holding the email address of the invite that sent an
     * unregistered person here. Set by {@see \Guidance\FilamentTenantMembers\Http\Controllers\AcceptInviteController}.
     */
    public const INVITE_EMAIL_SESSION_KEY = 'pending_invite_email';

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        parent::mount();
    }

    /**
     * When the visitor arrived from an invitation, the address is the whole point
     * of the link: pre-fill it and lock the field. The value is read from the
     * session on every request (never from Livewire state), so it survives a page
     * reload and cannot be swapped by a tampered request.
     */
    protected function getEmailFormComponent(): Component
    {
        $component = parent::getEmailFormComponent();

        if (filled($email = $this->getInvitedEmail())) {
            $component
                ->default($email)
                ->disabled()
                ->dehydrated();
        }

        return $component;
    }

    /**
     * The default subheading is an "Already registered? Sign in" link. An invitee
     * arriving here has no account by definition — the controller only routes
     * unknown addresses to registration — so the prompt is noise.
     */
    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->getInvitedEmail())) {
            return null;
        }

        return parent::getSubheading();
    }

    /**
     * The email input is disabled client-side, but Livewire state is still
     * client-supplied — re-assert the invited address before validation so the
     * account is created for the invitee and the `unique` rule runs against it.
     */
    protected function beforeValidate(): void
    {
        if (filled($email = $this->getInvitedEmail())) {
            $this->data['email'] = $email;
        }
    }

    protected function afterRegister(): void
    {
        session()->forget(static::INVITE_EMAIL_SESSION_KEY);
    }

    protected function getInvitedEmail(): ?string
    {
        return session()->get(static::INVITE_EMAIL_SESSION_KEY);
    }
}
