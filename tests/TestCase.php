<?php

namespace Tests;

use Guidance\FilamentTenantMembers\FilamentTenantMembersServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Tests\Models\User;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * The primary-key strategy under test ('id' or 'uuid').
     */
    abstract protected function keyType(): string;

    protected function getPackageProviders($app): array
    {
        return [
            FilamentTenantMembersServiceProvider::class,
        ];
    }

    /**
     * Runs after the package provider is registered (so mergeConfigFrom has seeded
     * the defaults) and before the provider is booted / migrations run. Setting
     * key_type here guarantees it is in place before the conditional migrations execute.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('filament-tenant-members.models.user', User::class);
        $app['config']->set('filament-tenant-members.key_type', $this->keyType());
    }

    /**
     * Register the test users table. The package's own migrations are loaded by the
     * service provider's boot().
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
