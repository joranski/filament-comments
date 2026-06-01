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
        ->and(CommentBodyValidator::isValid($html))->toBeTrue();
});

test('comment body accepts short text when an attachment is present', function (): void {
    $html = '<p>a</p><p><img src="https://example.test/files/scan.png" alt="scan"></p>';

    expect(CommentBodyValidator::isValid($html))->toBeTrue();
});
