<?php

declare(strict_types=1);

use Joranski\FilamentComments\Comments\Livewire\CommentPanel;

test('comment panel renders a heading-aware skeleton while lazy loading', function (): void {
    $panel = app(CommentPanel::class);

    $html = $panel->placeholder(['heading' => 'Customer Notes'])->render();

    expect($html)
        ->toContain('fi-comments-panel-placeholder')
        ->toContain('aria-busy="true"')
        ->toContain('Customer Notes')
        ->toContain('Loading comments');
});

test('comment panel placeholder hides the heading when the panel would', function (): void {
    $panel = app(CommentPanel::class);

    $html = $panel->placeholder(['heading' => 'Hidden Heading', 'showHeading' => false])->render();

    expect($html)
        ->toContain('fi-comments-panel-placeholder')
        ->not->toContain('Hidden Heading');
});
