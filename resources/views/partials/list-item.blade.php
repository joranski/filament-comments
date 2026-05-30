@php
    use Joranski\FilamentComments\Support\CommentAuthor;

    $author = $comment->user;
    $canDelete = CommentAuthor::canDelete($comment);
    $canEdit = $allowEdit && CommentAuthor::canEdit($comment);
    $canPin = $allowPins && ! ($isReply ?? false) && CommentAuthor::canPin($comment);
    $canReply = $allowReplies && ! ($isReply ?? false) && CommentAuthor::canReply($comment);
    $isEditing = $editingCommentId === $comment->id;
@endphp

<div
    @class([
        'flex items-start gap-3 p-4',
        'pl-10 border-l-4 border-primary-500/30' => $isReply ?? false,
    ])
    wire:key="comment-{{ $comment->id }}"
>
    <flux:avatar
        size="sm"
        :name="CommentAuthor::displayName($author)"
        class="shrink-0"
    />

    <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                @include('filament-comments::partials.message-author', [
                    'author' => $author,
                    'comment' => $comment,
                    'showPinned' => $comment->is_pinned && ! ($isReply ?? false),
                    'showEdited' => (bool) $comment->edited_at,
                ])
            </div>

            <div class="flex shrink-0 items-center gap-1">
                @if ($canPin)
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
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
                        size="sm"
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
                        size="sm"
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
                        size="sm"
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

        @if ($isEditing)
            @include('filament-comments::partials.list-item-edit-form')
        @else
            <div class="prose prose-sm dark:prose-invert mt-2 max-w-none text-zinc-700 dark:text-zinc-300 [&>*:first-child]:mt-0 [&>*:last-child]:mb-0">
                {!! $this->renderCommentBody($comment) !!}
            </div>
        @endif
    </div>
</div>
