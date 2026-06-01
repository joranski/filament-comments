<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Tests\Support;

use Joranski\FilamentComments\Support\AbstractCommentLifecycleHook;
use Joranski\FilamentComments\Support\CommentLifecycleEvent;
use Joranski\FilamentComments\Support\CommentLifecycleResult;

final class DeferCreateCommentLifecycleHook extends AbstractCommentLifecycleHook
{
    public function beforeCreate(CommentLifecycleEvent $event): CommentLifecycleResult
    {
        if (str_contains($event->body, 'defer-me')) {
            return CommentLifecycleResult::defer(
                deferKey: 'test-defer',
                metadata: ['reason' => 'testing'],
            );
        }

        return CommentLifecycleResult::proceed();
    }

    public function afterCreate(CommentLifecycleEvent $event): void
    {
        if (($event->metadata['send_notice'] ?? false) === true) {
            cache()->put('comment-lifecycle-after-create', $event->comment?->getKey());
        }
    }
}
