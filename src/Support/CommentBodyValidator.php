<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

final class CommentBodyValidator
{
    public static function isValid(string $body, int $minTextLength = 2): bool
    {
        $body = trim($body);

        if ($body === '') {
            return false;
        }

        if (static::hasAttachments(html: $body)) {
            return true;
        }

        return static::plainTextLength(html: $body) >= $minTextLength;
    }

    public static function plainTextLength(string $html): int
    {
        return strlen(trim(strip_tags($html)));
    }

    public static function hasAttachments(string $html): bool
    {
        if (preg_match('/<(img|video|audio|iframe|embed|object)\b/i', $html) === 1) {
            return true;
        }

        if (preg_match('/data-type=["\'](?:image|file|attachment)["\']/i', $html) === 1) {
            return true;
        }

        return preg_match('/class=["\'][^"\']*\battachment\b/i', $html) === 1;
    }
}
