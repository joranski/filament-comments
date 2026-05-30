<?php

declare(strict_types=1);

use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Livewire\LivewireManager;

test('service provider registers the comment panel livewire component', function (): void {
    /** @var LivewireManager $livewire */
    $livewire = app(LivewireManager::class);

    expect($livewire->getClassComponent('filament-comments.comment-panel'))
        ->toBe(CommentPanel::class);
});
