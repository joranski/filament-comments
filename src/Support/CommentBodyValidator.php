<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

final class CommentBodyValidator
{
    /**
     * File extensions treated as document attachments in comment HTML.
     *
     * @var list<string>
     */
    public const DOCUMENT_EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'csv',
        'ppt',
        'pptx',
        'txt',
        'odt',
        'ods',
        'odp',
    ];

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
        if ($html === '' || $html === '0') {
            return false;
        }

        if (preg_match('/<(img|video|audio|iframe|embed|object)\b/i', $html) === 1) {
            return true;
        }

        if (preg_match('/data-type=["\'](?:image|file|attachment)["\']/i', $html) === 1) {
            return true;
        }

        if (preg_match('/class=["\'][^"\']*\battachment\b/i', $html) === 1) {
            return true;
        }

        if (static::containsLinkedDocument(html: $html)) {
            return true;
        }

        return static::containsDocumentContentType(html: $html);
    }

    /**
     * Detects images, PDFs, office documents, and other file attachments in RichEditor HTML.
     */
    public static function containsDocument(string $html): bool
    {
        return static::hasAttachments(html: $html);
    }

    protected static function containsLinkedDocument(string $html): bool
    {
        $extensions = implode('|', array_map(
            preg_quote(...),
            static::DOCUMENT_EXTENSIONS,
        ));

        if (preg_match('/href\s*=\s*["\'][^"\']*\.(?:'.$extensions.')\b/i', $html) === 1) {
            return true;
        }

        if (preg_match('/filename["\']:\s*["\'][^"\']*\.(?:'.$extensions.')\b/i', $html) === 1) {
            return true;
        }

        if (preg_match('/src\s*=\s*["\'][^"\']*\.(?:'.$extensions.')\b/i', $html) === 1) {
            return true;
        }

        return false;
    }

    protected static function containsDocumentContentType(string $html): bool
    {
        if (preg_match_all('/contentType["\']:\s*["\']?([^"\'&}\s,]+)/i', $html, $matches) < 1) {
            return false;
        }

        foreach ($matches[1] as $contentType) {
            $contentType = strtolower($contentType);

            if (str_starts_with($contentType, 'image/')) {
                return true;
            }

            if (in_array($contentType, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/csv',
                'application/csv',
                'text/plain',
            ], true)) {
                return true;
            }
        }

        return false;
    }
}
