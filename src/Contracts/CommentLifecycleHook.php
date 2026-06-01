<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Contracts;

use Joranski\FilamentComments\Support\CommentLifecycleEvent;
use Joranski\FilamentComments\Support\CommentLifecycleResult;

interface CommentLifecycleHook
{
    public function beforeCreate(CommentLifecycleEvent $event): CommentLifecycleResult;

    public function afterCreate(CommentLifecycleEvent $event): void;

    public function beforeUpdate(CommentLifecycleEvent $event): CommentLifecycleResult;

    public function afterUpdate(CommentLifecycleEvent $event): void;

    public function beforeDelete(CommentLifecycleEvent $event): CommentLifecycleResult;

    public function afterDelete(CommentLifecycleEvent $event): void;
}
