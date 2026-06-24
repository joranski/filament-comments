@php
    use Joranski\FilamentComments\Support\CommentAuthor;
@endphp

<div @class([
    'flex flex-wrap items-center gap-x-2 gap-y-0.5',
    'gap-y-1' => ! ($compact ?? false) && ! ($condensed ?? false),
])>
    <flux:text @class([
        'font-medium text-zinc-950 dark:text-white',
        'text-sm' => ($compact ?? false) || ($condensed ?? false),
        'text-xs' => ($condensed ?? false) && ($compact ?? false),
    ])>
        {{ CommentAuthor::displayName($author) }}
    </flux:text>

    @if ($showPinned ?? false)
        <flux:badge :size="($condensed ?? false) ? 'xs' : 'sm'" color="amber" icon="bookmark">
            {{ __('Pinned') }}
        </flux:badge>
    @endif

    <flux:text :size="($condensed ?? false) ? 'xs' : 'sm'" class="text-zinc-500 dark:text-zinc-400">
        {{ CommentAuthor::formattedTimestamp($timestamp ?? $comment->created_at ?? null) }}
    </flux:text>

    @if ($showEdited ?? false)
        <flux:text :size="($condensed ?? false) ? 'xs' : 'sm'" class="text-zinc-400 dark:text-zinc-500">
            ({{ __('edited') }})
        </flux:text>
    @endif
</div>
