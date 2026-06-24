<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

final class CommentModels
{
    public static function commentClass(): string
    {
        $class = config('filament-comments.comment_model');

        if (! is_string($class) || $class === '') {
            return \Joranski\FilamentComments\Models\Comment::class;
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

    /** @return Builder<Model> */
    public static function mentionableUserQuery(): Builder
    {
        return self::applyMentionUserScope(self::userQuery());
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function applyMentionUserScope(Builder $query): Builder
    {
        $scope = config('filament-comments.mention_user_scope');

        if (! is_string($scope) || $scope === '') {
            return $query;
        }

        $userClass = self::userClass();
        $scopeMethod = 'scope'.Str::studly($scope);

        if (! method_exists($userClass, $scopeMethod)) {
            return $query;
        }

        return $query->{$scope}();
    }
}
