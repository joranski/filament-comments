<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Concerns;

use Joranski\FilamentComments\Support\CommentMentionProvider;

trait InteractsWithCommentMentionAutocomplete
{
    /**
     * @return list<array{id: int, name: string}>
     */
    public function searchMentionUsers(string $query = ''): array
    {
        return CommentMentionProvider::searchForAutocomplete(search: $query);
    }
}
