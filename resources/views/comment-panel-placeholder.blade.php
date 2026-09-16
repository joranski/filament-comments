{{-- Lazy-load skeleton for CommentPanel; mirrors the heading + composer + a few thread rows. --}}
<div class="fi-comments-panel fi-comments-panel-placeholder flex flex-col gap-4 animate-pulse" aria-busy="true" aria-label="{{ __('Loading comments') }}">
    @if ($showHeading && filled($heading))
        <flux:heading size="sm">{{ __($heading) }}</flux:heading>
    @endif

    <div class="h-20 rounded-lg border border-zinc-200 dark:border-white/10 bg-zinc-100/60 dark:bg-white/5"></div>

    <div class="flex flex-col gap-3">
        @foreach (range(1, 3) as $row)
            <div class="flex items-start gap-3">
                <div class="size-8 shrink-0 rounded-full bg-zinc-200 dark:bg-zinc-800"></div>
                <div class="flex-1 space-y-2 pt-1">
                    <div class="h-3 w-32 rounded bg-zinc-200 dark:bg-zinc-800"></div>
                    <div class="h-3 w-full rounded bg-zinc-100 dark:bg-zinc-800/80"></div>
                    <div class="h-3 w-2/3 rounded bg-zinc-100 dark:bg-zinc-800/80"></div>
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-center text-xs text-zinc-400">{{ __('Loading comments…') }}</p>
</div>
