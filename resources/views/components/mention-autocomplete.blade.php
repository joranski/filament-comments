@props([
    'statePath',
])

<div
    x-data="commentMentionAutocomplete(@js($statePath))"
    x-on:input.capture="if ($event.target.tagName === 'TEXTAREA') { textarea = $event.target; onInput() }"
    x-on:keydown.capture="if ($event.target.tagName === 'TEXTAREA') { textarea = $event.target; onKeydown($event) }"
    x-on:click.capture="if ($event.target.tagName === 'TEXTAREA') { textarea = $event.target; onInput() }"
    x-on:focusout.capture="if ($event.target.tagName === 'TEXTAREA') { window.setTimeout(() => close(), 150) }"
    class="relative"
>
    {{ $slot }}

    <div
        x-cloak
        x-show="open && users.length > 0"
        x-transition.opacity
        class="fixed z-[100] max-h-60 w-72 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-white/10 dark:bg-zinc-900"
        :style="`top: ${position.top}px; left: ${position.left}px;`"
        role="listbox"
        aria-label="{{ __('Mention suggestions') }}"
    >
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

    <div
        x-cloak
        x-show="open && loading"
        class="fixed z-[100] w-72 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-500 shadow-lg dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-400"
        :style="`top: ${position.top}px; left: ${position.left}px;`"
    >
        {{ __('Searching users…') }}
    </div>

    <div
        x-cloak
        x-show="open && ! loading && users.length === 0"
        class="fixed z-[100] w-72 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-500 shadow-lg dark:border-white/10 dark:bg-zinc-900 dark:text-zinc-400"
        :style="`top: ${position.top}px; left: ${position.left}px;`"
    >
        {{ __('No users found.') }}
    </div>
</div>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            if (window.__commentMentionAutocompleteRegistered) {
                return
            }

            window.__commentMentionAutocompleteRegistered = true

            Alpine.data('commentMentionAutocomplete', (statePath) => ({
                statePath,
                open: false,
                loading: false,
                query: '',
                users: [],
                selectedIndex: 0,
                mentionStart: null,
                textarea: null,
                searchTimer: null,
                position: { top: 0, left: 0 },

                onInput() {
                    if (! this.textarea) {
                        return
                    }

                    const cursor = this.textarea.selectionStart ?? 0
                    const before = this.textarea.value.slice(0, cursor)
                    const match = before.match(/(^|\s)@([^\s@]*)$/)

                    if (! match) {
                        this.close()

                        return
                    }

                    this.mentionStart = cursor - match[2].length - 1
                    this.query = match[2]
                    this.open = true
                    this.updatePosition()
                    this.scheduleSearch()
                },

                scheduleSearch() {
                    window.clearTimeout(this.searchTimer)

                    this.loading = true

                    this.searchTimer = window.setTimeout(async () => {
                        this.users = await this.$wire.searchMentionUsers(this.query)
                        this.selectedIndex = 0
                        this.loading = false
                        this.updatePosition()
                    }, 200)
                },

                onKeydown(event) {
                    if (! this.open) {
                        return
                    }

                    if (event.key === 'ArrowDown') {
                        event.preventDefault()
                        this.selectedIndex = Math.min(this.selectedIndex + 1, this.users.length - 1)

                        return
                    }

                    if (event.key === 'ArrowUp') {
                        event.preventDefault()
                        this.selectedIndex = Math.max(this.selectedIndex - 1, 0)

                        return
                    }

                    if (event.key === 'Enter' && this.users[this.selectedIndex]) {
                        event.preventDefault()
                        this.selectUser(this.users[this.selectedIndex])

                        return
                    }

                    if (event.key === 'Escape') {
                        event.preventDefault()
                        this.close()
                    }
                },

                selectUser(user) {
                    if (! this.textarea || this.mentionStart === null) {
                        return
                    }

                    const cursor = this.textarea.selectionStart ?? this.mentionStart
                    const before = this.textarea.value.slice(0, this.mentionStart)
                    const after = this.textarea.value.slice(cursor)
                    const insertion = '@' + user.name + ' '
                    const nextValue = before + insertion + after
                    const nextCursor = before.length + insertion.length

                    this.$wire.set(this.statePath, nextValue, false)
                    this.textarea.value = nextValue
                    this.textarea.focus()
                    this.textarea.setSelectionRange(nextCursor, nextCursor)
                    this.close()
                },

                updatePosition() {
                    if (! this.textarea) {
                        return
                    }

                    const rect = this.textarea.getBoundingClientRect()
                    const top = Math.min(rect.bottom + 8, window.innerHeight - 260)
                    const left = Math.min(rect.left, window.innerWidth - 300)

                    this.position = { top, left }
                },

                close() {
                    this.open = false
                    this.loading = false
                    this.query = ''
                    this.users = []
                    this.mentionStart = null
                    window.clearTimeout(this.searchTimer)
                },
            }))
        })
    </script>
@endonce
