<?php

namespace Guidance\FilamentTenantMembers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Switches a model between auto-incrementing bigint keys (default) and UUIDv7
 * string keys at runtime, driven by config('filament-tenant-members.key_type').
 *
 * Backward-compatible by design: with the default 'id' the model behaves exactly
 * like a stock auto-incrementing Eloquent model (no generated ids, keyType 'int',
 * incrementing true), so existing consumers are unaffected.
 */
trait HasConfigurableKeyType
{
    public static function bootHasConfigurableKeyType(): void
    {
        static::creating(function (Model $model) {
            if (! static::usesUuidKeys()) {
                return;
            }

            foreach ($model->uniqueIds() as $column) {
                if (empty($model->{$column})) {
                    $model->{$column} = (string) Str::uuid7();
                }
            }
        });
    }

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return static::usesUuidKeys() ? [$this->getKeyName()] : [];
    }

    public function getKeyType(): string
    {
        return static::usesUuidKeys() ? 'string' : 'int';
    }

    public function getIncrementing(): bool
    {
        return ! static::usesUuidKeys();
    }

    public static function usesUuidKeys(): bool
    {
        return config('filament-tenant-members.key_type', 'id') === 'uuid';
    }
}
