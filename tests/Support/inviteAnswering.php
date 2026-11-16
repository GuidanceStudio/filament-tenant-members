<?php

use Carbon\CarbonInterval;
use Guidance\FilamentTenantMembers\Enums\InvitationUnavailableReason;
use Guidance\FilamentTenantMembers\Events\OrganizationInviteAccepted;
use Guidance\FilamentTenantMembers\Exceptions\InvitationUnavailable;
use Guidance\FilamentTenantMembers\Models\Organization;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\Models\User;

/**
 * Shared by tests/Id and tests/Uuid: the guarded accept() contract must hold under
 * both primary-key strategies.
 */
function answeringSeed(array $inviteOverrides = []): array
{
    $inviter = User::create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'secret']);
    $invitee = User::create(['name' => 'Grace', 'email' => 'grace@example.test', 'password' => 'secret']);
    $organization = Organization::create(['name' => 'Acme', 'slug' => 'acme']);

    $invite = OrganizationInvite::create(array_merge([
        'token' => (string) Illuminate\Support\Str::uuid(),
        'organization_id' => $organization->getKey(),
        'user_id' => $inviter->getKey(),
        'email' => 'GRACE@example.test',
        'role' => 'admin',
        'expires_at' => now()->addDays(3),
    ], $inviteOverrides));

    return [$invite, $invitee, $inviter, $organization];
}

/** Records every dispatch through the REAL dispatcher (Event::fake ignores transactions). */
function answeringRecord(string $event): ArrayObject
{
    $fired = new ArrayObject;
    Event::listen($event, fn ($e) => $fired->append($e));

    return $fired;
}

/** The user's role in the org, or null when not a member. */
function answeringRole($organization, $user): ?string
{
    return DB::table('organization_user')
        ->where('organization_id', $organization->getKey())
        ->where('user_id', $user->getKey())
        ->value('role');
}

function answeringReason(callable $call): ?InvitationUnavailableReason
{
    try {
        $call();
    } catch (InvitationUnavailable $e) {
        return $e->reason;
    }

    return null;
}

function registerInviteAnsweringTests(): void
{
    it('accept() joins the org with the invited role and fires the accepted event once', function () {
        [$invite, $invitee, $inviter, $org] = answeringSeed();
        $fired = answeringRecord(OrganizationInviteAccepted::class);

        $invite->accept($invitee);

        expect(answeringRole($org, $invitee))->toBe('admin');
        expect($invite->accepted_at)->not->toBeNull()
            ->and($invite->fresh()->accepted_at)->not->toBeNull()
            ->and($fired)->toHaveCount(1)
            ->and($fired[0]->invite->is($invite))->toBeTrue()
            ->and($fired[0]->invite->accepted_at)->not->toBeNull()
            ->and($fired[0]->invite->organization->is($org))->toBeTrue()
            ->and($fired[0]->invite->user->is($inviter))->toBeTrue()
            ->and($fired[0]->user->is($invitee))->toBeTrue();
    });

    it('accept() refuses with NotPending an expired, accepted or deleted invite', function (string $state) {
        [$invite, $invitee, , $org] = answeringSeed();
        $fired = answeringRecord(OrganizationInviteAccepted::class);

        match ($state) {
            'expired' => $invite->update(['expires_at' => now()->subMinute()]),
            'accepted' => $invite->update(['accepted_at' => now()]),
            'deleted' => $invite->delete(),
        };

        expect(answeringReason(fn () => $invite->accept($invitee)))->toBe(InvitationUnavailableReason::NotPending);
        expect(answeringRole($org, $invitee))->toBeNull();
        expect($fired)->toHaveCount(0);
    })->with(['expired', 'accepted', 'deleted']);

    it('accept() refuses with EmailMismatch another user', function () {
        [$invite, , $inviter, $org] = answeringSeed();
        $fired = answeringRecord(OrganizationInviteAccepted::class);

        expect(answeringReason(fn () => $invite->accept($inviter)))->toBe(InvitationUnavailableReason::EmailMismatch);
        expect(answeringRole($org, $inviter))->toBeNull();
        expect($invite->fresh()->accepted_at)->toBeNull()
            ->and($fired)->toHaveCount(0);
    });

    it('accept() twice: the second refuses with NotPending and fires no second event', function () {
        [$invite, $invitee] = answeringSeed();
        $fired = answeringRecord(OrganizationInviteAccepted::class);

        $invite->accept($invitee);

        expect(answeringReason(fn () => $invite->accept($invitee)))->toBe(InvitationUnavailableReason::NotPending)
            ->and($fired)->toHaveCount(1);
    });

    it('completePasswordSet() fires the accepted event', function () {
        [$invite, $invitee, $inviter] = answeringSeed(['email' => 'grace@example.test']);
        $fired = answeringRecord(OrganizationInviteAccepted::class);

        $invite->completePasswordSet('chosen-by-the-user');

        expect(Hash::check('chosen-by-the-user', $invitee->fresh()->password))->toBeTrue()
            ->and($fired)->toHaveCount(1)
            ->and($fired[0]->user->is($invitee))->toBeTrue();
    });

    it('completePasswordSet() on a used or expired invite refuses and keeps the password', function (string $state) {
        [$invite, $invitee, , $org] = answeringSeed(['email' => 'grace@example.test']);
        $fired = answeringRecord(OrganizationInviteAccepted::class);
        $before = $invitee->fresh()->password;

        match ($state) {
            'expired' => $invite->update(['expires_at' => now()->subMinute()]),
            'used' => $invite->update(['accepted_at' => now()]),
        };

        expect(answeringReason(fn () => $invite->completePasswordSet('hijack-attempt')))
            ->toBe(InvitationUnavailableReason::NotPending);
        expect($invitee->fresh()->password)->toBe($before)
            ->and($fired)->toHaveCount(0);
        expect(answeringRole($org, $invitee))->toBeNull();
    })->with(['expired', 'used']);

    it('accept() inside an outer transaction that rolls back fires no event', function () {
        [$invite, $invitee, , $org] = answeringSeed();
        $fired = answeringRecord(OrganizationInviteAccepted::class);

        try {
            DB::transaction(function () use ($invite, $invitee) {
                $invite->accept($invitee);
                throw new RuntimeException('outer failure');
            });
        } catch (RuntimeException) {
        }

        expect($fired)->toHaveCount(0);
        expect(answeringRole($org, $invitee))->toBeNull();
    });

    it('creating an invite or bumping its expiry fires no accepted event', function () {
        [$invite, , $inviter, $org] = answeringSeed();
        $fired = answeringRecord(OrganizationInviteAccepted::class);

        $invite->update(['expires_at' => now()->add(CarbonInterval::days(7))]);
        OrganizationInvite::mintForUser($inviter, $org, 'user');

        expect($fired)->toHaveCount(0);
    });
}
