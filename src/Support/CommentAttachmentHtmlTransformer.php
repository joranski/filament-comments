<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

final class CommentAttachmentHtmlTransformer
{
    /**
     * @var list<string>
     */
    private const IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
        'bmp',
        'avif',
    ];

    public static function transform(string $html): string
    {
        if ($html === '' || stripos($html, '<img') === false) {
            return $html;
        }

        $transformed = preg_replace_callback(
            pattern: '/<img\b[^>]*>/i',
            callback: static fn (array $matches): string => static::replaceImageTag(tag: $matches[0]),
            subject: $html,
        );

        return is_string($transformed) ? $transformed : $html;
    }

    public static function isImageAttachment(string $src, string $alt = ''): bool
    {
        foreach ([$src, $alt] as $value) {
            $extension = static::extensionFrom(value: $value);

            if ($extension === null) {
                continue;
            }

            if (in_array($extension, self::IMAGE_EXTENSIONS, strict: true)) {
                return true;
            }

            if (in_array($extension, CommentBodyValidator::DOCUMENT_EXTENSIONS, strict: true)) {
                return false;
            }
        }

        if (preg_match('/\.pdf(?:[?#]|$)/i', $src) === 1) {
            return false;
        }

        if (preg_match('/\.(?:'.implode('|', CommentBodyValidator::DOCUMENT_EXTENSIONS).')(?:[?#]|$)/i', $src) === 1) {
            return false;
        }

        return true;
    }

    protected static function replaceImageTag(string $tag): string
    {
        $src = static::extractAttribute(tag: $tag, name: 'src');

        if ($src === null || $src === '') {
            return $tag;
        }

        $alt = static::extractAttribute(tag: $tag, name: 'alt') ?? '';

        if (static::isImageAttachment(src: $src, alt: $alt)) {
            return $tag;
        }

        return static::renderAttachmentLink(
            href: $src,
            label: static::resolveLabel(src: $src, alt: $alt),
        );
    }

    protected static function renderAttachmentLink(string $href, string $label): string
    {
        $href = e($href);
        $label = e($label);

        return <<<HTML
<a href="{$href}" target="_blank" rel="noopener noreferrer" class="fi-comment-attachment-link not-prose inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-medium text-zinc-800 no-underline hover:bg-zinc-100 dark:border-white/10 dark:bg-white/5 dark:text-zinc-100 dark:hover:bg-white/10">
<svg class="size-5 shrink-0 text-zinc-500 dark:text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
<span>{$label}</span>
</a>
HTML;
    }

    protected static function resolveLabel(string $src, string $alt): string
    {
        $path = parse_url($src, PHP_URL_PATH);

        if (is_string($path) && $path !== '') {
            $basename = basename($path);

            if ($basename !== '' && $basename !== '/' && str_contains($basename, '.')) {
                return urldecode($basename);
            }
        }

        if ($alt !== '' && $alt !== '0') {
            return $alt;
        }

        $extension = static::extensionFrom(value: $src);

        if ($extension !== null) {
            return strtoupper($extension).' '.__('file');
        }

        return __('Attachment');
    }

    protected static function extensionFrom(string $value): ?string
    {
        $path = parse_url($value, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            $path = $value;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : null;
    }

    protected static function extractAttribute(string $tag, string $name): ?string
    {
        if (preg_match('/\b'.preg_quote($name, '/').'\s*=\s*"([^"]*)"/i', $tag, $matches) === 1) {
            return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        if (preg_match("/\b".preg_quote($name, '/')."\s*=\s*'([^']*)'/i", $tag, $matches) === 1) {
            return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        }

        return null;
    }
}
