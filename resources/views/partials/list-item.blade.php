@php
    use Joranski\FilamentComments\Support\CommentAuthor;
    use Joranski\FilamentComments\Support\CommentThreadDepth;
    use Joranski\FilamentComments\Support\CommentUi;

    $author = $comment->user;
    $uiContext = $this->uiCompactProfileContext();
    $condensed = CommentUi::isCondensed($uiContext);
    $actionButtonSize = CommentUi::actionButtonSize($uiContext);
    $rootAvatarSize = CommentUi::rootAvatarSize($uiContext);
    $canDelete = CommentAuthor::canDelete($comment);
    $canEdit = $allowEdit && CommentAuthor::canEdit($comment);
    $canPin = $allowPins && ! ($isReply ?? false) && CommentAuthor::canPin($comment);
    $canReply = $allowReplies && CommentAuthor::canReply($comment);
    $isEditing = $editingCommentId === $comment->id;
    $isReplying = $allowReplies && $this->isReplyingToComment($comment->id);
    $isReplyItem = (bool) ($isReply ?? false);
    $nestLevel = (int) ($nestLevel ?? 0);
    $hasRepliesBelow = (bool) ($hasRepliesBelow ?? false);
    $replyIndentPx = CommentThreadDepth::replyIndentPx();
    $contentIndentCss = CommentThreadDepth::contentIndentCss($nestLevel);
@endphp

<div
    @class([
        'fi-comment-item relative flex w-full items-start gap-2 pr-4',
        'pl-4 pt-4 pb-4' => $nestLevel === 0 && ! $hasRepliesBelow && ! $condensed,
        'pl-4 pt-4 pb-2' => $nestLevel === 0 && $hasRepliesBelow && ! $condensed,
        'pl-3 pt-2.5 pb-2.5' => $nestLevel === 0 && ! $hasRepliesBelow && $condensed,
        'pl-3 pt-2.5 pb-1.5' => $nestLevel === 0 && $hasRepliesBelow && $condensed,
        'py-1.5' => $nestLevel > 0 && ! $condensed,
        'py-1' => $nestLevel > 0 && $condensed,
    ])
    wire:key="comment-{{ $comment->id }}"
>
    <div
        @class([
            'flex min-w-0 flex-1 items-start',
            'gap-3' => ! $condensed,
            'gap-2' => $condensed,
            'relative' => $isReplyItem,
        ])
        @if ($contentIndentCss)
            style="padding-left: {{ $contentIndentCss }};"
        @endif
    >
        @if ($isReplyItem)
            {{-- Branch connector from thread spine into this reply --}}
            <div
                class="pointer-events-none absolute top-[1.125rem] h-px bg-zinc-300/80 dark:bg-white/15"
                style="left: -{{ $replyIndentPx }}px; width: {{ $replyIndentPx }}px;"
                aria-hidden="true"
            ></div>
        @endif

        <flux:avatar
            :size="$isReplyItem ? 'xs' : $rootAvatarSize"
            :name="CommentAuthor::displayName($author)"
            @class([
                'shrink-0',
                'ring-2 ring-white dark:ring-zinc-900' => $isReplyItem,
            ])
        />

        <div class="min-w-0 flex-1">
            @if ($isReplyItem && ($parentComment ?? null))
                <flux:text size="sm" class="mb-0.5 leading-tight text-zinc-400 dark:text-zinc-500">
                    <span class="inline-flex items-center gap-1">
                        <flux:icon.arrow-uturn-left class="size-3.5 shrink-0" />
                        {{ CommentAuthor::displayName($parentComment->user) }}
                    </span>
                </flux:text>
            @endif

            @include('filament-comments::partials.message-author', [
                'author' => $author,
                'comment' => $comment,
                'showPinned' => $comment->is_pinned && ! $isReplyItem,
                'showEdited' => (bool) $comment->edited_at,
                'compact' => $isReplyItem || $condensed,
                'condensed' => $condensed,
            ])

            @if ($isEditing)
                <div class="mt-2">
                    @include('filament-comments::partials.list-item-edit-form')
                </div>
            @else
                <div @class([
                    'prose dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 [&>*:first-child]:mt-0 [&>*:last-child]:mb-0',
                    'prose-sm mt-2' => $nestLevel === 0 && ! $condensed,
                    'prose-sm mt-1.5 text-[0.8125rem] leading-snug' => $nestLevel === 0 && $condensed,
                    'prose-sm mt-1 text-[0.9375rem] leading-relaxed' => $nestLevel > 0 && ! $condensed,
                    'prose-sm mt-0.5 text-[0.8125rem] leading-snug' => $nestLevel > 0 && $condensed,
                ])>
                    {!! $this->renderCommentBody($comment) !!}
                </div>

                @if ($isReplying)
                    @include('filament-comments::partials.list-item-reply-form', [
                        'comment' => $comment,
                    ])
                @endif
            @endif
        </div>
    </div>

    <div class="flex shrink-0 items-center gap-0.5 self-start opacity-80 transition-opacity hover:opacity-100">
        @if ($canPin)
            <flux:button
                type="button"
                variant="ghost"
                size="{{ $actionButtonSize }}"
                :icon="$comment->is_pinned ? 'bookmark-slash' : 'bookmark'"
                wire:click="togglePin({{ $comment->id }})"
                wire:loading.attr="disabled"
                wire:target="togglePin({{ $comment->id }})"
                :title="$comment->is_pinned ? __('Unpin') : __('Pin')"
            />
        @endif

        @if ($canReply)
            <flux:button
                type="button"
                variant="ghost"
                size="{{ $actionButtonSize }}"
                icon="arrow-uturn-left"
                wire:click="startReply({{ $comment->id }})"
                wire:loading.attr="disabled"
                wire:target="startReply({{ $comment->id }})"
                :title="__('Reply')"
            />
        @endif

        @if ($canEdit && ! $isEditing)
            <flux:button
                type="button"
                variant="ghost"
                size="{{ $actionButtonSize }}"
                icon="pencil-square"
                wire:click="startEdit({{ $comment->id }})"
                wire:loading.attr="disabled"
                wire:target="startEdit({{ $comment->id }})"
                :title="__('Edit')"
            />
        @endif

        @if ($canDelete)
            <flux:button
                type="button"
                variant="ghost"
                size="{{ $actionButtonSize }}"
                icon="trash"
                color="danger"
                wire:click="deleteComment({{ $comment->id }})"
                wire:confirm="{{ __('Delete this comment?') }}"
                wire:loading.attr="disabled"
                wire:target="deleteComment({{ $comment->id }})"
            />
        @endif
    </div>
</div>
