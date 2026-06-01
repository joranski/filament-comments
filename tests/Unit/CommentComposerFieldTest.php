<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentComposerField;

test('comment composer field exposes shared rich editor toolbar without attachments', function (): void {
    config()->set('filament-comments.features.attachments', false);

    expect(CommentComposerField::toolbarButtons())->toBe([
        ['bold', 'italic', 'underline', 'strike'],
        ['link', 'blockquote', 'codeBlock', 'bulletList', 'orderedList'],
        ['undo', 'redo'],
    ]);
});
