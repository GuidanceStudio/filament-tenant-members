<?php

namespace Guidance\FilamentTenantMembers\Enums;

enum InvitationUnavailableReason: string
{
    /** Already accepted, expired, or no longer in the table (declined / revoked). */
    case NotPending = 'not_pending';

    /** The answering user's email is not the one the invitation was sent to. */
    case EmailMismatch = 'email_mismatch';
}
