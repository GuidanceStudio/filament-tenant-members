<?php

namespace Guidance\FilamentTenantMembers\Models;

use BackedEnum;
use Guidance\FilamentTenantMembers\FilamentTenantMembers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['token', 'organization_id', 'user_id', 'email', 'role', 'expires_at', 'accepted_at'])]
class OrganizationInvite extends Model
{
    protected function casts(): array
    {
        return [
            'role' => FilamentTenantMembers::getRoleEnum(),
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    public function scopeByToken(Builder $query, string $token): Builder
    {
        return $query->where('token', $token);
    }

    public function accept(Authenticatable $user): void
    {
        $this->update(['accepted_at' => now()]);

        $this->organization->users()->syncWithoutDetaching([
            $user->getAuthIdentifier() => [
                'role' => $this->role instanceof BackedEnum ? $this->role->value : $this->role,
            ],
        ]);
    }

    public function matchesUser(Authenticatable $user): bool
    {
        return strcasecmp((string) $user->email, $this->email) === 0;
    }

    public function isResendable(): bool
    {
        $cooldownMinutes = FilamentTenantMembers::getResendCooldownMinutes();

        return $this->updated_at->addMinutes($cooldownMinutes)->isPast();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(FilamentTenantMembers::getOrganizationModel());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(FilamentTenantMembers::getUserModel());
    }
}
