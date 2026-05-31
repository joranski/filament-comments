<?php

declare(strict_types=1);

use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Livewire\Livewire;

test('service provider registers the comment panel livewire component', function (): void {
    expect(app('livewire.factory')->create(name: 'filament-comments.comment-panel'))
        ->toBeInstanceOf(CommentPanel::class);
});
