<div
    class="fi-comment-reply-form mt-3 flex flex-col gap-3 rounded-lg border border-zinc-200 bg-zinc-50/80 p-3 dark:border-white/10 dark:bg-white/5"
    wire:key="reply-form-{{ $comment->id }}"
    x-data
    x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }))"
>
    @if ($this->usesTextareaMentionAutocomplete())
        <x-filament-comments::mention-autocomplete state-path="replyFormData.body">
            {{ $this->replyForm }}
        </x-filament-comments::mention-autocomplete>
    @else
        {{ $this->replyForm }}
    @endif

    <div class="flex items-center gap-2">
        <flux:button
            type="button"
            variant="primary"
            size="sm"
            wire:click="submitReply"
            wire:loading.attr="disabled"
            wire:target="submitReply"
        >
            <span wire:loading.remove wire:target="submitReply">{{ __('Add reply') }}</span>
            <span wire:loading wire:target="submitReply">{{ __('Adding…') }}</span>
        </flux:button>

        <flux:button
            type="button"
            variant="ghost"
            size="sm"
            wire:click="cancelReply"
        >
            {{ __('Cancel') }}
        </flux:button>
    </div>
</div>
