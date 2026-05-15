<?php

namespace Guidance\FilamentTenantMembers\Livewire;

use Guidance\FilamentTenantMembers\Events\OrganizationInviteCreated;
use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Livewire\Attributes\On;

class ListInvitations extends TableComponent
{
    #[On('invites-changed')]
    public function refresh(): void {}

    public function table(Table $table): Table
    {
        $tenant = Filament::getTenant();
        $expiresDays = FilamentTenantMembers::getInviteExpiresDays();

        return $table
            ->query(
                OrganizationInvite::query()
                    ->with('user')
                    ->where('organization_id', $tenant?->getKey())
                    ->pending()
            )
            ->columns([
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge(),
                TextColumn::make('user.name')
                    ->label('Invited by')
                    ->placeholder('-'),
                TextColumn::make('expires_at')
                    ->label('Expires at')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->actions([
                Action::make('resend')
                    ->icon('heroicon-o-paper-airplane')
                    ->label('Resend')
                    ->requiresConfirmation()
                    ->modalHeading('Resend invitation')
                    ->modalDescription(fn ($record) => "Resend the invitation to {$record->email}?")
                    ->visible(fn ($record) => $record->isResendable())
                    ->action(function ($record) use ($expiresDays): void {
                        if (! $record->isResendable()) {
                            Notification::make()
                                ->title('Please wait before resending.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $record->update([
                            'expires_at' => now()->addDays($expiresDays),
                        ]);

                        OrganizationInviteCreated::dispatch($record);

                        Notification::make()
                            ->title('Invitation resent')
                            ->body("Invitation resent to {$record->email}.")
                            ->success()
                            ->send();
                    }),
                ActionGroup::make([
                    DeleteAction::make()
                        ->label('Cancel invitation')
                        ->after(fn () => $this->dispatch('invites-changed')),
                ]),
            ]);
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>{{ $this->table }}</div>
        HTML;
    }
}
