<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

/**
 * Mentionable-user lookups backing the composer `@` autocomplete.
 */
final class CommentMentionProvider
{
    /**
     * @return array<string, string>
     */
    public static function search(string $search, ?int $exceptUserId = null): array
    {
        $exceptUserId ??= auth()->id();

        $query = CommentModels::mentionableUserQuery()
            ->whereNotNull('name')
            ->where('name', '!=', '');

        if ($exceptUserId !== null) {
            $query->whereKeyNot($exceptUserId);
        }

        if (filled($search)) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return $query
            ->orderBy('name')
            ->limit((int) config('filament-comments.mention_search_limit', 20))
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public static function searchForAutocomplete(string $search = ''): array
    {
        return collect(self::search(search: $search))
            ->map(fn (string $name, int|string $id): array => [
                'id' => (int) $id,
                'name' => $name,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<string, string>
     */
    public static function labelsFor(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return CommentModels::mentionableUserQuery()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->all();
    }
}
