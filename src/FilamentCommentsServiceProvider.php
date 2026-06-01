<?php

declare(strict_types=1);

namespace Joranski\FilamentComments;

// @package-candidate score=EXTRACTED signals=1,2,4,5 extracted=2026-05-18

use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Joranski\FilamentComments\Support\CommentAttachmentDefaults;
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
            ->hasViews();
    }

    public function packageBooted(): void
    {
        $this->ensureLivewirePreviewMimesForAttachments();

        Livewire::component('filament-comments.comment-panel', CommentPanel::class);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

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

    protected function ensureLivewirePreviewMimesForAttachments(): void
    {
        if (! (bool) config('filament-comments.features.attachments', false)) {
            return;
        }

        if ((bool) config('filament-comments.attachments.ensure_livewire_preview_mimes', true) === false) {
            return;
        }

        CommentAttachmentDefaults::mergeIntoLivewirePreviewMimes();
    }
}
