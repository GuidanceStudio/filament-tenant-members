<?php

namespace Guidance\FilamentTenantMembers\Concerns;

use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Guidance\FilamentTenantMembers\Models\OrganizationInvite;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait IsOrganization
{
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(FilamentTenantMembers::getUserModel())
            ->withPivot('role')
            ->withTimestamps();
    }

    public function invites(): HasMany
    {
        return $this->hasMany(OrganizationInvite::class);
    }
}
