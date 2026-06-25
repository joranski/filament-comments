@props([
    'commentPanelLivewireId',
    'schemaComponentKey',
    'statePath',
])

<div
    x-data="commentRichEditorMentionBridge({
        commentPanelLivewireId: @js($commentPanelLivewireId),
        schemaComponentKey: @js($schemaComponentKey),
        statePath: @js($statePath),
    })"
    class="relative"
>
    {{ $slot }}

    <template x-teleport="body">
        <div
            x-cloak
            x-show="open"
            x-transition.opacity
            class="fi-comments-mention-dropdown fixed w-72 max-h-60 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-white/10 dark:bg-zinc-900"
            :style="`top: ${position.top}px; left: ${position.left}px;`"
            role="listbox"
            aria-label="{{ __('Mention suggestions') }}"
        >
            <template x-if="loading">
                <div class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Searching users…') }}
                </div>
            </template>

            <template x-if="! loading && searchCompleted && users.length === 0">
                <div class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No users found.') }}
                </div>
            </template>

            <template x-if="! loading && users.length > 0">
                <div>
                    <template x-for="(user, index) in users" :key="user.id">
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-white/5"
                            :class="{ 'bg-zinc-50 dark:bg-white/5': index === selectedIndex }"
                            x-on:mousedown.prevent="selectUser(user)"
                            role="option"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-semibold uppercase text-zinc-700 dark:bg-white/10 dark:text-zinc-200"
                                x-text="user.name.split(' ').filter(Boolean).slice(0, 2).map(part => part[0]).join('')"
                            ></span>
                            <span x-text="user.name" class="truncate font-medium text-zinc-900 dark:text-white"></span>
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </template>
</div>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            if (window.__commentRichEditorMentionBridgeRegistered) {
                return
            }

            window.__commentRichEditorMentionBridgeRegistered = true

            Alpine.data('commentRichEditorMentionBridge', (config) => ({
                commentPanelLivewireId: config.commentPanelLivewireId,
                schemaComponentKey: config.schemaComponentKey,
                statePath: config.statePath,
                editor: null,
                editorDom: null,
                loadedEventName: null,
                open: false,
                loading: false,
                query: '',
                users: [],
                selectedIndex: 0,
                mentionStart: null,
                searchTimer: null,
                searchCompleted: false,
                position: { top: 0, left: 0 },

                init() {
                    this.loadedEventName = `schema-component-${this.commentPanelLivewireId}-${this.schemaComponentKey}-loaded`

                    this.onEditorLoaded = () => {
                        this.bindEditor()
                    }

                    window.addEventListener(this.loadedEventName, this.onEditorLoaded)

                    this.$nextTick(() => this.bindEditor())
                },

                destroy() {
                    if (this.loadedEventName && this.onEditorLoaded) {
                        window.removeEventListener(this.loadedEventName, this.onEditorLoaded)
                    }

                    this.unbindEditor()
                },

                bindEditor() {
                    const host = this.$el.querySelector('[x-ref="editor"]')?.closest('[x-data]')

                    if (! host || typeof Alpine === 'undefined' || typeof Alpine.$data !== 'function') {
                        return
                    }

                    const alpineData = Alpine.$data(host)

                    if (! alpineData || typeof alpineData.getEditor !== 'function') {
                        return
                    }

                    const nextEditor = alpineData.getEditor()

                    if (! nextEditor || nextEditor === this.editor) {
                        return
                    }

                    this.unbindEditor()
                    this.editor = nextEditor
                    this.editorDom = nextEditor.view?.dom ?? null

                    if (! this.editorDom) {
                        return
                    }

                    this.onEditorKeydown = (event) => this.handleEditorKeydown(event)
                    this.onEditorUpdate = () => this.syncMentionQuery()

                    this.editorDom.addEventListener('keydown', this.onEditorKeydown, true)
                    this.editor.on('update', this.onEditorUpdate)
                    this.editor.on('selectionUpdate', this.onEditorUpdate)
                },

                unbindEditor() {
                    if (this.editorDom && this.onEditorKeydown) {
                        this.editorDom.removeEventListener('keydown', this.onEditorKeydown, true)
                    }

                    if (this.editor && this.onEditorUpdate) {
                        this.editor.off('update', this.onEditorUpdate)
                        this.editor.off('selectionUpdate', this.onEditorUpdate)
                    }

                    this.editor = null
                    this.editorDom = null
                },

                syncMentionQuery() {
                    if (! this.editor) {
                        return
                    }

                    const { from } = this.editor.state.selection
                    const textBefore = this.editor.state.doc.textBetween(
                        Math.max(0, from - 200),
                        from,
                        '\n',
                        '\n',
                    )
                    const match = textBefore.match(/(^|\s)@([^\s@]*)$/)

                    if (! match) {
                        this.close()

                        return
                    }

                    this.mentionStart = from - match[2].length - 1
                    this.query = match[2]
                    this.open = true
                    this.searchCompleted = false
                    this.updatePosition()
                    this.scheduleSearch()
                },

                scheduleSearch() {
                    window.clearTimeout(this.searchTimer)
                    this.loading = true
                    this.searchCompleted = false

                    this.searchTimer = window.setTimeout(async () => {
                        const component = Livewire.find(this.commentPanelLivewireId)

                        if (! component) {
                            this.users = []
                            this.loading = false
                            this.searchCompleted = true

                            return
                        }

                        try {
                            this.users = await component.searchMentionUsers(this.query)
                        } catch {
                            this.users = []
                        }

                        this.selectedIndex = 0
                        this.loading = false
                        this.searchCompleted = true
                        this.updatePosition()
                    }, 200)
                },

                handleEditorKeydown(event) {
                    if (! this.open) {
                        return
                    }

                    if (event.key === 'ArrowDown') {
                        event.preventDefault()
                        event.stopPropagation()
                        this.selectedIndex = Math.min(this.selectedIndex + 1, this.users.length - 1)

                        return
                    }

                    if (event.key === 'ArrowUp') {
                        event.preventDefault()
                        event.stopPropagation()
                        this.selectedIndex = Math.max(this.selectedIndex - 1, 0)

                        return
                    }

                    if (event.key === 'Enter' && this.users[this.selectedIndex]) {
                        event.preventDefault()
                        event.stopPropagation()
                        this.selectUser(this.users[this.selectedIndex])

                        return
                    }

                    if (event.key === 'Escape') {
                        event.preventDefault()
                        event.stopPropagation()
                        this.close()
                    }
                },

                selectUser(user) {
                    if (! this.editor || this.mentionStart === null) {
                        return
                    }

                    const from = this.mentionStart
                    const to = this.editor.state.selection.from

                    this.editor
                        .chain()
                        .focus()
                        .deleteRange({ from, to })
                        .insertContentAt(from, [
                            {
                                type: 'mention',
                                attrs: {
                                    id: String(user.id),
                                    label: user.name,
                                    char: '@',
                                },
                            },
                            {
                                type: 'text',
                                text: ' ',
                            },
                        ])
                        .run()

                    this.close()
                },

                updatePosition() {
                    if (! this.editor || this.mentionStart === null) {
                        return
                    }

                    try {
                        const coords = this.editor.view.coordsAtPos(this.mentionStart)
                        const dropdownHeight = 240
                        const spaceBelow = window.innerHeight - coords.bottom
                        const spaceAbove = coords.top
                        const openAbove = spaceBelow < dropdownHeight && spaceAbove > spaceBelow
                        const top = openAbove
                            ? Math.max(8, coords.top - dropdownHeight - 8)
                            : Math.min(coords.bottom + 8, window.innerHeight - dropdownHeight - 8)
                        const left = Math.min(coords.left, window.innerWidth - 300)

                        this.position = { top, left }
                    } catch {
                        this.position = { top: 0, left: 0 }
                    }
                },

                close() {
                    this.open = false
                    this.loading = false
                    this.searchCompleted = false
                    this.query = ''
                    this.users = []
                    this.mentionStart = null
                    window.clearTimeout(this.searchTimer)
                },
            }))
        })
    </script>
@endonce
