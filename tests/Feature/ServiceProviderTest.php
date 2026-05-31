<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Livewire\Livewire;

test('service provider registers the comment panel livewire component', function (): void {
    expect(app('livewire.factory')->create(name: 'filament-comments.comment-panel'))
        ->toBeInstanceOf(CommentPanel::class);
});

test('service provider loads the comments table migration', function (): void {
    expect(Schema::hasTable('comments'))->toBeTrue()
        ->and(Schema::hasColumn('comments', 'parent_id'))->toBeTrue()
        ->and(Schema::hasColumn('comments', 'mentioned_user_ids'))->toBeTrue();
});
