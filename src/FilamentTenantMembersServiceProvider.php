<?php

namespace Guidance\FilamentTenantMembers;

use Guidance\FilamentTenantMembers\Console\Commands\CleanupExpiredInvites;
use Guidance\FilamentTenantMembers\Events\OrganizationInviteCreated;
use Guidance\FilamentTenantMembers\Listeners\AcceptPendingInvite;
use Guidance\FilamentTenantMembers\Listeners\SendOrganizationInviteMail;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FilamentTenantMembersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/filament-tenant-members.php', 'filament-tenant-members');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filament-tenant-members');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Event::listen(OrganizationInviteCreated::class, SendOrganizationInviteMail::class);
        Event::listen(Login::class, AcceptPendingInvite::class);

        if ($this->app->runningInConsole()) {
            $this->commands([CleanupExpiredInvites::class]);

            $this->publishes([
                __DIR__ . '/../config/filament-tenant-members.php' => config_path('filament-tenant-members.php'),
            ], 'filament-tenant-members-config');

            $this->publishesMigrations([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'filament-tenant-members-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/filament-tenant-members'),
            ], 'filament-tenant-members-views');
        }
    }
}
