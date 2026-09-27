<div class="mt-3 flex flex-col gap-3" wire:key="edit-form-{{ $comment->id }}">
    @include('filament-comments::partials.composer', ['composer' => 'edit'])

    <div class="flex items-center gap-2">
        <flux:button
            type="button"
            variant="primary"
            size="sm"
            wire:click="saveEdit"
            wire:loading.attr="disabled"
            wire:target="saveEdit,editAttachments"
        >
            {{ __('Save') }}
        </flux:button>

        <flux:button
            type="button"
            variant="ghost"
            size="sm"
            wire:click="cancelEdit"
        >
            {{ __('Cancel') }}
        </flux:button>
    </div>
</div>
