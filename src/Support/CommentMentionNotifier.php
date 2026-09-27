<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Joranski\FilamentComments\Contracts\SendsCommentNotifications;

class CommentMentionNotifier
{
    public function __construct(private readonly SendsCommentNotifications $sender) {}

    public function notify(Model $comment, Model $commentable, Authenticatable $author): void
    {
        $mentionedUserIds = $comment->mentioned_user_ids ?? [];

        if ($mentionedUserIds === []) {
            return;
        }

        $url = CommentContextResolver::urlFor($commentable);
        $authorName = CommentAuthor::displayName($author);
        $excerpt = str(strip_tags((string) $comment->comment))->limit(120)->toString();

        $recipients = CommentModels::userQuery()->whereIn('id', $mentionedUserIds);

        if (! (bool) config('filament-comments.notifications.notify_self_mentions', false)) {
            $recipients->whereKeyNot($author->getAuthIdentifier());
        }

        $recipients->get()->each(function (Model $mentionedUser) use ($comment, $commentable, $author, $authorName, $excerpt, $url): void {
            $this->sender->send(
                recipient: $mentionedUser,
                message: new CommentNotificationMessage(
                    kind: CommentNotificationMessage::KIND_MENTION,
                    title: __('You were mentioned in a comment'),
                    body: __('**:author** mentioned you: :excerpt', [
                        'author' => $authorName,
                        'excerpt' => $excerpt,
                    ]),
                    url: $url,
                    comment: $comment,
                    commentable: $commentable,
                    author: $author,
                ),
            );
        });
    }
}
