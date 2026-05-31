<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class CommentReplyNotifier
{
    public function notify(Model $reply, Model $parent, Model $commentable, Authenticatable $author): void
    {
        if (! (bool) config('filament-comments.features.reply_notifications', true)) {
            return;
        }

        if (! $reply->parent_id || $reply->parent_id !== $parent->id) {
            return;
        }

        $parentAuthorId = (int) $parent->user_id;

        if ($parentAuthorId <= 0 || $parentAuthorId === (int) $author->getAuthIdentifier()) {
            return;
        }

        $parentOwner = CommentModels::userQuery()->find($parentAuthorId);

        if (! $parentOwner instanceof Model) {
            return;
        }

        $url = CommentContextResolver::urlFor($commentable);
        $authorName = CommentAuthor::displayName($author);
        $parentExcerpt = str(strip_tags((string) $parent->comment))->limit(80)->toString();
        $replyExcerpt = str(strip_tags((string) $reply->comment))->limit(120)->toString();

        $notification = Notification::make()
            ->title(__('Someone replied to your comment'))
            ->body(__('**:author** replied to your comment (“:parent”): :excerpt', [
                'author' => $authorName,
                'parent' => $parentExcerpt,
                'excerpt' => $replyExcerpt,
            ]));

        if ($url !== null) {
            $notification->actions([
                Action::make('view')
                    ->label(__('View'))
                    ->url($url),
            ]);
        }

        $notification->sendToDatabase($parentOwner);
    }
}
