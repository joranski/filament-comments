<?php

declare(strict_types=1);

use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Joranski\FilamentComments\Support\CommentUi;

test('comment panel accepts compactProfile mount parameter', function (): void {
    $commentable = \TestCommentable::factory()->create();

    $panel = app(CommentPanel::class);
    $panel->mount(record: $commentable, compactProfile: true);

    expect($panel->compactProfile)->toBeTrue()
        ->and($panel->usesCompactProfile())->toBeTrue()
        ->and($panel->usesRichEditor())->toBeTrue();
});

test('comment panel compact method toggles condensed profile', function (): void {
    $panel = app(CommentPanel::class);

    $panel->compact();

    expect($panel->usesCompactProfile())->toBeTrue()
        ->and($panel->uiCompactProfileContext())->toBeTrue()
        ->and(CommentUi::panelClasses($panel->uiCompactProfileContext()))
        ->toContain('fi-comments-ui-condensed');
});

test('compact profile comment panel exposes mention search and label lookup', function (): void {
    $current = \User::factory()->create(['name' => 'Current User']);
    $other = \User::factory()->create(['name' => 'Jordan Analyst']);

    $this->actingAs($current);

    $panel = app(CommentPanel::class);
    $panel->mount(record: \TestCommentable::factory()->create(), compactProfile: true);

    expect($panel->searchMentionUsers(query: 'Jord'))->toBe([['id' => $other->id, 'name' => 'Jordan Analyst']])
        ->and($panel->getCommentMentionLabelsForJs(mentions: [['id' => $other->id, 'char' => '@']]))
        ->toBe([(string) $other->id => 'Jordan Analyst']);
});
