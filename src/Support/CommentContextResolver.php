<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Database\Eloquent\Model;

final class CommentContextResolver
{
    public static function urlFor(Model $commentable): ?string
    {
        $resolver = config('filament-comments.commentable_urls.'.get_class($commentable));

        if (! is_callable($resolver)) {
            return null;
        }

        return (string) $resolver($commentable);
    }
}
