<?php

namespace Guidance\FilamentTenantMembers\Concerns;

use Filament\Panel;
use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasOrganizations
{
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(FilamentTenantMembers::getOrganizationModel())
            ->withPivot('role')
            ->withTimestamps();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->organizations;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->organizations()->whereKey($tenant)->exists();
    }
}
