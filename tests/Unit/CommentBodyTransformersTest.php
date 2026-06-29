<?php

declare(strict_types=1);

use Joranski\FilamentComments\Contracts\CommentBodyTransformer;
use Joranski\FilamentComments\Support\CommentBodyTransformers;
use Joranski\FilamentComments\Support\CommentContentRenderer;

test('comment body transformers run during content rendering', function (): void {
    config()->set('filament-comments.body_transformers', [
        TestTrackingLinkTransformer::class,
    ]);

    $html = (new CommentContentRenderer)->render('<p>Track 1Z999AA10123456784</p>')->toHtml();

    expect($html)->toContain('data-test-tracking-link');
});

final class TestTrackingLinkTransformer implements CommentBodyTransformer
{
    public function transform(string $html): string
    {
        return str_replace('1Z999AA10123456784', '<span data-test-tracking-link>1Z999AA10123456784</span>', $html);
    }
}

test('comment body transformers noop when none are configured', function (): void {
    config()->set('filament-comments.body_transformers', []);

    $html = (new CommentContentRenderer)->render('<p>plain</p>')->toHtml();

    expect($html)->toBe('<p>plain</p>');
});
