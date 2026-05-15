<?php

return [
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
