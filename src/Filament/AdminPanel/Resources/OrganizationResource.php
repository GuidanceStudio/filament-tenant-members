<?php

namespace Guidance\FilamentTenantMembers\Filament\AdminPanel\Resources;

use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Guidance\FilamentTenantMembers\Filament\AdminPanel\Resources\OrganizationResource\Pages;
use Filament\Forms\Components\TextInput;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    public static function getModel(): string
    {
        return FilamentTenantMembers::getOrganizationModel();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('users_count')
                    ->label('Members')
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
