<?php

namespace Guidance\FilamentTenantMembers;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Guidance\FilamentTenantMembers\Filament\AdminPanel\Resources\OrganizationResource;
use Guidance\FilamentTenantMembers\Filament\AdminPanel\Resources\UserResource;

class FilamentTenantMembersAdminPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-tenant-members-admin';
    }

    public static function make(): static
    {
        return new static;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            UserResource::class,
            OrganizationResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
