<?php

namespace Guidance\FilamentTenantMembers\Livewire;

use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\TableComponent;
use Illuminate\Support\Facades\DB;

class ListMembers extends TableComponent
{
    public function table(Table $table): Table
    {
        $tenant = Filament::getTenant();
        $currentUserId = auth()->id();
        $roleEnum = FilamentTenantMembers::getRoleEnum();
        $currentRole = FilamentTenantMembers::getCurrentRole();

        $query = $tenant
            ? $tenant->users()->getQuery()
                ->select('users.*', 'organization_user.role as pivot_role')
                ->orderByRaw($roleEnum::orderBySql('organization_user.role'))
                ->orderBy('users.name')
            : FilamentTenantMembers::getUserModel()::query()->whereRaw('1 = 0');

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->weight(FontWeight::Medium)
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->weight(FontWeight::Light)
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('pivot_role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $roleEnum::tryFrom($state ?? '')?->getLabel() ?? $state ?? '-')
                    ->color(fn (?string $state) => $roleEnum::tryFrom($state ?? '')?->getColor() ?? 'gray')
                    ->grow(false),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('changeRole')
                        ->label('Change role')
                        ->icon('heroicon-o-user-circle')
                        ->modalHeading(fn ($record) => "Change role for {$record->name}")
                        ->modalSubmitActionLabel('Save')
                        ->schema([
                            Select::make('role')
                                ->label('New role')
                                ->options($roleEnum::assignableOptions())
                                ->required()
                                ->default(fn ($record) => $record->pivot_role),
                        ])
                        ->modalWidth(Width::Small)
                        ->action(function ($record, array $data) use ($roleEnum): void {
                            $tenant = Filament::getTenant();
                            $tenant?->users()->updateExistingPivot($record->id, [
                                'role' => $data['role'],
                            ]);

                            $role = $roleEnum::tryFrom($data['role']);

                            Notification::make()
                                ->title('Role updated')
                                ->body("{$record->name} is now " . ($role?->getLabel() ?? $data['role']) . '.')
                                ->success()
                                ->send();
                        }),
                    Action::make('transferOwnership')
                        ->label('Transfer ownership')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Transfer ownership')
                        ->modalDescription(fn ($record) => "Are you sure you want to transfer ownership to {$record->name}? You will be demoted to " . ($roleEnum::assignableOptions()[FilamentTenantMembers::getDefaultRole()] ?? FilamentTenantMembers::getDefaultRole()) . '.')
                        ->action(function ($record) use ($currentUserId, $roleEnum): void {
                            $tenant = Filament::getTenant();

                            if (! $tenant) {
                                return;
                            }

                            DB::transaction(function () use ($tenant, $record, $currentUserId, $roleEnum): void {
                                $tenant->users()->updateExistingPivot($record->id, [
                                    'role' => $roleEnum::ownerValue(),
                                ]);

                                $tenant->users()->updateExistingPivot($currentUserId, [
                                    'role' => FilamentTenantMembers::getDefaultRole(),
                                ]);
                            });

                            FilamentTenantMembers::flushCurrentRole();

                            Notification::make()
                                ->title('Ownership transferred')
                                ->body("{$record->name} is now the owner.")
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => $currentRole?->isProtected() ?? false),
                    Action::make('remove')
                        ->label('Remove')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Remove member')
                        ->modalDescription(fn ($record) => "Are you sure you want to remove {$record->name} from this organization?")
                        ->action(function ($record): void {
                            $tenant = Filament::getTenant();
                            $tenant?->users()->detach($record->id);

                            Notification::make()
                                ->title('Member removed')
                                ->body("{$record->name} has been removed from the organization.")
                                ->success()
                                ->send();
                        }),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Actions')
                    ->visible(function ($record) use ($currentUserId, $currentRole, $roleEnum): bool {
                        if ($record->id === $currentUserId) {
                            return false;
                        }

                        $targetRole = $roleEnum::tryFrom($record->pivot_role ?? '');

                        if ($targetRole?->isProtected()) {
                            return false;
                        }

                        return $currentRole?->canManageMembers() ?? false;
                    }),
                Action::make('leave')
                    ->label('Leave')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Leave organization')
                    ->modalDescription('Are you sure you want to leave this organization? You will lose access immediately.')
                    ->action(function () use ($currentUserId): void {
                        $tenant = Filament::getTenant();
                        $tenant?->users()->detach($currentUserId);

                        Notification::make()
                            ->title('You have left the organization.')
                            ->success()
                            ->send();

                        $this->redirect(Filament::getUrl());
                    })
                    ->visible(function ($record) use ($currentUserId, $currentRole): bool {
                        return $record->id === $currentUserId
                            && ! ($currentRole?->isProtected() ?? false);
                    }),
            ]);
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>{{ $this->table }}</div>
        HTML;
    }
}
