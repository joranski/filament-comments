<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Attachments;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentComments\Contracts\CommentAttachmentHandler;
use Joranski\FilamentComments\Support\CommentAttachmentContext;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class DefaultCommentAttachmentHandler implements CommentAttachmentHandler
{
    public function isEnabled(CommentAttachmentContext $context): bool
    {
        return (bool) config('filament-comments.attachments.enabled', true);
    }

    public function configureRichEditor(RichEditor $editor, CommentAttachmentContext $context): RichEditor
    {
        $editor = $editor
            ->fileAttachmentsDisk(fn (): ?string => $this->diskName())
            ->fileAttachmentsDirectory(fn (): ?string => $this->directory(context: $context))
            ->fileAttachmentsVisibility(fn (): ?string => $this->visibility())
            ->saveUploadedFileAttachmentUsing(
                fn (TemporaryUploadedFile|UploadedFile $file): string => $this->storeUploadedFile(
                    file: $file,
                    context: $context,
                ),
            )
            ->getFileAttachmentUrlUsing(
                fn (string $file): string => $this->urlForStoredFile(path: $file),
            );

        $acceptedTypes = config('filament-comments.attachments.accepted_file_types');

        if (is_array($acceptedTypes) && $acceptedTypes !== []) {
            $editor->fileAttachmentsAcceptedFileTypes($acceptedTypes);
        }

        $maxSize = config('filament-comments.attachments.max_size_kb');

        if (is_numeric($maxSize)) {
            $editor->fileAttachmentsMaxSize((int) $maxSize);
        }

        if (
            (bool) config('filament-comments.attachments.deduplicate', false)
            && RichEditor::hasMacro('deduplicateAttachments')
        ) {
            $editor->deduplicateAttachments();
        }

        return $editor;
    }

    public function configureRichContentRenderer(
        RichContentRenderer $renderer,
        CommentAttachmentContext $context,
    ): RichContentRenderer {
        $disk = $this->diskName();

        if ($disk !== null) {
            $renderer->fileAttachmentsDisk($disk);
        }

        return $renderer;
    }

    public function afterCommentSaved(Model $comment, CommentAttachmentContext $context): void {}

    public function beforeCommentDeleted(Model $comment): void {}

    protected function storeUploadedFile(
        TemporaryUploadedFile|UploadedFile $file,
        CommentAttachmentContext $context,
    ): string {
        $diskName = $this->diskName();
        $directory = $this->directory(context: $context);
        $path = $file->store($directory, ['disk' => $diskName]);

        if ($this->visibility() === 'public') {
            rescue(
                callback: fn (): mixed => Storage::disk($diskName)->setVisibility($path, 'public'),
                report: false,
            );
        }

        return $path;
    }

    protected function urlForStoredFile(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk($this->diskName())->url($path);
    }

    protected function diskName(): ?string
    {
        $disk = config('filament-comments.attachments.disk');

        return filled($disk) ? (string) $disk : config('filament.default_filesystem_disk');
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
