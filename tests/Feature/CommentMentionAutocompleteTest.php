<?php

declare(strict_types=1);

use Joranski\FilamentComments\Support\CommentMentionParser;
use Joranski\FilamentComments\Support\CommentMentionProvider;

test('mention provider search excludes the authenticated user', function (): void {
    $current = \User::factory()->create(['name' => 'Current User']);
    $other = \User::factory()->create(['name' => 'Other User']);

    $this->actingAs($current);

    $results = CommentMentionProvider::search(search: '');

    expect($results)->toHaveKey($other->id)
        ->and($results)->not->toHaveKey($current->id);
});

test('mention provider filters users as the query grows', function (): void {
    \User::factory()->create(['name' => 'Jordan Analyst']);
    \User::factory()->create(['name' => 'Jamie Smith']);

    $this->actingAs(\User::factory()->create());

    expect(CommentMentionProvider::search(search: 'Jord'))->toHaveCount(1)
        ->and(CommentMentionProvider::search(search: 'Jamie'))->toHaveCount(1)
        ->and(CommentMentionProvider::search(search: 'Nobody'))->toBe([]);
});

test('mention parser extracts rich editor mention ids from html', function (): void {
    $html = '<p>Hello <span data-type="mention" data-id="12" data-label="Jordan Analyst" data-char="@">@Jordan Analyst</span></p>';

    expect(app(CommentMentionParser::class)->parseUserIds($html))->toBe([12]);
});
