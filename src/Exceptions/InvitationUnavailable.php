<?php

namespace Guidance\FilamentTenantMembers\Exceptions;

use Guidance\FilamentTenantMembers\Enums\InvitationUnavailableReason;
use RuntimeException;

class InvitationUnavailable extends RuntimeException
{
    public function __construct(public readonly InvitationUnavailableReason $reason)
    {
        parent::__construct(match ($reason) {
            InvitationUnavailableReason::NotPending => 'This invitation is invalid or has expired.',
            InvitationUnavailableReason::EmailMismatch => 'This invitation was sent to a different email address.',
        });
    }
}
