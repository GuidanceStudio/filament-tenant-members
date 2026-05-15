<?php

namespace Guidance\FilamentTenantMembers\Filament\OrganizationPanel\Pages\Tenancy;

use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterOrganization extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Register Organization';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                        if (($get('slug') ?? '') !== Str::slug($old)) {
                            return;
                        }

                        $set('slug', Str::slug($state));
                    }),

                TextInput::make('slug')
                    ->required()
                    ->unique()
                    ->regex(FilamentTenantMembers::SLUG_REGEX)
                    ->validationMessages(['regex' => FilamentTenantMembers::SLUG_REGEX_MESSAGE]),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $organizationModel = FilamentTenantMembers::getOrganizationModel();
        $roleEnum = FilamentTenantMembers::getRoleEnum();

        $data['slug'] = $this->ensureUniqueSlug($data['slug']);

        return DB::transaction(function () use ($data, $organizationModel, $roleEnum) {
            $organization = $organizationModel::create($data);
            $organization->users()->attach(auth()->user(), ['role' => $roleEnum::ownerValue()]);

            return $organization;
        });
    }

    protected function ensureUniqueSlug(string $slug): string
    {
        $model = FilamentTenantMembers::getOrganizationModel();

        if (! $model::where('slug', $slug)->exists()) {
            return $slug;
        }

        do {
            $candidate = $slug . '-' . Str::random(5);
        } while ($model::where('slug', $candidate)->exists());

        return $candidate;
    }
}
