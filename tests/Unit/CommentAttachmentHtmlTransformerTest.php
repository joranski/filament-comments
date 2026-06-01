<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentAttachmentHtmlTransformer;
use Joranski\FilamentComments\Support\CommentContentRenderer;

test('document attachments render as download links instead of broken images', function (): void {
    $html = '<p>See attached</p><p><img src="https://example.test/files/report.pdf" alt="report.pdf"></p>';

    $rendered = CommentAttachmentHtmlTransformer::transform(html: $html);

    expect($rendered)
        ->toContain('fi-comment-attachment-link')
        ->toContain('href="https://example.test/files/report.pdf"')
        ->toContain('report.pdf')
        ->not->toContain('<img');
});

test('image attachments remain as images in comment html', function (): void {
    $html = '<p><img src="https://example.test/files/photo.jpg" alt="photo"></p>';

    $rendered = CommentAttachmentHtmlTransformer::transform(html: $html);

    expect($rendered)->toContain('<img')
        ->toContain('photo.jpg');
});

test('comment content renderer transforms document attachments for display', function (): void {
    $html = '<p><img src="https://example.test/files/specs.docx" alt="specs.docx"></p>';

    $rendered = (string) app(CommentContentRenderer::class)->render(html: $html);

    expect($rendered)
        ->toContain('fi-comment-attachment-link')
        ->toContain('specs.docx')
        ->not->toContain('<img');
});
