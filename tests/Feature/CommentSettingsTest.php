<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Joranski\FilamentComments\Models\FilamentCommentsSetting;
use Joranski\FilamentComments\Services\FilamentCommentsSettings;
use Joranski\FilamentComments\Support\CommentSettingsAuthorization;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('settings cache only the row id, never the model', function (): void {
    $settings = app(FilamentCommentsSettings::class)->current();

    expect(Cache::get('filament-comments.settings-id'))->toBe($settings->getKey());
});

test('settings survive a cache store that refuses to unserialize objects', function (): void {
    config()->set('cache.stores.array.serialize', true);
    config()->set('cache.serializable_classes', false);
    Cache::forgetDriver('array');

    (new FilamentCommentsSettings)->update(['compact_toolbar' => true]);

    $fresh = new FilamentCommentsSettings;

    expect($fresh->current())->toBeInstanceOf(FilamentCommentsSetting::class)
        ->and($fresh->compactToolbar())->toBeTrue()
        ->and(FilamentCommentsSetting::query()->count())->toBe(1);
});

test('a stale cached id falls back to the existing row', function (): void {
    $settings = app(FilamentCommentsSettings::class)->current();
    Cache::forever('filament-comments.settings-id', 999999);

    expect((new FilamentCommentsSettings)->current()->getKey())->toBe($settings->getKey());
});

test('updates are visible through a fresh service instance', function (): void {
    app(FilamentCommentsSettings::class)->update(['compact_action_icons' => true, 'ai_proofread_default' => false]);

    $fresh = new FilamentCommentsSettings;

    expect($fresh->compactActionIcons())->toBeTrue()
        ->and($fresh->aiProofreadDefault())->toBeFalse();
});

test('settings access requires one of the configured roles, not a same-named ability', function (): void {
    Role::query()->create(['name' => 'admin', 'guard_name' => 'web']);
    Permission::query()->create(['name' => 'admin', 'guard_name' => 'web']);

    $admin = \User::factory()->create();
    $admin->assignRole('admin');

    $abilityOnly = \User::factory()->create();
    $abilityOnly->givePermissionTo('admin');

    expect(CommentSettingsAuthorization::roles())->toBe(['super_admin', 'admin'])
        ->and(CommentSettingsAuthorization::canManage($admin))->toBeTrue()
        ->and(CommentSettingsAuthorization::canManage($abilityOnly))->toBeFalse()
        ->and(CommentSettingsAuthorization::canManage(null))->toBeFalse();
});

test('an empty role list falls back to comment view access', function (): void {
    config()->set('filament-comments.settings.export.roles', []);

    expect(CommentSettingsAuthorization::canManage(\User::factory()->create()))->toBeTrue();

    config()->set('filament-comments.authorization.fallback.view_any', false);

    expect(CommentSettingsAuthorization::canManage(\User::factory()->create()))->toBeFalse();
});
