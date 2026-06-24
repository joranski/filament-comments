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

test('mention provider seeds initial users for rich editor dropdown', function (): void {
    $current = \User::factory()->create(['name' => 'Current User']);
    $other = \User::factory()->create(['name' => 'Other User']);

    $this->actingAs($current);

    $provider = CommentMentionProvider::make();

    expect($provider->getItems())->toHaveKey((string) $other->id)
        ->and($provider->getItems())->not->toHaveKey((string) $current->id)
        ->and($provider->getSearchResults(search: ''))->toHaveKey((string) $other->id);
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
    $user = \User::factory()->create(['name' => 'Jordan Analyst']);

    $html = '<p>Hello <span data-type="mention" data-id="'.$user->id.'" data-label="Jordan Analyst" data-char="@">@Jordan Analyst</span></p>';

    expect(app(CommentMentionParser::class)->parseUserIds($html))->toBe([(int) $user->id]);
});

test('mention provider excludes users outside the configured mention scope', function (): void {
    config(['filament-comments.mention_user_scope' => 'employee']);

    $staff = \User::factory()->create(['name' => 'Staff Member']);
    $staff->assignRole(\Spatie\Permission\Models\Role::create([
        'name' => 'staff',
        'guard_name' => 'web',
    ]));

    \User::factory()->create(['name' => 'Shop Customer']);

    $this->actingAs($staff);

    expect(CommentMentionProvider::search(search: 'Shop'))->toBe([]);
});

test('mention provider includes other scoped users but not unscoped users', function (): void {
    config(['filament-comments.mention_user_scope' => 'employee']);

    $author = \User::factory()->create(['name' => 'Author Staff']);
    $author->assignRole(\Spatie\Permission\Models\Role::create([
        'name' => 'author',
        'guard_name' => 'web',
    ]));

    $otherStaff = \User::factory()->create(['name' => 'Jordan Analyst']);
    $otherStaff->assignRole(\Spatie\Permission\Models\Role::create([
        'name' => 'analyst',
        'guard_name' => 'web',
    ]));

    \User::factory()->create(['name' => 'Jordan Customer']);

    $this->actingAs($author);

    expect(CommentMentionProvider::search(search: 'Jord'))->toHaveKey((string) $otherStaff->id)
        ->and(CommentMentionProvider::search(search: 'Jord'))->toHaveCount(1);
});
