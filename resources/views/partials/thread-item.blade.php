@php
    $hasReplies = $allowReplies && $comment->replies->isNotEmpty();
@endphp

<div
    class="fi-comment-thread border-b border-zinc-200/80 last:border-b-0 dark:border-white/10"
    wire:key="comment-thread-{{ $comment->id }}"
>
    @include('filament-comments::partials.list-item', [
        'comment' => $comment,
        'isReply' => false,
        'nestLevel' => 0,
        'hasRepliesBelow' => $hasReplies,
    ])

    @if ($hasReplies)
        @include('filament-comments::partials.reply-nest', [
            'comments' => $comment->replies,
            'parentComment' => $comment,
            'nestLevel' => 1,
        ])
    @endif
</div>
