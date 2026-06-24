@php
    use Joranski\FilamentComments\Support\CommentAuthorization;
    use Joranski\FilamentComments\Support\CommentUi;

    $canViewAny = CommentAuthorization::canViewAny();
    $canCreate = CommentAuthorization::canCreate();
    $recordExists = $this->record?->exists ?? false;
    $threadMaxHeight = $this->resolvedThreadMaxHeight();
    $uiContext = $this->uiCompactProfileContext();
    $condensed = CommentUi::isCondensed($uiContext);
@endphp

<div
    @class(array_merge([
        'fi-comments-panel flex flex-col',
        'gap-4' => ! $condensed,
        'gap-2.5' => $condensed,
        'fi-comments-panel-compact' => $layout === 'compact',
        'fi-comments-panel-full' => $layout === 'full',
    ], CommentUi::panelClasses($uiContext)))
>
    @include('filament-comments::partials.ui-styles')
    @if ($showHeading && filled($heading))
        <flux:heading :size="$condensed ? 'xs' : 'sm'">{{ __($heading) }}</flux:heading>
    @endif

    @if (! $recordExists)
        <flux:callout variant="warning" icon="information-circle">
            {{ __('Save this record before adding comments.') }}
        </flux:callout>
    @elseif ($canCreate)
        <div @class([
            'flex flex-col',
            'gap-3' => ! $condensed,
            'gap-2' => $condensed,
        ])>
            @if ($this->usesTextareaMentionAutocomplete())
                <x-filament-comments::mention-autocomplete state-path="commentFormData.body">
                    {{ $this->form }}
                </x-filament-comments::mention-autocomplete>
            @else
                {{ $this->form }}
            @endif

            @if ($this->showsProofreadToggle())
                <flux:field variant="inline">
                    <flux:label>{{ __('Proofread with AI') }}</flux:label>
                    <flux:switch wire:model.live="proofreadWithAi" />
                </flux:field>
            @endif

            <div>
                <flux:button
                    type="button"
                    variant="primary"
                    :size="$condensed ? 'sm' : 'base'"
                    wire:click="addComment"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="addComment">{{ __($addButtonLabel) }}</span>
                    <span wire:loading wire:target="addComment">{{ __('Adding…') }}</span>
                </flux:button>
            </div>
        </div>
    @endif

    @if ($this->comments->isNotEmpty())
        <div class="flex flex-col gap-2">
            @if ($this->showCommentSearch())
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search comments…') }}"
                    icon="magnifying-glass"
                    :size="$condensed ? 'sm' : 'base'"
                    clearable
                />
            @endif

            <div
                @class([
                    'overflow-y-auto border border-zinc-200 dark:border-white/10 bg-white dark:bg-white/5',
                    'rounded-xl' => ! $condensed,
                    'rounded-lg' => $condensed,
                ])
                @if ($threadMaxHeight !== null)
                    style="max-height: {{ $threadMaxHeight }}px;"
                @endif
            >
                @foreach ($this->comments as $comment)
                    @include('filament-comments::partials.thread-item', [
                        'comment' => $comment,
                    ])
                @endforeach
            </div>
        </div>
    @elseif ($recordExists && $canViewAny && filled(trim($search)))
        <div class="flex flex-col gap-2">
            @if ($this->showCommentSearch())
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search comments…') }}"
                    icon="magnifying-glass"
                    :size="$condensed ? 'sm' : 'base'"
                    clearable
                />
            @endif

            <div class="fi-comments-empty-state flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 dark:border-white/15 px-6 py-10 text-center text-zinc-500 dark:text-zinc-400">
                <flux:icon.magnifying-glass @class(['mb-2', 'size-10' => ! $condensed, 'size-7' => $condensed]) />
                <flux:text :size="$condensed ? 'xs' : 'sm'">{{ __('No comments match your search.') }}</flux:text>
            </div>
        </div>
    @elseif ($recordExists && $canViewAny)
        <div class="fi-comments-empty-state flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 dark:border-white/15 px-6 py-10 text-center text-zinc-500 dark:text-zinc-400">
            <flux:icon.chat-bubble-bottom-center-text @class(['mb-2', 'size-10' => ! $condensed, 'size-7' => $condensed]) />
            <flux:text :size="$condensed ? 'xs' : 'sm'">{{ __('No comments yet.') }}</flux:text>
        </div>
    @endif

    @if ($showLifecyclePrompt)
        @php($deferPromptView = $this->lifecycleDeferPromptView())

        @if (filled($deferPromptView) && view()->exists($deferPromptView))
            @teleport('body')
                @include($deferPromptView, [
                    'pendingCommentPayload' => $pendingCommentPayload,
                    'lifecyclePromptKey' => $lifecyclePromptKey,
                ])
            @endteleport
        @endif
    @endif

    <x-filament-actions::modals />
</div>
