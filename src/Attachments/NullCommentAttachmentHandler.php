<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Attachments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Joranski\FilamentComments\Contracts\CommentAttachmentHandler;
use Joranski\FilamentComments\Support\CommentAttachmentContext;
use LogicException;

final class NullCommentAttachmentHandler implements CommentAttachmentHandler
{
    public function isEnabled(CommentAttachmentContext $context): bool
    {
        return false;
    }

    public function store(UploadedFile $file, CommentAttachmentContext $context): string
    {
        throw new LogicException('Comment attachments are disabled.');
    }

    public function url(string $reference, CommentAttachmentContext $context): ?string
    {
        return $reference;
    }

    public function afterCommentSaved(Model $comment, CommentAttachmentContext $context): void {}

    public function beforeCommentDeleted(Model $comment): void {}
}
