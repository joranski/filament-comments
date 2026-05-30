<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentGroups;

return [
    'comment_model' => null,
    'user_model' => null,

    'features' => [
        'replies' => true,
        'pins' => true,
        'mentions' => true,
        'search' => true,
        'edit' => true,
    ],

    'excluded_groups' => [
        CommentGroups::DELAY,
        CommentGroups::CHAT,
    ],

    'commentable_urls' => [],
];
