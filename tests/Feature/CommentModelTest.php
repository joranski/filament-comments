<?php

declare(strict_types=1);

use Joranski\FilamentComments\Models\Comment;

test('comment model recalculates commentable ratings when rated comments change', function (): void {
    $commentable = \TestCommentable::factory()->create();
    $user = \User::factory()->create();

    $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Four stars</p>',
        'active' => true,
        'rating' => 4,
    ]);

    $commentable->refresh();

    expect($commentable->rating_tot)->toBe(1)
        ->and($commentable->rating_avg)->toBe(4.0);

    $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Two stars</p>',
        'active' => true,
        'rating' => 2,
    ]);

    $commentable->refresh();

    expect($commentable->rating_tot)->toBe(2)
        ->and($commentable->rating_avg)->toBe(3.0);
});

test('comment model exposes stable group constants', function (): void {
    expect(Comment::GROUP_DELAY)->toBe('delay')
        ->and(Comment::GROUP_CHAT)->toBe('chat');
});

test('comment replies hydrate as the configured extended comment model', function (): void {
    config(['filament-comments.comment_model' => ExtendedComment::class]);

    $commentable = \TestCommentable::factory()->create();
    $user = \User::factory()->create();

    $root = $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Root note</p>',
        'active' => true,
    ]);

    $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Reply note</p>',
        'active' => true,
        'parent_id' => $root->id,
    ]);

    $root->load('replies');

    expect($root)->toBeInstanceOf(ExtendedComment::class)
        ->and($root->replies)->toHaveCount(1)
        ->and($root->replies->first())->toBeInstanceOf(ExtendedComment::class);
});
