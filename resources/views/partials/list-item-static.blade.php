@php
    use Joranski\FilamentComments\Support\CommentAuthor;

    $author = $comment->user;
    $canDelete = ($showDelete ?? true) && CommentAuthor::canDelete($comment);
@endphp

<div class="flex items-start gap-3 p-4">
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
                ])
            </div>

            @if ($canDelete)
                <flux:button
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="trash"
                    color="danger"
                    wire:click="deleteComment({{ $comment->id }})"
                    wire:confirm="{{ __('Delete this comment?') }}"
                    class="shrink-0"
                />
            @endif
        </div>

        <div class="prose prose-sm dark:prose-invert mt-2 max-w-none text-zinc-700 dark:text-zinc-300 [&>*:first-child]:mt-0 [&>*:last-child]:mb-0">
            {!! str($comment->comment ?? '')->toHtmlString() !!}
        </div>
    </div>
</div>
