<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentBodyValidator;

test('comment body requires at least two characters of plain text', function (): void {
    expect(CommentBodyValidator::isValid(''))->toBeFalse()
        ->and(CommentBodyValidator::isValid('a'))->toBeFalse()
        ->and(CommentBodyValidator::isValid('ab'))->toBeTrue()
        ->and(CommentBodyValidator::isValid('<p>ab</p>'))->toBeTrue();
});

test('comment body accepts attachment-only rich editor content', function (): void {
    $html = '<p><img src="https://example.test/files/report.pdf" alt=""></p>';

    expect(CommentBodyValidator::plainTextLength($html))->toBe(0)
        ->and(CommentBodyValidator::hasAttachments($html))->toBeTrue()
        ->and(CommentBodyValidator::containsDocument($html))->toBeTrue()
        ->and(CommentBodyValidator::isValid($html))->toBeTrue();
});

test('comment body detects linked office documents and csv attachments', function (): void {
    $csv = '<p><a href="https://example.test/files/export.csv">export</a></p>';
    $doc = '<p><a href="/storage/files/brief.docx">brief</a></p>';

    expect(CommentBodyValidator::hasAttachments($csv))->toBeTrue()
        ->and(CommentBodyValidator::containsDocument($csv))->toBeTrue()
        ->and(CommentBodyValidator::hasAttachments($doc))->toBeTrue()
        ->and(CommentBodyValidator::containsDocument($doc))->toBeTrue();
});

test('comment body detects document content types embedded in rich editor html', function (): void {
    $html = '<p>{"contentType":"application/pdf","filename":"scan.pdf"}</p>';

    expect(CommentBodyValidator::hasAttachments($html))->toBeTrue()
        ->and(CommentBodyValidator::containsDocument($html))->toBeTrue();
});

test('comment body accepts short text when an attachment is present', function (): void {
    $html = '<p>a</p><p><img src="https://example.test/files/scan.png" alt="scan"></p>';

    expect(CommentBodyValidator::isValid($html))->toBeTrue();
});
