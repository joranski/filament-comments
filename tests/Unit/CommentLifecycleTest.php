<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentAttachmentContext;
use Joranski\FilamentComments\Support\CommentLifecycle;
use Joranski\FilamentComments\Support\CommentLifecycleEvent;
use Joranski\FilamentComments\Tests\Support\DeferCreateCommentLifecycleHook;

test('comment lifecycle defers create when a hook requests it', function (): void {
    config()->set('filament-comments.lifecycle.hooks', [
        DeferCreateCommentLifecycleHook::class,
    ]);

    $event = new CommentLifecycleEvent(
        commentable: null,
        body: '<p>please defer-me</p>',
        context: new CommentAttachmentContext(composer: 'root'),
    );

    $result = CommentLifecycle::beforeCreate($event);

    expect($result->defer)->toBeTrue()
        ->and($result->deferKey)->toBe('test-defer')
        ->and($result->metadata)->toBe(['reason' => 'testing']);
});

test('comment lifecycle resolves defer prompt views from config', function (): void {
    config()->set('filament-comments.lifecycle.defer_prompts', [
        'test-defer' => 'filament-comments::comment-panel',
    ]);

    expect(CommentLifecycle::deferPromptView(deferKey: 'test-defer'))
        ->toBe('filament-comments::comment-panel')
        ->and(CommentLifecycle::deferPromptView(deferKey: 'missing'))
        ->toBeNull();
});
