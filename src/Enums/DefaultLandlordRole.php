<?php

namespace Guidance\FilamentTenantMembers\Enums;

use Filament\Support\Contracts\HasLabel;

enum DefaultLandlordRole: string implements HasLabel
{
    case Admin = 'admin';
    case User = 'user';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::User => 'User',
        };
    }
}
