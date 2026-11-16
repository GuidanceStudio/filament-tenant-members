<?php

namespace Guidance\FilamentTenantMembers\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An invitee declined. Carries no invite model: the row is deleted by then, so a
 * queued listener could not restore it. `inviter` is the invite's `user` (in the
 * admin-provisioning flow that is the invitee itself); `role` is the enum value.
 *
 * A revoke (deleting the invite from the invitations list) does NOT fire this.
 */
class OrganizationInviteDeclined implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Model $organization,
        public Model $inviter,
        public Authenticatable $user,
        public string $email,
        public string $role,
    ) {}
}
