<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Notifications;

use Illuminate\Database\Eloquent\Model;
use Joranski\FilamentComments\Contracts\SendsCommentNotifications;
use Joranski\FilamentComments\Support\CommentNotificationMessage;

final class DatabaseCommentNotificationSender implements SendsCommentNotifications
{
    public function send(Model $recipient, CommentNotificationMessage $message): void
    {
        if (! method_exists($recipient, 'notify')) {
            return;
        }

        $recipient->notify(CommentActivityNotification::fromMessage(message: $message));
    }
}
