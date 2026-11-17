<?php

use Guidance\FilamentTenantMembers\FilamentTenantMembers;

// Key-type independent (pure config resolution), so it runs in the Id suite only.

it('ships demoted_owner_role as null, so the demoted owner falls back to default_role', function () {
    expect(config()->has('filament-tenant-members.demoted_owner_role'))->toBeTrue()
        ->and(config('filament-tenant-members.demoted_owner_role'))->toBeNull()
        ->and(FilamentTenantMembers::getDemotedOwnerRole())->toBe('user');
});

it('follows a customised default_role while demoted_owner_role is null', function () {
    config()->set('filament-tenant-members.default_role', 'admin');

    expect(FilamentTenantMembers::getDemotedOwnerRole())->toBe('admin');
});

it('uses demoted_owner_role when set, without changing the invitation default', function () {
    config()->set('filament-tenant-members.demoted_owner_role', 'admin');

    expect(FilamentTenantMembers::getDemotedOwnerRole())->toBe('admin')
        ->and(FilamentTenantMembers::getDefaultRole())->toBe('user');
});
