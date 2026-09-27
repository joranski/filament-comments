<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Who may manage the package settings (UI density, AI toggles).
 *
 * Roles come from `filament-comments.settings.export.roles` and are checked as roles
 * (`hasAnyRole()`, e.g. spatie/laravel-permission), never as Gate abilities. With an empty
 * role list, or a user model without role support, access falls back to comment viewAny.
 */
final class CommentSettingsAuthorization
{
    /**
     * @return list<string>
     */
    public static function roles(): array
    {
        $roles = config('filament-comments.settings.export.roles');

        if (! is_array($roles)) {
            return ['super_admin', 'admin'];
        }

        return array_values(array_filter(array_map(strval(...), $roles), filled(...)));
    }

    public static function canManage(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        $roles = self::roles();

        if ($roles !== [] && method_exists($user, 'hasAnyRole')) {
            return (bool) $user->hasAnyRole($roles);
        }

        return CommentAuthorization::canViewAny($user);
    }
}
