{{--
    @-mention autocomplete for a wrapped <flux:editor>. Reads the TipTap instance Flux exposes on
    <ui-editor>, searches through the parent Livewire component's `searchMentionUsers()` and inserts
    plain `@Name ` text, which CommentMentionParser resolves to user ids on save.
--}}
@assets
    <script>
        (() => {
            const register = () => {
                if (window.__commentEditorMentionsRegistered) {
                    return
                }

                window.__commentEditorMentionsRegistered = true

                window.Alpine.data('commentEditorMentions', () => {
                    // Kept outside Alpine's reactive state: proxying the TipTap editor breaks it.
                    let editor = null

                    return {
                        open: false,
                        loading: false,
                        users: [],
                        selectedIndex: 0,
                        range: null,
                        searchTimer: null,
                        position: { top: 0, left: 0 },

                        init() {
                            const host = this.$el.querySelector('ui-editor')

                            if (host?.editor) {
                                this.bind(host.editor)

                                return
                            }

                            this.$el.addEventListener('flux:editor:ready', (event) => this.bind(event.detail.editor))
                        },

                        bind(instance) {
                            if (! instance || editor === instance) {
                                return
                            }

                            editor = instance
                            editor.on('update', () => this.detect())
                            editor.on('selectionUpdate', () => this.detect())
                            editor.on('blur', () => window.setTimeout(() => this.close(), 150))
                        },

                        detect() {
                            const selection = editor?.state.selection

                            if (! selection || ! selection.empty) {
                                this.close()

                                return
                            }

                            const $from = selection.$from
                            const before = $from.parent.textBetween(Math.max(0, $from.parentOffset - 64), $from.parentOffset, null, '\ufffc')
                            const match = before.match(/(^|\s)@([^\s@]{0,40})$/)

                            if (! match) {
                                this.close()

                                return
                            }

                            this.range = { from: selection.from - match[2].length - 1, to: selection.from }
                            this.open = true
                            this.updatePosition()
                            this.scheduleSearch(match[2])
                        },

                        scheduleSearch(query) {
                            window.clearTimeout(this.searchTimer)

                            this.loading = true

                            this.searchTimer = window.setTimeout(async () => {
                                this.users = await this.$wire.searchMentionUsers(query)
                                this.selectedIndex = 0
                                this.loading = false
                                this.updatePosition()
                            }, 200)
                        },

                        onKeydown(event) {
                            if (! this.open || this.users.length === 0) {
                                if (this.open && event.key === 'Escape') {
                                    event.preventDefault()
                                    event.stopPropagation()
                                    this.close()
                                }

                                return
                            }

                            const handled = {
                                ArrowDown: () => this.selectedIndex = Math.min(this.selectedIndex + 1, this.users.length - 1),
                                ArrowUp: () => this.selectedIndex = Math.max(this.selectedIndex - 1, 0),
                                Enter: () => this.pick(this.users[this.selectedIndex]),
                                Tab: () => this.pick(this.users[this.selectedIndex]),
                                Escape: () => this.close(),
                            }[event.key]

                            if (handled) {
                                event.preventDefault()
                                event.stopPropagation()
                                handled()
                            }
                        },

                        pick(user) {
                            if (! editor || ! this.range || ! user) {
                                return
                            }

                            editor.chain().focus().insertContentAt(this.range, '@' + user.name + ' ').run()
                            this.close()
                        },

                        updatePosition() {
                            if (! editor || ! this.range) {
                                return
                            }

                            const coords = editor.view.coordsAtPos(this.range.to)

                            this.position = {
                                top: Math.min(coords.bottom + 6, window.innerHeight - 260),
                                left: Math.min(coords.left, window.innerWidth - 300),
                            }
                        },

                        close() {
                            window.clearTimeout(this.searchTimer)
                            this.open = false
                            this.loading = false
                            this.users = []
                            this.range = null
                        },
                    }
                })
            }

            window.Alpine ? register() : document.addEventListener('alpine:init', register)
        })()
    </script>
@endassets

<div
    x-data="commentEditorMentions"
    x-on:keydown.capture="onKeydown($event)"
    class="fi-comments-editor-mentions relative"
>
    {{ $slot }}

    <template x-teleport="body">
        <div
            x-cloak
            x-show="open"
            x-transition.opacity
            class="fi-comments-mention-dropdown fixed z-50 max-h-60 w-72 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-white/10 dark:bg-zinc-900"
            :style="`top: ${position.top}px; left: ${position.left}px;`"
            role="listbox"
            aria-label="{{ __('Mention suggestions') }}"
            x-on:mousedown.prevent
        >
            <div x-show="loading" class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Searching users…') }}
            </div>

            <div x-show="! loading && users.length === 0" class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('No users found.') }}
            </div>

            <template x-for="(user, index) in users" :key="user.id">
                <button
                    type="button"
                    x-show="! loading"
                    class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-white/5"
                    :class="{ 'bg-zinc-50 dark:bg-white/5': index === selectedIndex }"
                    x-on:click="pick(user)"
                    role="option"
                    :aria-selected="index === selectedIndex"
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
