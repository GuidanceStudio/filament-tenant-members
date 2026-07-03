<?php

use Guidance\FilamentTenantMembers\Models\Organization;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Support\Str;
use Tests\Models\User;

it('resolves Organization as a non-incrementing uuid-string model', function () {
    $organization = new Organization;

    expect($organization->getKeyType())->toBe('string')
        ->and($organization->getIncrementing())->toBeFalse();
});

it('creates an Organization with a UUIDv7 string primary key', function () {
    $organization = Organization::create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    $id = $organization->getKey();

    expect($id)->toBeString()
        ->and(strlen($id))->toBe(36)
        ->and(Str::isUuid($id))->toBeTrue()
        ->and($id[14])->toBe('7'); // UUIDv7 version nibble
});

it('attaches a uuid-keyed user to an organization through the uuid pivot', function () {
    $user = User::create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.test',
        'password' => 'secret',
    ]);

    $organization = Organization::create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    expect($user->getKey())->toBeString()
        ->and(strlen($user->getKey()))->toBe(36);

    $organization->users()->attach($user->getKey(), ['role' => 'user']);

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $organization->getKey(),
        'user_id' => $user->getKey(),
        'role' => 'user',
    ]);

    expect($organization->users()->count())->toBe(1);
});

it('creates an OrganizationInvite with a UUIDv7 string primary key and uuid token', function () {
    $user = User::create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.test',
        'password' => 'secret',
    ]);

    $organization = Organization::create([
        'name' => 'Acme',
        'slug' => 'acme',
    ]);

    $token = (string) Str::uuid7();

    $invite = OrganizationInvite::create([
        'token' => $token,
        'organization_id' => $organization->getKey(),
        'user_id' => $user->getKey(),
        'email' => 'invitee@example.test',
        'expires_at' => now()->addDay(),
    ]);

    $id = $invite->getKey();

    expect($invite->getKeyType())->toBe('string')
        ->and($invite->getIncrementing())->toBeFalse()
        ->and($id)->toBeString()
        ->and(strlen($id))->toBe(36)
        ->and(Str::isUuid($id))->toBeTrue()
        ->and($id[14])->toBe('7')
        ->and(Str::isUuid($invite->token))->toBeTrue();
});
