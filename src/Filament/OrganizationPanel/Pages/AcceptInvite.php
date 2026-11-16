<?php

namespace Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Guidance\FilamentTenantMembers\Enums\InvitationUnavailableReason;
use Guidance\FilamentTenantMembers\Exceptions\InvitationUnavailable;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Contracts\Support\Htmlable;

class AcceptInvite extends Page
{
    const ERROR_INVALID = 'invalid';
    const ERROR_EMAIL_MISMATCH = 'email_mismatch';

    public ?string $token = null;

    public ?OrganizationInvite $invite = null;

    public ?string $error = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected string $view = 'filament-tenant-members::filament.pages.accept-invite';

    public static function getRoutePath(Panel $panel): string
    {
        return '/accept-invite/{token}';
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'accept-invite';
    }

    public function mount(string $token): void
    {
        $this->token = $token;

        if (! auth()->check()) {
            session()->put('pending_invite_token', $token);

            $this->redirect(Filament::getLoginUrl(), navigate: false);

            return;
        }

        $this->invite = OrganizationInvite::byToken($token)
            ->with(['organization', 'user'])
            ->pending()
            ->first();

        if (! $this->invite) {
            $this->error = self::ERROR_INVALID;

            return;
        }

        if (! $this->invite->matchesUser(auth()->user())) {
            $this->error = self::ERROR_EMAIL_MISMATCH;
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Invitation';
    }

    public function getHeading(): string|Htmlable
    {
        return match ($this->error) {
            self::ERROR_INVALID => 'Invalid invitation',
            self::ERROR_EMAIL_MISMATCH => 'Email mismatch',
            default => 'You\'ve been invited',
        };
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->error ? self::subheadingFor($this->error) : null;
    }

    protected static function subheadingFor(string $error): string
    {
        return match ($error) {
            self::ERROR_EMAIL_MISMATCH => 'This invitation was sent to a different email address. Please log in with the correct account.',
            default => 'This invitation is invalid or has expired.',
        };
    }

    public function getViewData(): array
    {
        return [
            'invite' => $this->invite,
            'error' => $this->error,
        ];
    }

    public function acceptAction(): Action
    {
        return Action::make('accept')
            ->label('Accept invitation')
            ->icon('heroicon-o-check-circle')
            ->color('primary')
            ->action(function (): void {
                if (! $this->canRespondToInvite()) {
                    $this->notifyCannotRespond();

                    return;
                }

                try {
                    $this->invite->accept(auth()->user());
                } catch (InvitationUnavailable $e) {
                    $this->notifyCannotRespond($e->reason);

                    return;
                }

                Notification::make()
                    ->title('Invitation accepted')
                    ->body("You have joined {$this->invite->organization->name}.")
                    ->success()
                    ->send();

                $this->redirect(
                    Filament::getUrl($this->invite->organization),
                    navigate: false,
                );
            });
    }

    public function declineAction(): Action
    {
        return Action::make('decline')
            ->label('Decline')
            ->icon('heroicon-o-x-circle')
            ->color('gray')
            ->outlined()
            ->requiresConfirmation()
            ->modalHeading('Decline invitation')
            ->modalDescription('Are you sure? You can ask to be invited again later.')
            ->action(function (): void {
                if (! $this->canRespondToInvite()) {
                    $this->notifyCannotRespond();

                    return;
                }

                $organizationName = $this->invite->organization->name;

                try {
                    $this->invite->decline(auth()->user());
                } catch (InvitationUnavailable $e) {
                    $this->notifyCannotRespond($e->reason);

                    return;
                }

                Notification::make()
                    ->title('Invitation declined')
                    ->body("You have declined the invitation to {$organizationName}.")
                    ->send();

                $this->redirect(Filament::getUrl(), navigate: false);
            });
    }

    protected function canRespondToInvite(): bool
    {
        return $this->invite !== null && $this->error === null;
    }

    protected function notifyCannotRespond(?InvitationUnavailableReason $reason = null): void
    {
        $body = match ($reason) {
            InvitationUnavailableReason::NotPending => self::subheadingFor(self::ERROR_INVALID),
            InvitationUnavailableReason::EmailMismatch => self::subheadingFor(self::ERROR_EMAIL_MISMATCH),
            null => $this->getSubheading(),
        };

        Notification::make()
            ->title('This invitation is no longer available.')
            ->body($body)
            ->danger()
            ->send();
    }
}
