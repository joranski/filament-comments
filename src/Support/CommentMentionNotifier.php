<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class CommentMentionNotifier
{
    public function notify(Model $comment, Model $commentable, Authenticatable $author): void
    {
        $mentionedUserIds = $comment->mentioned_user_ids ?? [];

        if ($mentionedUserIds === []) {
            return;
        }

        $url = CommentContextResolver::urlFor($commentable);
        $authorName = CommentAuthor::displayName($author);
        $excerpt = str(strip_tags((string) $comment->comment))->limit(120)->toString();

        CommentModels::userQuery()
            ->whereIn('id', $mentionedUserIds)
            ->whereKeyNot($author->getAuthIdentifier())
            ->get()
            ->each(function (Model $mentionedUser) use ($authorName, $excerpt, $url): void {
                $notification = Notification::make()
                    ->title(__('You were mentioned in a comment'))
                    ->body(__('**:author** mentioned you: :excerpt', [
                        'author' => $authorName,
                        'excerpt' => $excerpt,
                    ]));

                if ($url !== null) {
                    $notification->actions([
                        Action::make('view')
                            ->label(__('View'))
                            ->url($url),
                    ]);
                }

                $notification->sendToDatabase($mentionedUser);
            });
    }
}
