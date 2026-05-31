<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class CommentAuthor
{
    public static function initials(?Authenticatable $user): string
    {
        $name = trim((string) ($user?->name ?? ''));

        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name) ?: [];

        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        return strtoupper(substr($parts[0], 0, 1).substr($parts[array_key_last($parts)], 0, 1));
    }

    public static function displayName(?Authenticatable $user): string
    {
        return filled($user?->name ?? null) ? (string) $user->name : __('System');
    }

    public static function formattedTimestamp(?Carbon $createdAt): ?string
    {
        return $createdAt?->timezone(config('app.timezone'))->format('M j, Y g:i A');
    }

    public static function canDelete(Model $comment): bool
    {
        return CommentAuthorization::canDelete($comment);
    }

    public static function canView(Model $comment): bool
    {
        return CommentAuthorization::canView($comment);
    }

    public static function canEdit(Model $comment): bool
    {
        return CommentAuthorization::canUpdate($comment);
    }

    public static function canPin(Model $comment): bool
    {
        return CommentAuthorization::canPin($comment);
    }

    public static function canReply(Model $comment): bool
    {
        return CommentAuthorization::canReply($comment);
    }
}
