<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Support\HtmlString;

final class CommentContentRenderer
{
    /**
     * @param  list<int>|null  $mentionedUserIds
     */
    public function render(string $html, ?array $mentionedUserIds = null): HtmlString
    {
        $html = CommentAttachmentHtmlTransformer::transform(html: $html);
        $html = CommentBodyTransformers::transform(html: $html);

        if ($mentionedUserIds === null || $mentionedUserIds === []) {
            return new HtmlString($html);
        }

        $names = CommentModels::userQuery()
            ->whereIn('id', $mentionedUserIds)
            ->pluck('name', 'id');

        if ($names->isEmpty()) {
            return new HtmlString($html);
        }

        $rendered = $html;

        foreach ($names as $name) {
            if (! filled($name)) {
                continue;
            }

            $pattern = '/@'.preg_quote((string) $name, '/').'(?=\s|<|$|[.,!?;:])/iu';
            $replacement = '<span class="font-medium text-primary-600 dark:text-primary-400">@'.e((string) $name).'</span>';
            $rendered = preg_replace($pattern, $replacement, $rendered) ?? $rendered;
        }

        return new HtmlString($rendered);
    }
}
