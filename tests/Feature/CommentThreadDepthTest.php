<?php

declare(strict_types=1);

use Joranski\FilamentComments\Models\Comment;
use Joranski\FilamentComments\Support\CommentThreadDepth;

test('comment thread depth treats roots as zero', function (): void {
    $commentable = \TestCommentable::factory()->create();
    $user = \User::factory()->create();

    $root = $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Root</p>',
        'active' => true,
    ]);

    expect(CommentThreadDepth::depth($root))->toBe(0)
        ->and(CommentThreadDepth::canAcceptReply($root))->toBeTrue();
});

test('comment thread depth walks parent chain', function (): void {
    config(['filament-comments.max_reply_depth' => 10]);

    $commentable = \TestCommentable::factory()->create();
    $user = \User::factory()->create();

    $root = $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Root</p>',
        'active' => true,
    ]);

    $reply = $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Reply</p>',
        'active' => true,
        'parent_id' => $root->id,
    ]);

    $nested = $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Nested</p>',
        'active' => true,
        'parent_id' => $reply->id,
    ]);

    expect(CommentThreadDepth::depth($reply))->toBe(1)
        ->and(CommentThreadDepth::depth($nested))->toBe(2)
        ->and(CommentThreadDepth::canAcceptReply($nested))->toBeTrue();
});

test('comment thread depth stops accepting replies at configured max', function (): void {
    config(['filament-comments.max_reply_depth' => 2]);

    $commentable = \TestCommentable::factory()->create();
    $user = \User::factory()->create();

    $root = $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Root</p>',
        'active' => true,
    ]);

    $depthOne = $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Depth one</p>',
        'active' => true,
        'parent_id' => $root->id,
    ]);

    $depthTwo = $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Depth two</p>',
        'active' => true,
        'parent_id' => $depthOne->id,
    ]);

    expect(CommentThreadDepth::canAcceptReply($depthOne))->toBeTrue()
        ->and(CommentThreadDepth::canAcceptReply($depthTwo))->toBeFalse();
});

test('comment thread depth builds cumulative content indent css', function (): void {
    config(['filament-comments.reply_indent_px' => 15]);

    expect(CommentThreadDepth::contentIndentCss(0))->toBeNull()
        ->and(CommentThreadDepth::contentIndentCss(1))->toBe('calc(1rem + 2rem + 0.75rem + 15px)')
        ->and(CommentThreadDepth::contentIndentCss(2))->toBe('calc(1rem + 2rem + 0.75rem + 15px + 1.5rem + 0.75rem + 15px)');
});
