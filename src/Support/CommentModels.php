<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class CommentModels
{
    public static function commentClass(): string
    {
        $class = config('filament-comments.comment_model');

        if (! is_string($class) || $class === '') {
            throw new RuntimeException('Configure filament-comments.comment_model in the host application.');
        }

        return $class;
    }

    public static function userClass(): string
    {
        $class = config('filament-comments.user_model') ?? config('auth.providers.users.model');

        if (! is_string($class) || $class === '') {
            throw new RuntimeException('Configure filament-comments.user_model or auth.providers.users.model.');
        }

        return $class;
    }

    /** @return Builder<Model> */
    public static function userQuery(): Builder
    {
        return self::userClass()::query();
    }
}
