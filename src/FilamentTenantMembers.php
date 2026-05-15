<?php

namespace Guidance\FilamentTenantMembers;

use Filament\Facades\Filament;
use Guidance\FilamentTenantMembers\Contracts\RoleEnum;

class FilamentTenantMembers
{
    const SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    const SLUG_REGEX_MESSAGE = 'The slug may only contain lowercase letters, numbers, and hyphens.';

    protected static ?RoleEnum $currentRoleCache = null;

    protected static bool $currentRoleResolved = false;

    public static function getCurrentRole(): ?RoleEnum
    {
        if (static::$currentRoleResolved) {
            return static::$currentRoleCache;
        }

        static::$currentRoleResolved = true;

        $tenant = Filament::getTenant();

        if (! $tenant) {
            return static::$currentRoleCache = null;
        }

        $pivotRole = $tenant->users()
            ->where('users.id', auth()->id())
            ->first()
            ?->pivot
            ?->role;

        if (! $pivotRole) {
            return static::$currentRoleCache = null;
        }

        $roleEnum = static::getRoleEnum();

        return static::$currentRoleCache = $roleEnum::tryFrom($pivotRole);
    }

    public static function flushCurrentRole(): void
    {
        static::$currentRoleCache = null;
        static::$currentRoleResolved = false;
    }

    /** @return class-string */
    public static function getRoleEnum(): string
    {
        return config('filament-tenant-members.role_enum');
    }

    /** @return class-string */
    public static function getLandlordRoleEnum(): string
    {
        return config('filament-tenant-members.landlord_role_enum');
    }

    /** @return class-string */
    public static function getUserModel(): string
    {
        return config('filament-tenant-members.models.user');
    }

    /** @return class-string */
    public static function getOrganizationModel(): string
    {
        return config('filament-tenant-members.models.organization');
    }

    public static function getDefaultRole(): string
    {
        return config('filament-tenant-members.default_role');
    }

    public static function getInviteExpiresDays(): int
    {
        return config('filament-tenant-members.invite_expires_days', 7);
    }

    public static function getResendCooldownMinutes(): int
    {
        return config('filament-tenant-members.resend_cooldown_minutes', 5);
    }

    public static function getMaxInvitesPerBatch(): int
    {
        return config('filament-tenant-members.max_invites_per_batch', 5);
    }

    public static function getPanelId(): string
    {
        return config('filament-tenant-members.panel_id', 'organization');
    }

    public static function getAcceptInviteUrl(string $token): string
    {
        $panel = Filament::getPanel(static::getPanelId());
        $path = $panel->getPath();
        $prefix = $path !== '' ? "/{$path}" : '';

        return "{$prefix}/accept-invite/{$token}";
    }

    public static function getTenantSlugAttribute(): string
    {
        return config('filament-tenant-members.tenant_slug_attribute', 'slug');
    }
}
