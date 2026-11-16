<?php

namespace Guidance\FilamentTenantMembers\Events;

use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An invitation became a membership. `invite` is the accepted row, with its
 * `organization` and `user` (the inviter) loaded; `user` is who accepted.
 *
 * In the admin-provisioning flow the invite's `user` is the invitee itself, so a
 * consumer notifying "the inviter" should skip it when
 * `$event->invite->user_id === $event->user->getAuthIdentifier()`.
 */
class OrganizationInviteAccepted implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public OrganizationInvite $invite,
        public Authenticatable $user,
    ) {}
}
