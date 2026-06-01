<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Attachments;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Model;
use Joranski\FilamentComments\Contracts\CommentAttachmentHandler;
use Joranski\FilamentComments\Support\CommentAttachmentContext;

final class NullCommentAttachmentHandler implements CommentAttachmentHandler
{
    public function isEnabled(CommentAttachmentContext $context): bool
    {
        return false;
    }

    public function configureRichEditor(RichEditor $editor, CommentAttachmentContext $context): RichEditor
    {
        return $editor;
    }

    public function configureRichContentRenderer(
        RichContentRenderer $renderer,
        CommentAttachmentContext $context,
    ): RichContentRenderer {
        return $renderer;
    }

    public function afterCommentSaved(Model $comment, CommentAttachmentContext $context): void {}

    public function beforeCommentDeleted(Model $comment): void {}
}
