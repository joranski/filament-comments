<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Filament\Forms\Components\RichEditor\MentionProvider;

final class CommentMentionProvider
{
    public static function make(): MentionProvider
    {
        return MentionProvider::make('@')
            ->getSearchResultsUsing(fn (string $search): array => self::search(search: $search))
            ->getLabelsUsing(fn (array $ids): array => self::labelsFor(ids: $ids))
            ->searchPrompt(__('Mention someone…'))
            ->searchingMessage(__('Searching users…'))
            ->noSearchResultsMessage(__('No users found.'))
            ->noItemsMessage(__('No users available.'));
    }

    /**
     * @return array<string, string>
     */
    public static function search(string $search, ?int $exceptUserId = null): array
    {
        $exceptUserId ??= auth()->id();

        $query = CommentModels::userQuery()
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

        return CommentModels::userQuery()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->all();
    }
}
