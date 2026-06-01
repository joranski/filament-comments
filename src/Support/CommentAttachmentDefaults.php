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
}
