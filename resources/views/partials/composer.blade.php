@php
    /** @var string $composer root|reply|edit */
    $formProperty = match ($composer) {
        'reply' => 'replyFormData',
        'edit' => 'editFormData',
        default => 'commentFormData',
    };
    $attachmentProperty = match ($composer) {
        'reply' => 'replyAttachments',
        'edit' => 'editAttachments',
        default => 'commentAttachments',
    };
    $statePath = $formProperty.'.body';
    $placeholder = $this->composerPlaceholder($composer);
    $attachmentsEnabled = $this->attachmentsEnabled($composer);
    $queuedAttachments = $attachmentsEnabled ? $this->queuedAttachments($composer) : [];
    $maxSizeKb = \Joranski\FilamentComments\Support\CommentAttachments::maxSizeKb();
@endphp

<div class="fi-comments-composer flex flex-col gap-2" data-composer="{{ $composer }}">
    @if ($this->usesRichEditor())
        @if ($this->usesEditorMentionAutocomplete())
            <x-filament-comments::editor-mentions>
                <flux:editor
                    wire:model="{{ $statePath }}"
                    :toolbar="$this->editorToolbar()"
                    :placeholder="$placeholder"
                    :aria-label="__('Comment')"
                    class="fi-comments-editor **:data-[slot=content]:min-h-[5rem]!"
                />
            </x-filament-comments::editor-mentions>
        @else
            <flux:editor
                wire:model="{{ $statePath }}"
                :toolbar="$this->editorToolbar()"
                :placeholder="$placeholder"
                :aria-label="__('Comment')"
                class="fi-comments-editor **:data-[slot=content]:min-h-[5rem]!"
            />
        @endif
    @elseif ($this->usesTextareaMentionAutocomplete())
        <x-filament-comments::mention-autocomplete :state-path="$statePath">
            <flux:textarea
                wire:model="{{ $statePath }}"
                :rows="$this->textareaRows()"
                :placeholder="$placeholder"
                :aria-label="__('Comment')"
            />
        </x-filament-comments::mention-autocomplete>
    @else
        <flux:textarea
            wire:model="{{ $statePath }}"
            :rows="$this->textareaRows()"
            :placeholder="$placeholder"
            :aria-label="__('Comment')"
        />
    @endif

    <flux:error :name="$statePath" class="mt-0!" />

    @if ($composer === 'edit' && $editExistingAttachments !== [])
        <div class="flex flex-wrap gap-2">
            @foreach ($this->existingAttachmentLabels() as $index => $label)
                <span wire:key="existing-attachment-{{ $index }}-{{ md5($label) }}">
                    <flux:badge size="sm" icon="paper-clip">
                        {{ $label }}
                        <flux:badge.close wire:click="removeExistingAttachment({{ $index }})" :aria-label="__('Remove attachment')" />
                    </flux:badge>
                </span>
            @endforeach
        </div>
    @endif

    @if ($attachmentsEnabled)
        <flux:file-upload wire:model="{{ $attachmentProperty }}" multiple :aria-label="__('Attach files')">
            <flux:file-upload.dropzone
                :heading="__('Drop files or click to attach')"
                :text="$maxSizeKb ? __('Up to :size MB each', ['size' => round($maxSizeKb / 1024, 1)]) : null"
                inline
                with-progress
            />
        </flux:file-upload>

        @if ($queuedAttachments !== [])
            <div class="flex flex-col gap-1.5">
                @foreach ($queuedAttachments as $index => $file)
                    <div wire:key="queued-{{ $composer }}-{{ $index }}-{{ md5($file->getFilename()) }}">
                        <flux:file-item
                            :heading="$file->getClientOriginalName()"
                            :size="$file->getSize()"
                        >
                            <x-slot name="actions">
                                <flux:file-item.remove
                                    wire:click="removeUpload('{{ $composer }}', {{ $index }})"
                                    :aria-label="__('Remove file: :name', ['name' => $file->getClientOriginalName()])"
                                />
                            </x-slot>
                        </flux:file-item>
                    </div>
                @endforeach
            </div>
        @endif

        <flux:error :name="$attachmentProperty" class="mt-0!" />
    @endif
</div>
