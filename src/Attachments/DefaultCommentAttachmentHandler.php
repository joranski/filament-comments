<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Attachments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentComments\Contracts\CommentAttachmentHandler;
use Joranski\FilamentComments\Support\CommentAttachmentContext;

/**
 * Stores attachments on a filesystem disk (`filament-comments.attachments.disk`,
 * falling back to `filesystems.default`) and embeds their public URL.
 */
final class DefaultCommentAttachmentHandler implements CommentAttachmentHandler
{
    public function isEnabled(CommentAttachmentContext $context): bool
    {
        return (bool) config('filament-comments.attachments.enabled', true);
    }

    public function store(UploadedFile $file, CommentAttachmentContext $context): string
    {
        $diskName = $this->diskName();
        $path = (string) $file->store($this->directory(context: $context) ?? '', ['disk' => $diskName]);

        if ($this->visibility() === 'public') {
            rescue(
                callback: fn (): mixed => Storage::disk($diskName)->setVisibility($path, 'public'),
                report: false,
            );
        }

        return $path;
    }

    public function url(string $reference, CommentAttachmentContext $context): ?string
    {
        if (str_starts_with($reference, 'http://') || str_starts_with($reference, 'https://')) {
            return $reference;
        }

        return Storage::disk($this->diskName())->url($reference);
    }

    public function afterCommentSaved(Model $comment, CommentAttachmentContext $context): void {}

    public function beforeCommentDeleted(Model $comment): void {}

    protected function diskName(): string
    {
        $disk = config('filament-comments.attachments.disk');

        return filled($disk) ? (string) $disk : (string) config('filesystems.default');
    }

    protected function directory(CommentAttachmentContext $context): ?string
    {
        $directory = config('filament-comments.attachments.directory', 'comment-attachments');

        if (is_callable($directory)) {
            return $directory($context);
        }

        return filled($directory) ? (string) $directory : null;
    }

    protected function visibility(): ?string
    {
        $visibility = config('filament-comments.attachments.visibility', 'public');

        return filled($visibility) ? (string) $visibility : null;
    }
}
