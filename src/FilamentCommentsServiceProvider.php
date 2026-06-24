<?php

declare(strict_types=1);

namespace Joranski\FilamentComments;

// @package-candidate score=EXTRACTED signals=1,2,4,5 extracted=2026-05-18

use Joranski\FilamentComments\Ai\NullCommentAiAssistant;
use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Joranski\FilamentComments\Contracts\CommentAiAssistant;
use Joranski\FilamentComments\Filament\Settings\CommentsSettingsPanel;
use Joranski\FilamentComments\Services\CommentAiProcessor;
use Joranski\FilamentComments\Services\FilamentCommentsSettings;
use Joranski\FilamentComments\Support\CommentAttachmentDefaults;
use Joranski\FilamentEmails\Support\FilamentPackageSettingsRegistry;
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

    public function packageRegistered(): void
    {
        $this->app->singleton(FilamentCommentsSettings::class);
        $this->app->singleton(CommentAiProcessor::class);

        $this->app->bind(CommentAiAssistant::class, function ($app): CommentAiAssistant {
            $class = config('filament-comments.ai.assistant', NullCommentAiAssistant::class);

            if (! is_string($class) || $class === '' || ! class_exists($class)) {
                return new NullCommentAiAssistant;
            }

            return $app->make($class);
        });
    }

    public function packageBooted(): void
    {
        $this->ensureLivewirePreviewMimesForAttachments();

        Livewire::component('filament-comments.comment-panel', CommentPanel::class);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->app->booted(function (): void {
            if (class_exists(FilamentPackageSettingsRegistry::class)) {
                app(FilamentPackageSettingsRegistry::class)->register(CommentsSettingsPanel::class);
            }
        });

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
