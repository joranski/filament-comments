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

test('comment composer field reads toolbar buttons from config', function (): void {
    config()->set('filament-comments.features.attachments', false);
    config()->set('filament-comments.rich_editor.toolbar_buttons', [
        ['bold', 'italic'],
        ['link'],
    ]);

    expect(CommentComposerField::toolbarButtons())->toBe([
        ['bold', 'italic'],
        ['link'],
    ]);
});

test('flux toolbar maps configured buttons to flux editor items', function (): void {
    config()->set('filament-comments.features.attachments', true);

    expect(CommentComposerField::fluxToolbar())
        ->toBe('bold italic underline strike | link blockquote code bullet ordered | undo redo');
});

test('flux toolbar drops unknown and duplicate buttons and empty groups', function (): void {
    config()->set('filament-comments.rich_editor.toolbar_buttons', [
        ['bold', 'h2', 'h3', 'table'],
        ['attachFiles'],
        ['bold', 'redo'],
    ]);

    expect(CommentComposerField::fluxToolbar())->toBe('bold heading | redo');
});

test('comment composer field does not duplicate attachFiles when configured manually', function (): void {
    config()->set('filament-comments.features.attachments', true);
    config()->set('filament-comments.rich_editor.toolbar_buttons', [
        ['bold'],
        ['attachFiles'],
    ]);

    $buttons = CommentComposerField::toolbarButtons(
        context: new \Joranski\FilamentComments\Support\CommentAttachmentContext(composer: 'root'),
    );

    expect($buttons)->toBe([
        ['bold'],
        ['attachFiles'],
    ]);
});
