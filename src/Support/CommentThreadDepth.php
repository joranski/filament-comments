<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Database\Eloquent\Model;

final class CommentThreadDepth
{
    public static function maxReplyDepth(): int
    {
        return max(1, (int) config('filament-comments.max_reply_depth', 10));
    }

    public static function depth(Model $comment): int
    {
        if ($comment->parent_id === null) {
            return 0;
        }

        $depth = 0;
        $current = $comment;
        $seen = [];
        $limit = self::maxReplyDepth() + 1;

        while ($current->parent_id !== null && $depth < $limit) {
            if (isset($seen[$current->id])) {
                break;
            }

            $seen[$current->id] = true;
            $depth++;

            if ($current->relationLoaded('parent') && $current->parent instanceof Model) {
                $current = $current->parent;

                continue;
            }

            $current = CommentModels::commentClass()::query()
                ->select(['id', 'parent_id'])
                ->find($current->parent_id);

            if ($current === null) {
                break;
            }
        }

        return $depth;
    }

    public static function canAcceptReply(Model $comment): bool
    {
        return self::depth($comment) < self::maxReplyDepth();
    }

    public static function replyIndentPx(): int
    {
        return max(1, (int) config('filament-comments.reply_indent_px', 15));
    }

    public static function contentIndentCss(int $nestLevel): ?string
    {
        if ($nestLevel <= 0) {
            return null;
        }

        $parts = ['1rem', '2rem', '0.75rem', self::replyIndentPx().'px'];

        for ($level = 2; $level <= $nestLevel; $level++) {
            array_push($parts, '1.5rem', '0.75rem', self::replyIndentPx().'px');
        }

        return 'calc('.implode(' + ', $parts).')';
    }

    public static function rootAvatarCenterCss(): string
    {
        return 'calc(1rem + 1rem - 1px)';
    }

    public static function replyAvatarCenterCss(int $nestLevel): string
    {
        $indent = self::contentIndentCss($nestLevel);

        return $indent === null
            ? self::rootAvatarCenterCss()
            : "calc({$indent} + 0.75rem - 1px)";
    }

    public static function threadSpineLeftCss(int $nestLevel): string
    {
        if ($nestLevel <= 1) {
            return self::rootAvatarCenterCss();
        }

        return self::replyAvatarCenterCss($nestLevel - 1);
    }
}
