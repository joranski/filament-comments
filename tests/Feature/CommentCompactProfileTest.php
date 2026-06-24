<?php

declare(strict_types=1);

use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Joranski\FilamentComments\Comments\Schemas\CommentPanelSchema;
use Joranski\FilamentComments\Comments\Widgets\CommentsWidget;
use Joranski\FilamentComments\Support\CommentUi;

test('comment panel accepts compactProfile mount parameter', function (): void {
    $commentable = \TestCommentable::factory()->create();

    $panel = app(CommentPanel::class);
    $panel->mount(record: $commentable, compactProfile: true);

    expect($panel->compactProfile)->toBeTrue()
        ->and($panel->usesCompactProfile())->toBeTrue();
});

test('comment panel compact method toggles condensed profile', function (): void {
    $panel = app(CommentPanel::class);

    $panel->compact();

    expect($panel->usesCompactProfile())->toBeTrue()
        ->and($panel->uiCompactProfileContext())->toBeTrue()
        ->and(CommentUi::panelClasses($panel->uiCompactProfileContext()))
        ->toContain('fi-comments-ui-condensed');
});

test('comments widget compact method enables condensed profile', function (): void {
    $widget = new CommentsWidget;
    $widget->compact();

    expect($widget->compactProfile)->toBeTrue();
});

test('comments widget make accepts compactProfile property', function (): void {
    $configuration = CommentsWidget::make(['compactProfile' => true]);

    expect($configuration)->toBeInstanceOf(\Filament\Widgets\WidgetConfiguration::class);
});

test('comment panel schema passes compact profile to embedded livewire data', function (): void {
    $config = CommentPanelSchema::widgetConfiguration(compact: true);

    expect($config)->toHaveKey('compactProfile', true);
});
