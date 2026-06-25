<?php

declare(strict_types=1);

use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Joranski\FilamentComments\Support\CommentMentionProvider;

test('compact profile comment panel resolves rich editor mention search', function (): void {
    $current = \User::factory()->create(['name' => 'Current User']);
    $other = \User::factory()->create(['name' => 'Jordan Analyst']);

    $this->actingAs($current);

    $commentable = \TestCommentable::factory()->create();

    $panel = app(CommentPanel::class);
    $panel->mount(record: $commentable, compactProfile: true);

    expect($panel->usesCompactProfile())->toBeTrue()
        ->and($panel->usesRichEditor())->toBeTrue()
        ->and($panel->searchMentionUsers(query: 'Jord'))->toHaveCount(1);

    $body = $panel->getCachedSchemas()['form']->getComponent('body');

    expect($body)->toBeInstanceOf(\Joranski\FilamentComments\Forms\Components\CommentRichEditor::class)
        ->and($body->getMentionSearchResultsForJs(search: 'Jord', char: '@'))
        ->toHaveKey((string) $other->id);
});

test('compact profile comment panel exposes panel-level mention label lookup', function (): void {
    $current = \User::factory()->create(['name' => 'Current User']);
    $other = \User::factory()->create(['name' => 'Jordan Analyst']);

    $this->actingAs($current);

    $commentable = \TestCommentable::factory()->create();

    $panel = app(CommentPanel::class);
    $panel->mount(record: $commentable, compactProfile: true);

    expect($panel->getCommentMentionLabelsForJs(mentions: [
        ['id' => $other->id, 'char' => '@'],
    ]))->toBe([
        (string) $other->id => 'Jordan Analyst',
    ]);
});

test('compact profile mention provider includes initial users for empty query', function (): void {
    $current = \User::factory()->create(['name' => 'Current User']);
    $other = \User::factory()->create(['name' => 'Jordan Analyst']);

    $this->actingAs($current);

    expect(CommentMentionProvider::make()->getItems())->toHaveKey((string) $other->id);
});

test('comment rich editor mention bridge view is registered', function (): void {
    expect(view()->exists('filament-comments::components.rich-editor-mention-bridge'))->toBeTrue();

    $richEditor = file_get_contents(__DIR__.'/../../resources/views/components/rich-editor.blade.php');
    $bridge = file_get_contents(__DIR__.'/../../resources/views/components/rich-editor-mention-bridge.blade.php');

    expect($richEditor)->toContain('rich-editor-mention-bridge')
        ->and($bridge)->toContain('commentRichEditorMentionBridge')
        ->and($bridge)->toContain('fi-comments-mention-dropdown')
        ->and($bridge)->toContain('x-teleport="body"')
        ->and($bridge)->toContain('mentionRange')
        ->and($bridge)->toContain('pickUser(user)')
        ->and($bridge)->toContain('resolveMentionRange')
        ->and($bridge)->toContain('resolveEditor()');
});
