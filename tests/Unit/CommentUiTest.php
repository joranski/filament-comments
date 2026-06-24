<?php

declare(strict_types=1);

use Joranski\FilamentComments\Services\FilamentCommentsSettings;
use Joranski\FilamentComments\Support\CommentUi;

test('comment ui reads compact flags from config when settings service is not bound', function (): void {
    config([
        'filament-comments.ui.compact_toolbar' => true,
        'filament-comments.ui.compact_action_icons' => false,
    ]);

    expect(CommentUi::compactToolbar())->toBeTrue()
        ->and(CommentUi::compactActionIcons())->toBeFalse()
        ->and(CommentUi::actionButtonSize())->toBe('sm')
        ->and(CommentUi::panelClasses())->toBe(['fi-comments-ui-compact-toolbar']);
});

test('comment ui reads compact flags from persisted settings', function (): void {
    app(FilamentCommentsSettings::class)->update([
        'compact_toolbar' => false,
        'compact_action_icons' => true,
        'ai_proofread_enabled' => false,
        'ai_proofread_default' => true,
        'ai_summarize_threads' => false,
    ]);

    expect(CommentUi::compactToolbar())->toBeFalse()
        ->and(CommentUi::compactActionIcons())->toBeTrue()
        ->and(CommentUi::actionButtonSize())->toBe('xs')
        ->and(CommentUi::panelClasses())->toBe(['fi-comments-ui-compact-actions']);
});

test('comment ui compact profile forces condensed toolbar, actions, and panel class', function (): void {
    expect(CommentUi::compactToolbar(true))->toBeTrue()
        ->and(CommentUi::compactActionIcons(true))->toBeTrue()
        ->and(CommentUi::isCondensed(true))->toBeTrue()
        ->and(CommentUi::actionButtonSize(true))->toBe('xs')
        ->and(CommentUi::rootAvatarSize(true))->toBe('xs')
        ->and(CommentUi::panelClasses(true))->toContain('fi-comments-ui-condensed')
        ->and(CommentUi::panelClasses(true))->toContain('fi-comments-ui-compact-toolbar')
        ->and(CommentUi::panelClasses(true))->toContain('fi-comments-ui-compact-actions');
});

test('comment ui without compact profile defers to global settings only', function (): void {
    config([
        'filament-comments.ui.compact_toolbar' => false,
        'filament-comments.ui.compact_action_icons' => false,
    ]);

    expect(CommentUi::panelClasses(null))->toBe([])
        ->and(CommentUi::isCondensed(null))->toBeFalse();
});
