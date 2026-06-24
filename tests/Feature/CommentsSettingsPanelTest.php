<?php

declare(strict_types=1);

use Joranski\FilamentComments\Filament\Settings\CommentsSettingsPanel;
use Joranski\FilamentComments\Services\FilamentCommentsSettings;
use Joranski\FilamentEmails\Contracts\FilamentPackageSettingsPanel;
use Joranski\FilamentEmails\Support\FilamentPackageSettingsRegistry;

test('comments settings panel implements package settings contract', function (): void {
    expect(CommentsSettingsPanel::class)
        ->toImplement(FilamentPackageSettingsPanel::class)
        ->and(CommentsSettingsPanel::id())->toBe('filament-comments')
        ->and(CommentsSettingsPanel::label())->toBe('Comments');
});

test('comments settings panel persists ui and ai toggles', function (): void {
    CommentsSettingsPanel::save([
        'compact_toolbar' => true,
        'compact_action_icons' => true,
        'ai_proofread_enabled' => true,
        'ai_proofread_default' => false,
        'ai_summarize_threads' => true,
    ]);

    $settings = app(FilamentCommentsSettings::class)->current();

    expect($settings->compact_toolbar)->toBeTrue()
        ->and($settings->compact_action_icons)->toBeTrue()
        ->and($settings->ai_proofread_enabled)->toBeTrue()
        ->and($settings->ai_proofread_default)->toBeFalse()
        ->and($settings->ai_summarize_threads)->toBeTrue();
});

test('comments settings panel registers on package settings registry when emails is present', function (): void {
    if (! class_exists(FilamentPackageSettingsRegistry::class)) {
        $this->markTestSkipped('joranski/filament-emails is not installed.');
    }

    $registry = app(FilamentPackageSettingsRegistry::class);

    expect($registry->find(CommentsSettingsPanel::id()))->toBe(CommentsSettingsPanel::class);
});
