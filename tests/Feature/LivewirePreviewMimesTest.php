<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentAttachmentDefaults;

test('comment attachment defaults include document preview mimes for livewire', function (): void {
    expect(CommentAttachmentDefaults::previewMimes())
        ->toContain('pdf')
        ->toContain('docx')
        ->toContain('csv');
});

test('comment attachment defaults merge document preview mimes into livewire config', function (): void {
    config()->set('livewire.temporary_file_upload.preview_mimes', ['jpg', 'png']);

    CommentAttachmentDefaults::mergeIntoLivewirePreviewMimes();

    expect(config('livewire.temporary_file_upload.preview_mimes'))
        ->toContain('jpg')
        ->toContain('png')
        ->toContain('pdf');
});
