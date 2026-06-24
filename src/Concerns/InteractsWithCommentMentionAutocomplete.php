<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Concerns;

use Joranski\FilamentComments\Support\CommentMentionProvider;
use Livewire\Attributes\Renderless;

trait InteractsWithCommentMentionAutocomplete
{
    /**
     * @return list<array{id: int, name: string}>
     */
    #[Renderless]
    public function searchMentionUsers(string $query = ''): array
    {
        return CommentMentionProvider::searchForAutocomplete(search: $query);
    }

    /**
     * @param  array<int, array{id: mixed, char: string}>  $mentions
     * @return array<string, string>
     */
    #[Renderless]
    public function getCommentMentionLabelsForJs(array $mentions = []): array
    {
        if ($mentions === []) {
            return [];
        }

        $ids = collect($mentions)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return CommentMentionProvider::labelsFor(ids: $ids);
    }
}
