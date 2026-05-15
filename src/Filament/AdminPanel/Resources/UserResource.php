<?php

namespace Guidance\FilamentTenantMembers\Filament\AdminPanel\Resources;

use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Guidance\FilamentTenantMembers\Filament\AdminPanel\Resources\UserResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getModel(): string
    {
        return FilamentTenantMembers::getUserModel();
    }

    public static function form(Schema $schema): Schema
    {
        $landlordRoleEnum = FilamentTenantMembers::getLandlordRoleEnum();

        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->required()
                    ->email()
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->label('System Role')
                    ->options(
                        collect($landlordRoleEnum::cases())
                            ->mapWithKeys(fn ($role) => [$role->value => $role->getLabel()])
                            ->all()
                    )
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $landlordRoleEnum = FilamentTenantMembers::getLandlordRoleEnum();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Not verified')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(
                        collect($landlordRoleEnum::cases())
                            ->mapWithKeys(fn ($role) => [$role->value => $role->getLabel()])
                            ->all()
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
