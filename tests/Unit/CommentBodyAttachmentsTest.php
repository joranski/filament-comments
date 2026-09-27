<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentAttachmentHtmlTransformer;
use Joranski\FilamentComments\Support\CommentBodyAttachments;

test('split separates images and document cards from the editable text', function (): void {
    $card = CommentAttachmentHtmlTransformer::transform('<img src="https://cdn.test/docs/report.pdf" alt="report.pdf">');

    $parts = CommentBodyAttachments::split('<p>Hello</p><p><img src="https://cdn.test/a.png" alt="a.png"></p><p>'.$card.'</p>');

    expect($parts['body'])->toBe('<p>Hello</p>')
        ->and($parts['attachments'])->toHaveCount(2)
        ->and($parts['attachments'][0])->toBe('<img src="https://cdn.test/a.png" alt="a.png">')
        ->and($parts['attachments'][1])->toContain('fi-comment-attachment-link');
});

test('append wraps attachments in paragraphs after the body', function (): void {
    expect(CommentBodyAttachments::append('<p>Text</p>', ['<img src="x.png">', '<p><img src="y.png"></p>', ' ']))
        ->toBe('<p>Text</p><p><img src="x.png"></p><p><img src="y.png"></p>');
});

test('html escapes the url and file name', function (): void {
    expect(CommentBodyAttachments::html(url: 'https://cdn.test/a.png?x=1&y="2"', name: 'a <b>.png'))
        ->toBe('<p><img src="https://cdn.test/a.png?x=1&amp;y=&quot;2&quot;" alt="a &lt;b&gt;.png"></p>');
});

test('label prefers alt text, then the file name from the url', function (): void {
    $card = CommentAttachmentHtmlTransformer::transform('<img src="https://cdn.test/docs/My%20Report.pdf" alt="">');

    expect(CommentBodyAttachments::label('<img src="https://cdn.test/a.png" alt="Scan">'))->toBe('Scan')
        ->and(CommentBodyAttachments::label('<img src="https://cdn.test/files/photo.jpg">'))->toBe('photo.jpg')
        ->and(CommentBodyAttachments::label($card))->toBe('My Report.pdf');
});
