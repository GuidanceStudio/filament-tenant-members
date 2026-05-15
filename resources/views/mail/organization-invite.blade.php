<x-mail::message>
# You've been invited

**{{ $invite->user->name }}** has invited you to join **{{ $invite->organization->name }}** as **{{ $invite->role->getLabel() }}**.

This invitation expires on {{ $invite->expires_at->format('M d, Y') }}.

<x-mail::button :url="route('invite.accept', $invite->token)">
Accept Invitation
</x-mail::button>

If you weren't expecting this invitation, you can ignore this email.
</x-mail::message>
