<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

abstract class AbstractCommentLifecycleHook implements \Joranski\FilamentComments\Contracts\CommentLifecycleHook
{
    public function beforeCreate(CommentLifecycleEvent $event): CommentLifecycleResult
    {
        return CommentLifecycleResult::proceed();
    }

    public function afterCreate(CommentLifecycleEvent $event): void {}

    public function beforeUpdate(CommentLifecycleEvent $event): CommentLifecycleResult
    {
        return CommentLifecycleResult::proceed();
    }

    public function afterUpdate(CommentLifecycleEvent $event): void {}

    public function beforeDelete(CommentLifecycleEvent $event): CommentLifecycleResult
    {
        return CommentLifecycleResult::proceed();
    }

    public function afterDelete(CommentLifecycleEvent $event): void {}
}
