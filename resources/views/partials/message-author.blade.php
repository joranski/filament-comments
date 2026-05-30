@php
    use Joranski\FilamentComments\Support\CommentAuthor;
@endphp

<div class="flex flex-wrap items-center gap-x-2 gap-y-1">
    <flux:text class="font-medium text-zinc-950 dark:text-white">
        {{ CommentAuthor::displayName($author) }}
    </flux:text>

    @if ($showPinned ?? false)
        <flux:badge size="sm" color="amber" icon="bookmark">
            {{ __('Pinned') }}
        </flux:badge>
    @endif

    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
        {{ CommentAuthor::formattedTimestamp($timestamp ?? $comment->created_at ?? null) }}
    </flux:text>

    @if ($showEdited ?? false)
        <flux:text size="sm" class="text-zinc-400 dark:text-zinc-500">
            ({{ __('edited') }})
        </flux:text>
    @endif
</div>
