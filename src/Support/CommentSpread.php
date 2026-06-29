<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Joranski\FilamentComments\Contracts\CommentSpreadHandler;

final class CommentSpread
{
    public static function afterSaved(CommentLifecycleEvent $event): void
    {
        foreach (static::handlers() as $handlerClass) {
            /** @var CommentSpreadHandler $handler */
            $handler = app($handlerClass);

            if (! $handler->supports($event)) {
                continue;
            }

            $handler->spread($event);
        }
    }

    /**
     * @return list<class-string<CommentSpreadHandler>>
     */
    protected static function handlers(): array
    {
        $handlers = config('filament-comments.spread.handlers', []);

        if (! is_array($handlers)) {
            return [];
        }

        return array_values(array_filter(
            $handlers,
            fn (mixed $handler): bool => is_string($handler)
                && $handler !== ''
                && is_subclass_of($handler, CommentSpreadHandler::class),
        ));
    }
}
