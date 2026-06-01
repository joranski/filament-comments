<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentComments\Attachments\DefaultCommentAttachmentHandler;
use Joranski\FilamentComments\Support\CommentAttachmentContext;
use Joranski\FilamentComments\Support\CommentAttachments;

beforeEach(function (): void {
    Storage::fake('public');
    config()->set('filament.default_filesystem_disk', 'public');
    config()->set('filament-comments.features.attachments', true);
    config()->set('filament-comments.attachments.handler', DefaultCommentAttachmentHandler::class);
    config()->set('filament-comments.attachments.disk', 'public');
    config()->set('filament-comments.attachments.directory', 'comment-attachments');
});

test('attachments are disabled by default', function (): void {
    config()->set('filament-comments.features.attachments', false);

    expect(CommentAttachments::enabled())->toBeFalse()
        ->and(CommentComposerFieldToolbar())->not->toContain(['attachFiles']);
});

test('comment composer toolbar includes attachFiles when attachments are enabled', function (): void {
    expect(CommentComposerFieldToolbar())->toContain(['attachFiles']);
});

test('default handler stores uploaded files on configured disk', function (): void {
    $handler = app(DefaultCommentAttachmentHandler::class);
    $context = new CommentAttachmentContext(composer: 'root');
    $file = UploadedFile::fake()->create('note.pdf', 100, 'application/pdf');

    $reflection = new ReflectionMethod($handler, 'storeUploadedFile');
    $path = $reflection->invoke($handler, $file, $context);

    Storage::disk('public')->assertExists($path);
});

test('default handler applies document mime types when accepted_file_types is null', function (): void {
    config()->set('filament-comments.attachments.accepted_file_types', null);

    $handler = app(DefaultCommentAttachmentHandler::class);
    $editor = \Filament\Forms\Components\RichEditor::make('body');

    $configured = $handler->configureRichEditor($editor, new CommentAttachmentContext(composer: 'root'));

    expect($configured->getFileAttachmentsAcceptedFileTypes())
        ->toContain('application/pdf')
        ->toContain('text/csv')
        ->toContain('image/*');
});

/**
 * @return list<list<string>>
 */
function CommentComposerFieldToolbar(): array
{
    return \Joranski\FilamentComments\Support\CommentComposerField::toolbarButtons(
        context: new CommentAttachmentContext(composer: 'root'),
    );
}
