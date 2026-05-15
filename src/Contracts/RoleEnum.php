<?php

namespace Guidance\FilamentTenantMembers\Contracts;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

interface RoleEnum extends HasLabel, HasColor
{
    /** @return array<string, string> */
    public static function assignableOptions(): array;

    public static function orderBySql(string $column): string;

    public function canInviteMembers(): bool;

    public function canManageMembers(): bool;

    public function isProtected(): bool;

    /** Returns the value used for the organization creator role */
    public static function ownerValue(): string;
}
