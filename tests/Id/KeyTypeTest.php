<?php

use Guidance\FilamentTenantMembers\Models\Organization;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Support\Str;
use Tests\Models\User;

it('resolves Organization as an auto-incrementing bigint model', function () {
    $organization = new Organization;

    expect($organization->getKeyType())->toBe('int')
        ->and($organization->getIncrementing())->toBeTrue();
});

it('creates an Organization with an integer primary key', function () {
    $organization = Organization::create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    expect($organization->getKey())->toBeInt()
        ->and($organization->getKey())->toBeGreaterThan(0);
});

it('attaches a user to an organization through the integer-keyed pivot', function () {
    $user = User::create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
        'password' => 'secret',
    ]);

    $organization = Organization::create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    $organization->users()->attach($user->getKey(), ['role' => 'user']);

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->getKey(),
        'user_id' => $user->getKey(),
        'role' => 'user',
    ]);

    expect($organization->users()->count())->toBe(1);
});

it('creates an OrganizationInvite with an integer primary key', function () {
    $user = User::create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.test',
        'password' => 'secret',
    ]);

    $organization = Organization::create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    $invite = OrganizationInvite::create([
        'token' => (string) Str::uuid7(),
        'organization_id' => $organization->getKey(),
        'user_id' => $user->getKey(),
        'email' => 'invitee@example.test',
        'expires_at' => now()->addDay(),
    ]);

    expect($invite->getKeyType())->toBe('int')
        ->and($invite->getIncrementing())->toBeTrue()
        ->and($invite->getKey())->toBeInt()
        ->and($invite->getKey())->toBeGreaterThan(0);
});
