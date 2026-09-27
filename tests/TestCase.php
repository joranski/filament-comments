<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Tests;

use Flux\FluxServiceProvider;
use FluxPro\FluxProServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Joranski\FilamentComments\FilamentCommentsServiceProvider;
use Joranski\FilamentComments\Tests\Support\EnsureErrorBagHook;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        $providers = [
            LivewireServiceProvider::class,
            FluxServiceProvider::class,
            FluxProServiceProvider::class,
            FilamentCommentsServiceProvider::class,
        ];

        if (class_exists(\Spatie\Permission\PermissionServiceProvider::class)) {
            $providers[] = \Spatie\Permission\PermissionServiceProvider::class;
        }

        return $providers;
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('auth.providers.users.model', \User::class);
        $app['config']->set('filament-comments.user_model', \User::class);
        $app['config']->set('filament-comments.comment_model', \Joranski\FilamentComments\Models\Comment::class);
        $app['config']->set('filament-comments.authorization.mode', 'fallback');
        $app['config']->set('filament-comments.authorization.fallback.pin', true);
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('cache.default', 'array');

        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            $app['config']->set('permission.models.permission', \Spatie\Permission\Models\Permission::class);
            $app['config']->set('permission.models.role', \Spatie\Permission\Models\Role::class);
            $app['config']->set('permission.testing', true);
        }
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__.'/Support/Models.php';

        Livewire::componentHook(EnsureErrorBagHook::class);

        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
    }
}
