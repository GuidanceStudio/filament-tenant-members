<?php

use Carbon\CarbonInterval;
use Guidance\FilamentTenantMembers\Models\Organization;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Support\Facades\Hash;
use Tests\Models\User;

/**
 * The admin-provisioning ("set your own password") lifecycle added in v0.1.1:
 * mintForUser() mints a targeted single-use, TTL-bounded invite; the invitee
 * consumes it via completePasswordSet() to set their OWN secret and join the
 * org. The admin never handles the password.
 */

function spfSeed(): array
{
    $user = User::create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.test',
        'password' => 'placeholder-unusable',
    ]);

    $organization = Organization::create(['name' => 'Acme', 'slug' => 'acme']);

    return [$user, $organization];
}

it('mints a pending, targeted, single-use invite for an existing user', function () {
    [$user, $org] = spfSeed();

    $invite = OrganizationInvite::mintForUser($user, $org, 'admin', CarbonInterval::hours(48));

    expect($invite->email)->toBe('grace@example.test')
        ->and($invite->user_id)->toBe($user->getKey())
        ->and($invite->accepted_at)->toBeNull()
        ->and($invite->expires_at->isFuture())->toBeTrue()
        ->and($invite->expires_at->lessThanOrEqualTo(now()->addHours(48)->addMinute()))->toBeTrue()
        ->and(OrganizationInvite::byToken($invite->token)->pending()->exists())->toBeTrue();
});

it('defaults the TTL to the configured invite_expires_days', function () {
    [$user, $org] = spfSeed();
    config()->set('filament-tenant-members.invite_expires_days', 7);

    $invite = OrganizationInvite::mintForUser($user, $org, 'admin');

    expect($invite->expires_at->greaterThan(now()->addDays(6)))->toBeTrue()
        ->and($invite->expires_at->lessThanOrEqualTo(now()->addDays(7)->addMinute()))->toBeTrue();
});

it('sets the user own password, hashes it, and joins the org on completion', function () {
    [$user, $org] = spfSeed();
    $invite = OrganizationInvite::mintForUser($user, $org, 'admin');

    $returned = $invite->completePasswordSet('chosen-by-the-user');

    expect($returned->getKey())->toBe($user->getKey())
        ->and(Hash::check('chosen-by-the-user', $user->fresh()->password))->toBeTrue()
        ->and($invite->fresh()->accepted_at)->not->toBeNull();

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $org->getKey(),
        'user_id' => $user->getKey(),
        'role' => 'admin',
    ]);
});

it('is single-use: a consumed invite is no longer pending', function () {
    [$user, $org] = spfSeed();
    $invite = OrganizationInvite::mintForUser($user, $org, 'admin');

    $invite->completePasswordSet('first-secret');

    expect(OrganizationInvite::byToken($invite->token)->pending()->exists())->toBeFalse();
});
