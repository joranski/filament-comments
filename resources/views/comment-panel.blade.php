@php
    use Joranski\FilamentComments\Support\CommentAuthor;

    $canCreate = auth()->user()?->can('create', config('filament-comments.comment_model')) ?? false;
    $recordExists = $this->record?->exists ?? false;
@endphp

<div
    @class([
        'fi-comments-panel flex flex-col gap-4',
        'fi-comments-panel-compact' => $layout === 'compact',
        'fi-comments-panel-full' => $layout === 'full',
    ])
>
    @if ($showHeading && filled($heading))
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="sm">{{ __($heading) }}</flux:heading>

            @if ($allowSearch && $recordExists && ($this->comments->isNotEmpty() || filled($search)))
                <div class="w-full sm:w-64">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search comments…') }}"
                        icon="magnifying-glass"
                        clearable
                    />
                </div>
            @endif
        </div>
    @elseif ($allowSearch && $recordExists && ($this->comments->isNotEmpty() || filled($search)))
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('Search comments…') }}"
            icon="magnifying-glass"
            clearable
        />
    @endif

    @if (! $recordExists)
        <flux:callout variant="warning" icon="information-circle">
            {{ __('Save this record before adding comments.') }}
        </flux:callout>
    @elseif ($canCreate)
        @if ($replyingToCommentId && $this->replyingToComment)
            <flux:callout variant="secondary" icon="arrow-uturn-left">
                <x-slot name="actions">
                    <flux:button type="button" variant="ghost" size="sm" wire:click="cancelReply">
                        {{ __('Cancel') }}
                    </flux:button>
                </x-slot>

                {{ __('Replying to :name', ['name' => CommentAuthor::displayName($this->replyingToComment->user)]) }}
            </flux:callout>
        @endif

        <div class="flex flex-col gap-3">
            {{ $this->form }}

            <div>
                <flux:button
                    type="button"
                    variant="primary"
                    wire:click="addComment"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="addComment">
                        {{ __($replyingToCommentId ? __('Add reply') : $addButtonLabel) }}
                    </span>
                    <span wire:loading wire:target="addComment">{{ __('Adding…') }}</span>
                </flux:button>
            </div>
        </div>
    @endif

    @if ($this->comments->isNotEmpty())
        <div @class([
            'overflow-y-auto rounded-xl border border-zinc-200 dark:border-white/10 divide-y divide-zinc-200 dark:divide-white/10 bg-white dark:bg-white/5',
            'max-h-[28rem]' => $layout === 'full',
            'max-h-80' => $layout === 'compact',
        ])>
            @foreach ($this->comments as $comment)
                @include('filament-comments::partials.list-item', [
                    'comment' => $comment,
                    'isReply' => false,
                ])

                @if ($allowReplies && $comment->replies->isNotEmpty())
                    <div class="divide-y divide-zinc-100 dark:divide-white/5 bg-zinc-50/80 dark:bg-white/[0.02]">
                        @foreach ($comment->replies as $reply)
                            @include('filament-comments::partials.list-item', [
                                'comment' => $reply,
                                'isReply' => true,
                            ])
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    @elseif ($recordExists && filled(trim($search)))
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 dark:border-white/15 px-6 py-10 text-center text-zinc-500 dark:text-zinc-400">
            <flux:icon.magnifying-glass class="mb-2 size-10" />
            <flux:text size="sm">{{ __('No comments match your search.') }}</flux:text>
        </div>
    @elseif ($recordExists)
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 dark:border-white/15 px-6 py-10 text-center text-zinc-500 dark:text-zinc-400">
            <flux:icon.chat-bubble-bottom-center-text class="mb-2 size-10" />
            <flux:text size="sm">{{ __('No comments yet.') }}</flux:text>
        </div>
    @endif
</div>
