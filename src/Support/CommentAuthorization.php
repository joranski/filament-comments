<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

final class CommentAuthorization
{
    public static function canViewAny(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can('viewAny', CommentModels::commentClass());
        }

        return (bool) config('filament-comments.authorization.fallback.view_any', true);
    }

    public static function canView(Model $comment, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (! self::canViewAny($user)) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can('view', $comment);
        }

        return (bool) config('filament-comments.authorization.fallback.view', true);
    }

    public static function canCreate(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can('create', CommentModels::commentClass());
        }

        return (bool) config('filament-comments.authorization.fallback.create', true);
    }

    public static function canUpdate(Model $comment, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            if ($user->can('update', $comment)) {
                return true;
            }

            return self::authorMayUpdateOwn() && self::isAuthor($comment, $user);
        }

        return (bool) config('filament-comments.authorization.fallback.update_own', true)
            && self::isAuthor($comment, $user);
    }

    public static function canDelete(Model $comment, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (method_exists($comment, 'hasReplies') && $comment->hasReplies()) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            if ($user->can('deleteAny', $comment)) {
                return true;
            }

            return $user->can('delete', $comment)
                && self::authorMayDeleteOwn()
                && self::isAuthor($comment, $user);
        }

        return (bool) config('filament-comments.authorization.fallback.delete_own', true)
            && self::isAuthor($comment, $user);
    }

    public static function canPin(Model $comment, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null || (method_exists($comment, 'isReply') && $comment->isReply())) {
            return false;
        }

        if (self::usesPolicyChecks()) {
            return $user->can('update', $comment);
        }

        return (bool) config('filament-comments.authorization.fallback.pin', false);
    }

    public static function canReply(Model $comment, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null || ! CommentThreadDepth::canAcceptReply($comment)) {
            return false;
        }

        return self::canCreate($user);
    }

    public static function hasRegisteredPolicy(): bool
    {
        return Gate::getPolicyFor(CommentModels::commentClass()) !== null;
    }

    public static function usesPolicyChecks(): bool
    {
        return match (self::authorizationMode()) {
            'policy' => true,
            'fallback' => false,
            default => self::hasRegisteredPolicy(),
        };
    }

    public static function authorizationMode(): string
    {
        $mode = (string) config('filament-comments.authorization.mode', 'auto');

        return in_array($mode, ['auto', 'policy', 'fallback'], true) ? $mode : 'auto';
    }

    public static function isAuthor(Model $comment, Authenticatable $user): bool
    {
        if (! isset($comment->user_id)) {
            return false;
        }

        return (int) $comment->user_id === (int) $user->getAuthIdentifier();
    }

    public static function authorMayUpdateOwn(): bool
    {
        return (bool) config('filament-comments.authorization.author_may_update_own', true);
    }

    public static function authorMayDeleteOwn(): bool
    {
        return (bool) config('filament-comments.authorization.author_may_delete_own', true);
    }

    /**
     * @param  Collection<int, Model>  $comments
     * @return Collection<int, Model>
     */
    public static function filterVisible(Collection $comments): Collection
    {
        return $comments
            ->filter(fn (Model $comment): bool => self::canView($comment))
            ->map(function (Model $comment): Model {
                if ($comment->relationLoaded('replies') && $comment->replies->isNotEmpty()) {
                    $comment->setRelation(
                        'replies',
                        self::filterVisible($comment->replies),
                    );
                }

                return $comment;
            })
            ->values();
    }
}
