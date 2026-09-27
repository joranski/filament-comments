<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

/**
 * Builds and separates the attachment markup embedded in comment bodies.
 *
 * Attachments are stored as `<img>` tags (documents are turned into download cards by
 * {@see CommentAttachmentHtmlTransformer}). The Flux editor has no image node, so the
 * edit composer keeps existing attachments beside the editor and re-appends them on save.
 */
final class CommentBodyAttachments
{
    private const ATTACHMENT_PATTERN = '/<a\b[^>]*\bfi-comment-attachment-link\b[^>]*>.*?<\/a>|<img\b[^>]*>/is';

    public static function html(string $url, string $name): string
    {
        return '<p><img src="'.e($url).'" alt="'.e($name).'"></p>';
    }

    /**
     * @return array{body: string, attachments: list<string>}
     */
    public static function split(string $html): array
    {
        preg_match_all(self::ATTACHMENT_PATTERN, $html, $matches);

        $body = (string) preg_replace(self::ATTACHMENT_PATTERN, '', $html);
        $body = (string) preg_replace('/<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>/i', '', $body);

        return [
            'body' => trim($body),
            'attachments' => array_values($matches[0]),
        ];
    }

    /**
     * @param  list<string>  $attachments
     */
    public static function append(string $body, array $attachments): string
    {
        $markup = collect($attachments)
            ->filter(fn (string $attachment): bool => trim($attachment) !== '')
            ->map(fn (string $attachment): string => str_starts_with(ltrim($attachment), '<p') ? $attachment : '<p>'.$attachment.'</p>')
            ->implode('');

        return trim($body.$markup);
    }

    public static function label(string $attachmentHtml): string
    {
        foreach (['alt', 'src', 'href'] as $attribute) {
            if (preg_match('/\b'.$attribute.'\s*=\s*"([^"]*)"/i', $attachmentHtml, $matches) !== 1) {
                continue;
            }

            $value = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);

            if ($attribute !== 'alt') {
                $value = urldecode(basename((string) (parse_url($value, PHP_URL_PATH) ?: $value)));
            }

            if (trim($value) !== '') {
                return $value;
            }
        }

        $text = trim(strip_tags($attachmentHtml));

        return $text !== '' ? $text : __('Attachment');
    }
}
