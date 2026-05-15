<?php

namespace Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
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
        return match ($this->error) {
            self::ERROR_INVALID => 'This invitation is invalid or has expired.',
            self::ERROR_EMAIL_MISMATCH => 'This invitation was sent to a different email address. Please log in with the correct account.',
            default => null,
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

                $this->invite->accept(auth()->user());

                Notification::make()
                    ->title('Invitation accepted')
                    ->body("You have joined {$this->invite->organization->name}.")
                    ->success()
                    ->send();

                $this->redirect(
                    Filament::getPanel()->getUrl($this->invite->organization),
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
                $this->invite->delete();

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

    protected function notifyCannotRespond(): void
    {
        Notification::make()
            ->title('This invitation is no longer available.')
            ->body($this->getSubheading())
            ->danger()
            ->send();
    }
}
