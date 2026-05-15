<?php

namespace Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Component;

class Register extends BaseRegister
{
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());

            return;
        }

        parent::mount();

        if ($email = session()->pull('pending_invite_email')) {
            $this->form->fill(['email' => $email]);
        }
    }

    protected function getEmailFormComponent(): Component
    {
        $component = parent::getEmailFormComponent();

        if ($this->data['email'] ?? false) {
            $component->disabled()->dehydrated();
        }

        return $component;
    }
}
