<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Joranski\FilamentComments\Support\CommentAttachmentContext;

/**
 * Stores files attached to a comment composer and resolves them for display.
 *
 * Implementations are UI-agnostic: the comment panel uploads files through Livewire,
 * calls {@see store()} and embeds the returned reference in the comment body.
 */
interface CommentAttachmentHandler
{
    public function isEnabled(CommentAttachmentContext $context): bool;

    /**
     * Persist an uploaded file and return a reference (a URL or a disk path) that
     * {@see url()} can resolve.
     */
    public function store(UploadedFile $file, CommentAttachmentContext $context): string;

    /**
     * Resolve a reference returned by {@see store()} to a URL the browser can load.
     */
    public function url(string $reference, CommentAttachmentContext $context): ?string;

    public function afterCommentSaved(Model $comment, CommentAttachmentContext $context): void;

    public function beforeCommentDeleted(Model $comment): void;
}
