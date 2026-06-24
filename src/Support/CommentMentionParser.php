<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class CommentMentionParser
{
    /**
     * @return Collection<int, Model>
     */
    public function parseUsers(string $body): Collection
    {
        $ids = $this->parseUserIds($body);

        if ($ids === []) {
            return collect();
        }

        return CommentModels::mentionableUserQuery()
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * @return list<int>
     */
    public function parseUserIds(string $body): array
    {
        $ids = array_merge(
            $this->parseRichEditorMentionIds($body),
            $this->parsePlainTextMentionIds($body),
        );

        return array_values(array_unique(array_map(intval(...), $this->filterMentionableUserIds($ids))));
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    protected function filterMentionableUserIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return CommentModels::mentionableUserQuery()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    protected function parseRichEditorMentionIds(string $body): array
    {
        if (! str_contains($body, 'mention')) {
            return [];
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[@data-type="mention" and @data-id]');

        if ($nodes === false) {
            return [];
        }

        $ids = [];

        foreach ($nodes as $node) {
            $id = (int) $node->attributes?->getNamedItem('data-id')?->nodeValue;

            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    protected function parsePlainTextMentionIds(string $body): array
    {
        $plainText = html_entity_decode(strip_tags($body));

        if ($plainText === '') {
            return [];
        }

        $users = CommentModels::mentionableUserQuery()
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->get(['id', 'name'])
            ->sortByDesc(fn (Model $user): int => mb_strlen((string) $user->name))
            ->values();

        $matched = [];

        foreach ($users as $user) {
            $name = trim((string) $user->name);

            if ($name === '') {
                continue;
            }

            $pattern = '/@'.preg_quote($name, '/').'(?=\s|<|$|[.,!?;:])/iu';

            if (preg_match($pattern, $plainText) === 1) {
                $matched[] = (int) $user->id;
            }
        }

        return $matched;
    }
}
