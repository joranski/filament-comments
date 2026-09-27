<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Joranski\FilamentComments\Contracts\SendsCommentNotifications;

class CommentReplyNotifier
{
    public function __construct(private readonly SendsCommentNotifications $sender) {}

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

        $this->sender->send(
            recipient: $parentOwner,
            message: new CommentNotificationMessage(
                kind: CommentNotificationMessage::KIND_REPLY,
                title: __('Someone replied to your comment'),
                body: __('**:author** replied to your comment (“:parent”): :excerpt', [
                    'author' => CommentAuthor::displayName($author),
                    'parent' => str(strip_tags((string) $parent->comment))->limit(80)->toString(),
                    'excerpt' => str(strip_tags((string) $reply->comment))->limit(120)->toString(),
                ]),
                url: CommentContextResolver::urlFor($commentable),
                comment: $reply,
                commentable: $commentable,
                author: $author,
            ),
        );
    }
}
