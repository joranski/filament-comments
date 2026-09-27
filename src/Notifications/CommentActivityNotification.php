<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Notifications;

use Illuminate\Notifications\Notification;
use Joranski\FilamentComments\Support\CommentNotificationMessage;

final class CommentActivityNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly array $payload) {}

    public static function fromMessage(CommentNotificationMessage $message): self
    {
        return new self([
            'format' => 'filament-comments',
            'kind' => $message->kind,
            'title' => $message->title,
            'body' => $message->bodyHtml(),
            'url' => $message->url,
            'comment_id' => $message->comment->getKey(),
            'commentable_type' => $message->commentable->getMorphClass(),
            'commentable_id' => $message->commentable->getKey(),
            'author_id' => $message->author->getAuthIdentifier(),
        ]);
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}
