<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentComments\Attachments\DefaultCommentAttachmentHandler;
use Joranski\FilamentComments\Attachments\NullCommentAttachmentHandler;
use Joranski\FilamentComments\Support\CommentAttachmentContext;
use Joranski\FilamentComments\Support\CommentAttachments;
use Joranski\FilamentComments\Support\CommentComposerField;

beforeEach(function (): void {
    Storage::fake('comments-public');
    config()->set('filament-comments.features.attachments', true);
    config()->set('filament-comments.attachments.handler', DefaultCommentAttachmentHandler::class);
    config()->set('filament-comments.attachments.disk', 'comments-public');
    config()->set('filament-comments.attachments.directory', 'comment-attachments');
});

test('attachments are disabled by default', function (): void {
    config()->set('filament-comments.features.attachments', false);

    expect(CommentAttachments::enabled())->toBeFalse()
        ->and(CommentComposerField::toolbarButtons(context: new CommentAttachmentContext(composer: 'root')))
        ->not->toContain(['attachFiles']);
});

test('host rich editor toolbar includes attachFiles when attachments are enabled', function (): void {
    expect(CommentComposerField::toolbarButtons(context: new CommentAttachmentContext(composer: 'root')))
        ->toContain(['attachFiles']);
});

test('default handler stores uploads on the configured disk and resolves their url', function (): void {
    $handler = app(DefaultCommentAttachmentHandler::class);
    $context = new CommentAttachmentContext(composer: 'root');

    $path = $handler->store(file: UploadedFile::fake()->create('note.pdf', 100, 'application/pdf'), context: $context);

    Storage::disk('comments-public')->assertExists($path);

    expect($path)->toStartWith('comment-attachments/')
        ->and($handler->url(reference: $path, context: $context))->toBe(Storage::disk('comments-public')->url($path))
        ->and($handler->url(reference: 'https://cdn.test/a.png', context: $context))->toBe('https://cdn.test/a.png');
});

test('default handler falls back to the default filesystem disk', function (): void {
    Storage::fake('comments-default');
    config()->set('filament-comments.attachments.disk', null);
    config()->set('filesystems.default', 'comments-default');

    $path = app(DefaultCommentAttachmentHandler::class)->store(
        file: UploadedFile::fake()->image('photo.png'),
        context: new CommentAttachmentContext(composer: 'root'),
    );

    Storage::disk('comments-default')->assertExists($path);
});

test('storeAsHtml embeds the stored attachment url with the original file name', function (): void {
    $html = CommentAttachments::storeAsHtml(
        file: UploadedFile::fake()->create('Quarterly report.pdf', 10, 'application/pdf'),
        context: new CommentAttachmentContext(composer: 'root'),
    );

    expect($html)->toStartWith('<p><img src="')
        ->toContain('alt="Quarterly report.pdf"');
});

test('file rules default to document and image mime types', function (): void {
    config()->set('filament-comments.attachments.accepted_file_types', null);
    config()->set('filament-comments.attachments.max_size_kb', 2048);

    $rules = CommentAttachments::fileRules();

    expect($rules[0])->toBe('file')
        ->and($rules[1])->toStartWith('mimetypes:')->toContain('application/pdf')->toContain('image/*')->toContain('text/csv')
        ->and($rules[2])->toBe('max:2048');
});

test('an empty accepted type list allows any file type', function (): void {
    config()->set('filament-comments.attachments.accepted_file_types', []);
    config()->set('filament-comments.attachments.max_size_kb', null);

    expect(CommentAttachments::fileRules())->toBe(['file']);
});

test('an invalid handler class falls back to the null handler', function (): void {
    config()->set('filament-comments.attachments.handler', stdClass::class);

    expect(CommentAttachments::handler())->toBeInstanceOf(NullCommentAttachmentHandler::class);
});
