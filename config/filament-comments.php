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
        'attachments' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Comment attachments (RichEditor file uploads)
    |--------------------------------------------------------------------------
    |
    | Enable features.attachments, then choose a handler class that implements
    | CommentAttachmentHandler. The default handler stores files on a Laravel disk.
    | Host apps can bind a custom handler (e.g. Spatie Media Library on the
    | commentable model) via attachments.handler.
    |
    */
    'attachments' => [
        'enabled' => true,
        'handler' => \Joranski\FilamentComments\Attachments\DefaultCommentAttachmentHandler::class,
        'disk' => null,
        'directory' => 'comment-attachments',
        'visibility' => 'public',
        'max_size_kb' => null,
        /*
         | When null, images, PDFs, Word/Excel/PowerPoint, CSV, and plain text are accepted.
         | Set to an empty array to allow all file types supported by Filament RichEditor.
         */
        'accepted_file_types' => null,
        'deduplicate' => false,
        /*
         | Filament RichEditor attachFiles uses Livewire temporary preview URLs.
         | When true, document extensions required for PDF/Office uploads are merged
         | into livewire.temporary_file_upload.preview_mimes at boot.
         */
        'ensure_livewire_preview_mimes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rich editor toolbar
    |--------------------------------------------------------------------------
    |
    | Configure toolbar button groups for comment composers. When attachments are
    | enabled, attachFiles is appended automatically unless already present or
    | append_attach_files_when_enabled is false.
    |
    | @see https://filamentphp.com/docs/forms/rich-editor#customizing-the-toolbar-buttons
    |
    */
    'rich_editor' => [
        'toolbar_buttons' => [
            ['bold', 'italic', 'underline', 'strike'],
            ['link', 'blockquote', 'codeBlock', 'bulletList', 'orderedList'],
            ['undo', 'redo'],
        ],
        'append_attach_files_when_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Comment lifecycle hooks
    |--------------------------------------------------------------------------
    |
    | Register classes implementing CommentLifecycleHook to run logic before or
    | after comments are created, updated, or deleted. Return defer() from
    | beforeCreate/beforeUpdate to pause and show a prompt view keyed in
    | lifecycle.defer_prompts (host app Blade views).
    |
    */
    'lifecycle' => [
        'hooks' => [
            // App\Support\Comments\PromptDocumentEmailHook::class,
        ],
        'defer_prompts' => [
            // 'document-email' => 'filament.resources.service-orders.prompts.document-email',
        ],
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
