<?php

namespace Tests\Models;

use Guidance\FilamentTenantMembers\Concerns\HasConfigurableKeyType;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Test stand-in User model. It uses the same HasConfigurableKeyType trait as the
 * package models so its primary key flips (bigint vs uuid) by the same config flag,
 * keeping the users table and the package's foreign keys in sync under both strategies.
 */
class User extends Authenticatable
{
    use HasConfigurableKeyType;

    protected $table = 'users';

    protected $guarded = [];

    /**
     * Mirror the framework-default `hashed` cast every real consumer applies to
     * `password`, so completePasswordSet's plaintext assignment is hashed on
     * save (the primitive relies on this cast, never hashing itself).
     */
    protected $casts = [
        'password' => 'hashed',
    ];
}
