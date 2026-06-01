<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Model;
use Joranski\FilamentComments\Attachments\DefaultCommentAttachmentHandler;
use Joranski\FilamentComments\Attachments\NullCommentAttachmentHandler;
use Joranski\FilamentComments\Contracts\CommentAttachmentHandler;

final class CommentAttachments
{
    public static function handler(): CommentAttachmentHandler
    {
        $class = (string) config('filament-comments.attachments.handler', DefaultCommentAttachmentHandler::class);

        if (! is_subclass_of($class, CommentAttachmentHandler::class)) {
            return app(NullCommentAttachmentHandler::class);
        }

        return app($class);
    }

    public static function enabled(?CommentAttachmentContext $context = null): bool
    {
        if (! (bool) config('filament-comments.features.attachments', false)) {
            return false;
        }

        if (! (bool) config('filament-comments.attachments.enabled', true)) {
            return false;
        }

        if ($context === null) {
            return true;
        }

        return static::handler()->isEnabled(context: $context);
    }

    public static function configureRichEditor(
        RichEditor $editor,
        CommentAttachmentContext $context,
    ): RichEditor {
        if (! static::enabled(context: $context)) {
            return $editor;
        }

        return static::handler()->configureRichEditor(editor: $editor, context: $context);
    }

    public static function configureRichContentRenderer(
        RichContentRenderer $renderer,
        CommentAttachmentContext $context,
    ): RichContentRenderer {
        if (! static::enabled(context: $context)) {
            return $renderer;
        }

        return static::handler()->configureRichContentRenderer(renderer: $renderer, context: $context);
    }

    public static function afterCommentSaved(Model $comment, CommentAttachmentContext $context): void
    {
        if (! static::enabled(context: $context)) {
            return;
        }

        static::handler()->afterCommentSaved(comment: $comment, context: $context);
    }

    public static function beforeCommentDeleted(Model $comment): void
    {
        if (! (bool) config('filament-comments.features.attachments', false)) {
            return;
        }

        static::handler()->beforeCommentDeleted(comment: $comment);
    }
}
