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
            x-on:mousedown.prevent
        >
            <div
                x-show="loading"
                class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400"
            >
                {{ __('Searching users…') }}
            </div>

            <div
                x-show="! loading && searchCompleted && users.length === 0"
                class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400"
            >
                {{ __('No users found.') }}
            </div>

            <template x-for="(user, index) in users" :key="user.id">
                <button
                    type="button"
                    x-show="! loading && users.length > 0"
                    class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-white/5"
                    :class="{ 'bg-zinc-50 dark:bg-white/5': index === selectedIndex }"
                    x-on:pointerdown.prevent.stop="pickUser(user)"
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
                editorHostEl: null,
                editorDom: null,
                boundEditor: null,
                loadedEventName: null,
                open: false,
                loading: false,
                query: '',
                users: [],
                selectedIndex: 0,
                mentionStart: null,
                mentionRange: null,
                isPicking: false,
                searchTimer: null,
                searchCompleted: false,
                position: { top: 0, left: 0 },

                init() {
                    this.loadedEventName = `schema-component-${this.commentPanelLivewireId}-${this.schemaComponentKey}-loaded`

                    this.onEditorLoaded = () => {
                        this.bindEditorListeners()
                    }

                    window.addEventListener(this.loadedEventName, this.onEditorLoaded)

                    this.$nextTick(() => this.bindEditorListeners())
                },

                destroy() {
                    if (this.loadedEventName && this.onEditorLoaded) {
                        window.removeEventListener(this.loadedEventName, this.onEditorLoaded)
                    }

                    this.unbindEditorListeners()
                },

                editorHost() {
                    return this.$el.querySelector('[x-ref="editor"]')?.closest('[x-data]') ?? null
                },

                resolveEditor() {
                    const host = this.editorHost()

                    if (! host || typeof Alpine === 'undefined' || typeof Alpine.$data !== 'function') {
                        return null
                    }

                    const alpineData = Alpine.$data(host)

                    if (! alpineData || typeof alpineData.getEditor !== 'function') {
                        return null
                    }

                    return alpineData.getEditor()
                },

                bindEditorListeners() {
                    const editor = this.resolveEditor()

                    if (! editor?.view?.dom || editor === this.boundEditor) {
                        return
                    }

                    this.unbindEditorListeners()

                    this.boundEditor = editor
                    this.editorHostEl = this.editorHost()
                    this.editorDom = editor.view.dom

                    this.onEditorKeydown = (event) => this.handleEditorKeydown(event)
                    this.onEditorUpdate = () => this.syncMentionQuery()

                    this.editorDom.addEventListener('keydown', this.onEditorKeydown, true)
                    editor.on('update', this.onEditorUpdate)
                    editor.on('selectionUpdate', this.onEditorUpdate)
                },

                unbindEditorListeners() {
                    if (this.editorDom && this.onEditorKeydown) {
                        this.editorDom.removeEventListener('keydown', this.onEditorKeydown, true)
                    }

                    if (this.boundEditor && this.onEditorUpdate) {
                        this.boundEditor.off('update', this.onEditorUpdate)
                        this.boundEditor.off('selectionUpdate', this.onEditorUpdate)
                    }

                    this.boundEditor = null
                    this.editorHostEl = null
                    this.editorDom = null
                },

                resolveMentionRange(editor = this.resolveEditor()) {
                    if (! editor) {
                        return null
                    }

                    const { from } = editor.state.selection
                    const textBefore = editor.state.doc.textBetween(
                        Math.max(0, from - 200),
                        from,
                        '\n',
                        '\n',
                    )
                    const match = textBefore.match(/(^|\s)@([^\s@]*)$/)

                    if (! match) {
                        return null
                    }

                    const range = {
                        from: from - match[2].length - 1,
                        to: from,
                    }

                    this.mentionStart = range.from
                    this.mentionRange = range
                    this.query = match[2]

                    return range
                },

                syncMentionQuery() {
                    if (this.isPicking) {
                        return
                    }

                    this.bindEditorListeners()

                    const range = this.resolveMentionRange()

                    if (! range) {
                        this.close()

                        return
                    }

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
                        this.resolveMentionRange()
                        this.updatePosition()
                    }, 200)
                },

                handleEditorKeydown(event) {
                    if (this.isPicking) {
                        return
                    }

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
                        this.pickUser(this.users[this.selectedIndex])

                        return
                    }

                    if (event.key === 'Escape') {
                        event.preventDefault()
                        event.stopPropagation()
                        this.close()
                    }
                },

                pickUser(user) {
                    this.isPicking = true

                    try {
                        this.selectUser(user)
                    } finally {
                        this.isPicking = false
                    }
                },

                selectUser(user) {
                    const editor = this.resolveEditor()

                    if (! editor || ! user) {
                        return
                    }

                    const range = this.resolveMentionRange(editor)

                    if (! range) {
                        return
                    }

                    editor
                        .chain()
                        .focus()
                        .deleteRange({ from: range.from, to: range.to })
                        .insertContentAt(range.from, [
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
                    const editor = this.resolveEditor()

                    if (! editor || this.mentionStart === null) {
                        return
                    }

                    try {
                        const coords = editor.view.coordsAtPos(this.mentionStart)
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
                    this.mentionRange = null
                    window.clearTimeout(this.searchTimer)
                },
            }))
        })
    </script>
@endonce
