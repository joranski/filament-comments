<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class CommentMentionParser
{
    /**
     * @return Collection<int, Model>
     */
    public function parseUsers(string $body): Collection
    {
        $plainText = html_entity_decode(strip_tags($body));

        if ($plainText === '') {
            return collect();
        }

        $users = CommentModels::userQuery()
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->get(['id', 'name'])
            ->sortByDesc(fn (Model $user): int => mb_strlen((string) $user->name))
            ->values();

        $matched = collect();

        foreach ($users as $user) {
            $name = trim((string) $user->name);

            if ($name === '') {
                continue;
            }

            $pattern = '/@'.preg_quote($name, '/').'(?=\s|<|$|[.,!?;:])/iu';

            if (preg_match($pattern, $plainText) === 1) {
                $matched->put($user->id, $user);
            }
        }

        return $matched->values();
    }

    /**
     * @return list<int>
     */
    public function parseUserIds(string $body): array
    {
        return $this->parseUsers($body)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
