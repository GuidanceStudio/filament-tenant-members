<?php

namespace Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages;

use BackedEnum;
use Guidance\FilamentTenantMembers\Events\OrganizationInviteCreated;
use Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Clusters\Settings;
use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Guidance\FilamentTenantMembers\Livewire\ListInvitations;
use Guidance\FilamentTenantMembers\Livewire\ListMembers;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;

class Members extends Page
{
    protected static ?string $cluster = Settings::class;

    public int $activeTab = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 2;

    public function getTitle(): string|Htmlable
    {
        return 'Members';
    }

    public static function canAccess(): bool
    {
        $currentRole = FilamentTenantMembers::getCurrentRole();

        return $currentRole !== null;
    }

    protected function getHeaderActions(): array
    {
        $tenant = Filament::getTenant();
        $roleEnum = FilamentTenantMembers::getRoleEnum();
        $currentRole = FilamentTenantMembers::getCurrentRole();
        $maxInvites = FilamentTenantMembers::getMaxInvitesPerBatch();

        return [
            Action::make('invite')
                ->label('Invite Users')
                ->icon('heroicon-o-user-plus')
                ->visible(fn (): bool => $currentRole?->canInviteMembers() ?? false)
                ->schema([
                    Repeater::make('emailAddresses')
                        ->label('Email addresses')
                        ->hiddenLabel()
                        ->minItems(1)
                        ->maxItems($maxInvites)
                        ->defaultItems(1)
                        ->deletable(fn ($state): bool => is_array($state) && count($state) > 1)
                        ->reorderable(false)
                        ->addActionLabel('Add another')
                        ->schema([
                            Grid::make()
                                ->columns(3)
                                ->schema([
                                    TextInput::make('email')
                                        ->required()
                                        ->columnSpan(2)
                                        ->placeholder('email@example.com')
                                        ->email()
                                        ->maxLength(254)
                                        ->distinct()
                                        ->rule(
                                            Rule::unique(OrganizationInvite::class, 'email')
                                                ->where('organization_id', $tenant?->id)
                                                ->whereNull('accepted_at')
                                                ->where(fn ($query) => $query->where('expires_at', '>', now()))
                                        )
                                        ->validationMessages([
                                            'unique' => 'This email already has a pending invitation.',
                                        ])
                                        ->rules([
                                            fn () => function (string $attribute, mixed $value, \Closure $fail) use ($tenant) {
                                                if ($tenant?->users()->where('email', $value)->exists()) {
                                                    $fail('This email belongs to an existing member.');
                                                }
                                            },
                                        ]),
                                    Select::make('role')
                                        ->label('Role')
                                        ->options($roleEnum::assignableOptions())
                                        ->required()
                                        ->default(FilamentTenantMembers::getDefaultRole()),
                                ]),
                        ]),
                ])
                ->modalHeading('Invite Users')
                ->modalDescription('Invite new members to your organization by email.')
                ->modalSubmitActionLabel('Send invitations')
                ->action(fn (array $data) => $this->createInvites($data)),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $tenant = Filament::getTenant();

        return $schema->components([
            Tabs::make()
                ->livewireProperty('activeTab')
                ->tabs([
                    Tab::make('Members')
                        ->icon('heroicon-m-users')
                        ->schema([
                            Livewire::make(ListMembers::class)->key('list-members'),
                        ]),
                    Tab::make('Pending Invitations')
                        ->icon('heroicon-m-envelope')
                        ->badge(
                            OrganizationInvite::query()
                                ->where('organization_id', $tenant?->id)
                                ->pending()
                                ->count() ?: null
                        )
                        ->schema([
                            Livewire::make(ListInvitations::class)->key('list-invitations'),
                        ]),
                ])
                ->contained(false),
        ]);
    }

    protected function createInvites(array $data): void
    {
        $emailAddresses = $data['emailAddresses'] ?? [];
        $tenant = Filament::getTenant();
        $expiresDays = FilamentTenantMembers::getInviteExpiresDays();

        if (! $tenant) {
            return;
        }

        $invites = DB::transaction(function () use ($emailAddresses, $tenant, $expiresDays): array {
            $created = [];

            foreach ($emailAddresses as $item) {
                $emailAddress = $item['email'] ?? null;
                $role = $item['role'] ?? FilamentTenantMembers::getDefaultRole();

                if (! $emailAddress) {
                    continue;
                }

                $exists = OrganizationInvite::query()
                    ->where('organization_id', $tenant->id)
                    ->where('email', $emailAddress)
                    ->pending()
                    ->lockForUpdate()
                    ->exists();

                if ($exists) {
                    continue;
                }

                $created[] = OrganizationInvite::create([
                    'organization_id' => $tenant->id,
                    'user_id' => auth()->id(),
                    'email' => $emailAddress,
                    'token' => Str::uuid(),
                    'role' => $role,
                    'expires_at' => now()->addDays($expiresDays),
                ]);
            }

            return $created;
        });

        foreach ($invites as $invite) {
            OrganizationInviteCreated::dispatch($invite);
        }

        $count = count($invites);

        if ($count > 0) {
            Notification::make()
                ->title($count === 1
                    ? 'Invitation sent successfully.'
                    : "{$count} invitations sent successfully.")
                ->success()
                ->send();

            $this->dispatch('invites-changed');
        } else {
            Notification::make()
                ->title('No invitations sent.')
                ->body('All provided emails already have pending invitations.')
                ->warning()
                ->send();
        }
    }

    #[On('invites-changed')]
    public function refreshContent(): void {}
}
