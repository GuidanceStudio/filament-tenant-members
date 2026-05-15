<?php

namespace Guidance\FilamentTenantMembers;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Clusters\Settings;
use Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages\AcceptInvite;
use Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages\Members;
use Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages\Settings\GeneralSettings;
use Guidance\FilamentTenantMembers\Livewire\ListInvitations;
use Guidance\FilamentTenantMembers\Livewire\ListMembers;

class FilamentTenantMembersPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-tenant-members';
    }

    public static function make(): static
    {
        return new static;
    }

    public function register(Panel $panel): void
    {
        $panel
            ->authenticatedRoutes(fn (Panel $panel) => AcceptInvite::registerRoutes($panel))
            ->pages([
                Settings::class,
                GeneralSettings::class,
                Members::class,
            ])
            ->livewireComponents([
                ListMembers::class,
                ListInvitations::class,
            ]);
    }

    public function boot(Panel $panel): void {}
}
