<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

final class CommentAttachmentDefaults
{
    /**
     * Default MIME types accepted by RichEditor file attachments when
     * filament-comments.attachments.accepted_file_types is null.
     *
     * @return list<string>
     */
    public static function acceptedFileTypes(): array
    {
        return [
            'image/*',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/csv',
            'application/csv',
            'text/plain',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
        ];
    }

    /**
     * File extensions Livewire must allow for temporary preview URLs when using
     * Filament RichEditor attachFiles (AttachFilesAction calls temporaryUrl()).
     *
     * @return list<string>
     */
    public static function previewMimes(): array
    {
        return [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'csv',
            'ppt',
            'pptx',
            'txt',
            'odt',
            'ods',
            'odp',
        ];
    }

    /**
     * Default RichEditor toolbar button groups.
     *
     * @return list<list<string>>
     */
    public static function toolbarButtons(): array
    {
        return [
            ['bold', 'italic', 'underline', 'strike'],
            ['link', 'blockquote', 'codeBlock', 'bulletList', 'orderedList'],
            ['undo', 'redo'],
        ];
    }

    public static function mergeIntoLivewirePreviewMimes(): void
    {
        $existing = config('livewire.temporary_file_upload.preview_mimes', []);

        if (! is_array($existing)) {
            $existing = [];
        }

        config([
            'livewire.temporary_file_upload.preview_mimes' => array_values(array_unique([
                ...$existing,
                ...static::previewMimes(),
            ])),
        ]);
    }
}
