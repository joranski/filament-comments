<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentComposerField;

test('comment composer field exposes shared rich editor toolbar', function (): void {
    expect(CommentComposerField::toolbarButtons())->toBe([
        ['bold', 'italic', 'underline', 'strike'],
        ['link', 'blockquote', 'codeBlock', 'bulletList', 'orderedList'],
        ['undo', 'redo'],
    ]);
});
