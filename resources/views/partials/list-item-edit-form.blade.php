<div class="mt-3 flex flex-col gap-3">
    @if ($this->usesTextareaMentionAutocomplete())
        <x-filament-comments::mention-autocomplete state-path="editFormData.body">
            {{ $this->editForm }}
        </x-filament-comments::mention-autocomplete>
    @else
        {{ $this->editForm }}
    @endif

    <div class="flex items-center gap-2">
        <flux:button
            type="button"
            variant="primary"
            size="sm"
            wire:click="saveEdit"
            wire:loading.attr="disabled"
            wire:target="saveEdit"
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
