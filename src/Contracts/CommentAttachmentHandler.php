<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Contracts;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Model;
use Joranski\FilamentComments\Support\CommentAttachmentContext;

interface CommentAttachmentHandler
{
    public function isEnabled(CommentAttachmentContext $context): bool;

    public function configureRichEditor(RichEditor $editor, CommentAttachmentContext $context): RichEditor;

    public function configureRichContentRenderer(
        RichContentRenderer $renderer,
        CommentAttachmentContext $context,
    ): RichContentRenderer;

    public function afterCommentSaved(Model $comment, CommentAttachmentContext $context): void;

    public function beforeCommentDeleted(Model $comment): void;
}
