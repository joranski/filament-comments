@php
    use Joranski\FilamentComments\Support\CommentUi;

    $uiContext = $this->uiCompactProfileContext();
@endphp

@if (CommentUi::compactToolbar($uiContext))
    <style>
        .fi-comments-ui-compact-toolbar .fi-comments-editor ui-toolbar > div {
            height: 2rem;
            padding: 0.25rem;
            gap: 0.25rem;
        }

        .fi-comments-ui-compact-toolbar .fi-comments-editor ui-toolbar button {
            height: 1.5rem;
            min-width: 1.5rem;
            padding: 0.125rem;
        }

        .fi-comments-ui-compact-toolbar .fi-comments-editor ui-toolbar [data-flux-icon] {
            width: 0.875rem;
            height: 0.875rem;
        }
    </style>
@endif

@if (CommentUi::compactActionIcons($uiContext))
    <style>
        .fi-comments-ui-compact-actions .fi-comment-item flux-button,
        .fi-comments-ui-compact-actions .fi-comment-item [data-flux-button] {
            --flux-button-size: 1.75rem;
        }
    </style>
@endif

@if ($this->allowMentions)
    <style>
        .fi-comments-mention-dropdown {
            position: fixed !important;
            z-index: 2147483000 !important;
            pointer-events: auto !important;
        }
    </style>
@endif

@if (CommentUi::isCondensed($uiContext))
    <style>
        .fi-comments-ui-condensed.fi-comments-panel {
            gap: 0.625rem;
        }

        .fi-comments-ui-condensed .fi-comment-item {
            gap: 0.375rem;
        }

        .fi-comments-ui-condensed .fi-comment-thread .fi-comment-item[class*="pl-4"] {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .fi-comments-ui-condensed .fi-comment-thread .fi-comment-item[class*="pt-4"] {
            padding-top: 0.625rem;
        }

        .fi-comments-ui-condensed .fi-comment-thread .fi-comment-item[class*="pb-4"],
        .fi-comments-ui-condensed .fi-comment-thread .fi-comment-item[class*="pb-2"] {
            padding-bottom: 0.5rem;
        }

        .fi-comments-ui-condensed .fi-comment-item .prose {
            font-size: 0.8125rem;
            line-height: 1.45;
        }

        .fi-comments-ui-condensed .fi-comments-empty-state flux-icon,
        .fi-comments-ui-condensed .fi-comments-empty-state [data-flux-icon] {
            width: 1.75rem;
            height: 1.75rem;
        }
    </style>
@endif
