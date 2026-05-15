<?php

namespace Guidance\FilamentTenantMembers\Enums;

use Guidance\FilamentTenantMembers\Contracts\RoleEnum;

enum DefaultRole: string implements RoleEnum
{
    case Owner = 'owner';
    case Admin = 'admin';
    case User = 'user';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::User => 'User',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Owner => 'warning',
            self::Admin => 'info',
            self::User => 'gray',
        };
    }

    public function canInviteMembers(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            default => false,
        };
    }

    public function canManageMembers(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            default => false,
        };
    }

    public function isProtected(): bool
    {
        return $this === self::Owner;
    }

    public static function ownerValue(): string
    {
        return self::Owner->value;
    }

    public static function orderBySql(string $column): string
    {
        $cases = collect(self::cases())
            ->map(fn (self $role, int $index) => "WHEN '{$role->value}' THEN {$index}")
            ->implode(' ');

        return "CASE {$column} {$cases} ELSE " . count(self::cases()) . ' END';
    }

    public static function assignableOptions(): array
    {
        return collect(self::cases())
            ->reject(fn (self $role) => $role->isProtected())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->getLabel()])
            ->all();
    }
}
