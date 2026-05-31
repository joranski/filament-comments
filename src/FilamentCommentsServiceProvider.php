<?php

declare(strict_types=1);

namespace Joranski\FilamentComments;

// @package-candidate score=EXTRACTED signals=1,2,4,5 extracted=2026-05-18
// See docs/extraction-candidates.md — host owns Comment model, migrations, policies.

use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentCommentsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-comments')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations();
    }

    public function packageBooted(): void
    {
        Livewire::component('filament-comments.comment-panel', CommentPanel::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations/vendor/joranski/filament-comments'),
            ], 'filament-comments-migrations');

            $this->publishes([
                __DIR__.'/../stubs/CommentPolicy.php.stub' => app_path('Policies/CommentPolicy.php'),
            ], 'filament-comments-policy');

            $this->publishes([
                __DIR__.'/../stubs/CommentPolicyShield.php.stub' => app_path('Policies/CommentPolicy.php'),
            ], 'filament-comments-policy-shield');
        }
    }
}
