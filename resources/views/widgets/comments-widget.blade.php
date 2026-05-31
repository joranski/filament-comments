<x-filament-widgets::widget>
    <x-filament::section :heading="$showHeading ? __($heading) : null">
        @livewire(
            \Joranski\FilamentComments\Comments\Livewire\CommentPanel::class,
            [
                'record' => $record,
                'layout' => $layout,
                'group' => $group,
                'topic' => $topic,
                'excludeGroup' => $excludeGroup,
                'excludedGroups' => $excludedGroups ?? config('filament-comments.excluded_groups', []),
                'heading' => $heading,
                'showHeading' => false,
                'threadMaxHeight' => $threadMaxHeight,
            ],
            key('comments-widget-'.($record?->getKey() ?? 'new').'-'.($group ?? 'all').'-'.($topic ?? 'all'))
        )
    </x-filament::section>
</x-filament-widgets::widget>
