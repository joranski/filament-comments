<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Joranski\FilamentComments\Contracts\CommentLifecycleHook;

final class CommentLifecycle
{
    public static function beforeCreate(CommentLifecycleEvent $event): CommentLifecycleResult
    {
        return static::dispatchBefore(
            method: 'beforeCreate',
            event: $event,
        );
    }

    public static function afterCreate(CommentLifecycleEvent $event): void
    {
        static::dispatchAfter(method: 'afterCreate', event: $event);
    }

    public static function beforeUpdate(CommentLifecycleEvent $event): CommentLifecycleResult
    {
        return static::dispatchBefore(
            method: 'beforeUpdate',
            event: $event,
        );
    }

    public static function afterUpdate(CommentLifecycleEvent $event): void
    {
        static::dispatchAfter(method: 'afterUpdate', event: $event);
    }

    public static function beforeDelete(CommentLifecycleEvent $event): CommentLifecycleResult
    {
        return static::dispatchBefore(
            method: 'beforeDelete',
            event: $event,
        );
    }

    public static function afterDelete(CommentLifecycleEvent $event): void
    {
        static::dispatchAfter(method: 'afterDelete', event: $event);
    }

    public static function deferPromptView(?string $deferKey): ?string
    {
        if ($deferKey === null || $deferKey === '') {
            return null;
        }

        $views = config('filament-comments.lifecycle.defer_prompts', []);

        if (! is_array($views)) {
            return null;
        }

        $view = $views[$deferKey] ?? null;

        return is_string($view) && $view !== '' ? $view : null;
    }

    protected static function dispatchBefore(
        string $method,
        CommentLifecycleEvent $event,
    ): CommentLifecycleResult {
        $result = CommentLifecycleResult::proceed();

        foreach (static::hooks() as $hookClass) {
            /** @var CommentLifecycleHook $hook */
            $hook = app($hookClass);
            $hookResult = $hook->{$method}($event);

            if (! $hookResult->proceed && ! $hookResult->defer) {
                return CommentLifecycleResult::abort();
            }

            if ($hookResult->defer) {
                return $hookResult;
            }

            if ($hookResult->metadata !== []) {
                $result = CommentLifecycleResult::proceed(
                    metadata: array_merge($result->metadata, $hookResult->metadata),
                );
            }
        }

        return $result;
    }

    protected static function dispatchAfter(string $method, CommentLifecycleEvent $event): void
    {
        foreach (static::hooks() as $hookClass) {
            /** @var CommentLifecycleHook $hook */
            $hook = app($hookClass);
            $hook->{$method}($event);
        }
    }

    /**
     * @return list<class-string<CommentLifecycleHook>>
     */
    protected static function hooks(): array
    {
        $hooks = config('filament-comments.lifecycle.hooks', []);

        if (! is_array($hooks)) {
            return [];
        }

        return array_values(array_filter(
            $hooks,
            fn (mixed $hook): bool => is_string($hook) && $hook !== '' && is_subclass_of($hook, CommentLifecycleHook::class),
        ));
    }
}
