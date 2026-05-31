<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Joranski\FilamentComments\Models\Comment;
use Joranski\FilamentComments\Support\CommentAuthorization;
use Joranski\FilamentComments\Tests\Support\Policies\ShieldCommentPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('comment authorization uses fallback rules when no policy is registered', function (): void {
    config([
        'filament-comments.authorization.mode' => 'fallback',
    ]);

    $user = \User::factory()->create();
    $other = \User::factory()->create();
    $commentable = \TestCommentable::factory()->create();

    $comment = $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => '<p>Owned note</p>',
        'active' => true,
    ]);

    expect(CommentAuthorization::canCreate($user))->toBeTrue()
        ->and(CommentAuthorization::canViewAny($user))->toBeTrue()
        ->and(CommentAuthorization::canView($comment, $user))->toBeTrue()
        ->and(CommentAuthorization::canUpdate($comment, $user))->toBeTrue()
        ->and(CommentAuthorization::canUpdate($comment, $other))->toBeFalse()
        ->and(CommentAuthorization::canDelete($comment, $user))->toBeTrue()
        ->and(CommentAuthorization::canDelete($comment, $other))->toBeFalse()
        ->and(CommentAuthorization::canPin($comment, $user))->toBeTrue();
});

test('comment authorization auto mode delegates to registered policies', function (): void {
    config([
        'filament-comments.authorization.mode' => 'auto',
        'filament-comments.authorization.author_may_update_own' => false,
        'filament-comments.authorization.author_may_delete_own' => false,
    ]);

    Gate::policy(Comment::class, ShieldCommentPolicy::class);

    $role = Role::query()->firstOrCreate(['name' => 'comment-reader', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'ViewAny:Comment', 'guard_name' => 'web']);
    $role->syncPermissions(['ViewAny:Comment']);

    $reader = \User::factory()->create();
    $reader->assignRole($role);

    $author = \User::factory()->create();
    $commentable = \TestCommentable::factory()->create();

    $comment = $commentable->comments()->create([
        'user_id' => $author->id,
        'comment' => '<p>Protected note</p>',
        'active' => true,
    ]);

    expect(CommentAuthorization::usesPolicyChecks())->toBeTrue()
        ->and(CommentAuthorization::canViewAny($reader))->toBeTrue()
        ->and(CommentAuthorization::canView($comment, $reader))->toBeFalse()
        ->and(CommentAuthorization::canCreate($reader))->toBeFalse()
        ->and(CommentAuthorization::canUpdate($comment, $author))->toBeFalse()
        ->and(CommentAuthorization::canDelete($comment, $author))->toBeFalse();
});

test('comment authorization allows authors to edit own comments when configured with shield policy', function (): void {
    config([
        'filament-comments.authorization.mode' => 'auto',
        'filament-comments.authorization.author_may_update_own' => true,
        'filament-comments.authorization.author_may_delete_own' => true,
    ]);

    Gate::policy(Comment::class, ShieldCommentPolicy::class);

    $author = \User::factory()->create();
    $commentable = \TestCommentable::factory()->create();

    $comment = $commentable->comments()->create([
        'user_id' => $author->id,
        'comment' => '<p>Author owned</p>',
        'active' => true,
    ]);

    expect(CommentAuthorization::canUpdate($comment, $author))->toBeTrue()
        ->and(CommentAuthorization::canDelete($comment, $author))->toBeFalse();
});

test('comment authorization grants create when user has shield create permission', function (): void {
    config(['filament-comments.authorization.mode' => 'auto']);

    Gate::policy(Comment::class, ShieldCommentPolicy::class);

    $role = Role::query()->firstOrCreate(['name' => 'comment-writer', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'Create:Comment', 'guard_name' => 'web']);
    $role->syncPermissions(['Create:Comment']);

    $writer = \User::factory()->create();
    $writer->assignRole($role);

    expect(CommentAuthorization::canCreate($writer))->toBeTrue();
});

test('comment authorization grants view when user has shield view permission', function (): void {
    config(['filament-comments.authorization.mode' => 'auto']);

    Gate::policy(Comment::class, ShieldCommentPolicy::class);

    $role = Role::query()->firstOrCreate(['name' => 'comment-viewer', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'ViewAny:Comment', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'View:Comment', 'guard_name' => 'web']);
    $role->syncPermissions(['ViewAny:Comment', 'View:Comment']);

    $viewer = \User::factory()->create();
    $viewer->assignRole($role);

    $commentable = \TestCommentable::factory()->create();

    $comment = $commentable->comments()->create([
        'user_id' => \User::factory()->create()->id,
        'comment' => '<p>Visible note</p>',
        'active' => true,
    ]);

    expect(CommentAuthorization::canViewAny($viewer))->toBeTrue()
        ->and(CommentAuthorization::canView($comment, $viewer))->toBeTrue()
        ->and(CommentAuthorization::canCreate($viewer))->toBeFalse();
});

test('comment authorization hides panel thread when viewAny is denied', function (): void {
    config(['filament-comments.authorization.mode' => 'auto']);

    Gate::policy(Comment::class, ShieldCommentPolicy::class);

    $role = Role::query()->firstOrCreate(['name' => 'comment-creator-only', 'guard_name' => 'web']);
    Permission::query()->firstOrCreate(['name' => 'Create:Comment', 'guard_name' => 'web']);
    $role->syncPermissions(['Create:Comment']);

    $writer = \User::factory()->create();
    $writer->assignRole($role);

    expect(CommentAuthorization::canViewAny($writer))->toBeFalse()
        ->and(CommentAuthorization::canCreate($writer))->toBeTrue();
});
