<?php

namespace Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages\Settings;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Clusters\Settings;
use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rule;

class GeneralSettings extends Page
{
    protected static ?string $cluster = Settings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 9999;

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return 'General';
    }

    public function getTitle(): string|Htmlable
    {
        return 'General Settings';
    }

    public function mount(): void
    {
        $this->form->fill(
            Filament::getTenant()->only(['name', 'slug']),
        );
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $tenant = Filament::getTenant();

        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('slug')
                    ->required()
                    ->regex(FilamentTenantMembers::SLUG_REGEX)
                    ->rules([Rule::unique($tenant->getTable(), 'slug')->ignore($tenant->getKey())])
                    ->validationMessages(['regex' => FilamentTenantMembers::SLUG_REGEX_MESSAGE]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Filament::getTenant()->update($data);

        Notification::make()
            ->title('Saved successfully.')
            ->success()
            ->send();
    }
}
