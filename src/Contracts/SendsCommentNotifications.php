<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Contracts;

use Illuminate\Database\Eloquent\Model;
use Joranski\FilamentComments\Support\CommentNotificationMessage;

/**
 * Delivers mention / reply notifications. The default stores a plain Laravel database
 * notification; host apps may bind a sender that matches their notification bell format.
 */
interface SendsCommentNotifications
{
    public function send(Model $recipient, CommentNotificationMessage $message): void;
}
