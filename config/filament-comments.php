<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentGroups;

return [
    'comment_model' => \Joranski\FilamentComments\Models\Comment::class,
    'user_model' => null,

    'features' => [
        'replies' => true,
        'pins' => true,
        'mentions' => true,
        'search' => true,
        'edit' => true,
        'reply_notifications' => true,
    ],

    'excluded_groups' => [
        CommentGroups::DELAY,
        CommentGroups::CHAT,
    ],

    'mention_search_limit' => 20,

    // Max height (px) for the scrollable comments thread. Set to null to disable.
    'thread_max_height' => 1000,

    // Horizontal indent (px) for each reply nest level under a parent comment.
    'reply_indent_px' => 15,

    // Maximum reply nesting depth (root = 0; default allows depths 1–10).
    'max_reply_depth' => 10,

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | mode:
    |   auto     — use Laravel policies when registered; otherwise fallback rules
    |   policy   — always require policy checks (deny when no policy)
    |   fallback — ignore policies; use fallback rules only
    |
    | With Filament Shield, register CommentPolicy (see stubs) and keep mode "auto".
    | Without Shield, either publish the standalone policy stub or rely on fallback.
    |
    */
    'authorization' => [
        'mode' => 'auto',
        'fallback' => [
            'view_any' => true,
            'view' => true,
            'create' => true,
            'update_own' => true,
            'delete_own' => true,
            'pin' => false,
        ],
        'author_may_update_own' => true,
        'author_may_delete_own' => true,
    ],

    'commentable_urls' => [],
];
