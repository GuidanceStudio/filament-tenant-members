<?php

use Guidance\FilamentTenantMembers\Http\Controllers\AcceptInviteController;
use Illuminate\Support\Facades\Route;

Route::get(
    config('filament-tenant-members.routes.prefix', 'invite') . '/{token}',
    AcceptInviteController::class
)
    ->middleware(config('filament-tenant-members.routes.middleware', ['web', 'throttle:10,1']))
    ->name('invite.accept');
