<?php

return [
    // Primary-key strategy for this package's tables (organizations, organization_user,
    // organization_invites) and its foreign keys to the users table:
    //   'id'   — auto-incrementing bigint keys (default; unchanged behaviour).
    //   'uuid' — UUIDv7 string keys (use when your users table is UUID-keyed).
    'key_type' => 'id',

    'models' => [
        'user' => \App\Models\User::class,
        'organization' => \Guidance\FilamentTenantMembers\Models\Organization::class,
    ],

    'role_enum' => \Guidance\FilamentTenantMembers\Enums\DefaultRole::class,
    'landlord_role_enum' => \Guidance\FilamentTenantMembers\Enums\DefaultLandlordRole::class,

    'panel_id' => 'organization',

    'default_role' => 'user',
    'invite_expires_days' => 7,
    'max_invites_per_batch' => 5,
    'resend_cooldown_minutes' => 5,
    'tenant_slug_attribute' => 'slug',

    'routes' => [
        'prefix' => 'invite',
        'middleware' => ['web', 'throttle:10,1'],
    ],
];
