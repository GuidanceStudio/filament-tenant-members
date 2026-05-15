<?php

namespace Guidance\FilamentTenantMembers\Mail;

use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrganizationInvite $invite,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've been invited to join {$this->invite->organization->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'filament-tenant-members::mail.organization-invite',
        );
    }
}
