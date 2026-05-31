@php
    use Joranski\FilamentComments\Support\CommentThreadDepth;

    $maxReplyDepth = CommentThreadDepth::maxReplyDepth();
    $nestLevel = (int) ($nestLevel ?? 1);
@endphp

<div class="relative pb-3">
    <div
        class="pointer-events-none absolute bottom-3 w-px bg-zinc-300/80 dark:bg-white/15"
        style="left: {{ CommentThreadDepth::threadSpineLeftCss($nestLevel) }}; top: -0.375rem;"
        aria-hidden="true"
    ></div>

    <div class="flex flex-col gap-1">
        @foreach ($comments as $comment)
            @php
                $hasNestedReplies = $allowReplies
                    && $comment->replies->isNotEmpty()
                    && $nestLevel < $maxReplyDepth;
            @endphp

            @include('filament-comments::partials.list-item', [
                'comment' => $comment,
                'isReply' => true,
                'nestLevel' => $nestLevel,
                'parentComment' => $parentComment,
                'hasRepliesBelow' => $hasNestedReplies,
            ])

            @if ($hasNestedReplies)
                @include('filament-comments::partials.reply-nest', [
                    'comments' => $comment->replies,
                    'parentComment' => $comment,
                    'nestLevel' => $nestLevel + 1,
                ])
            @endif
        @endforeach
    </div>
</div>
