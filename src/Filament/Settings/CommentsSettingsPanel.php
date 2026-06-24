<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Filament\Settings;

use Filament\Schemas\Components\Section;
use Illuminate\Contracts\Auth\Authenticatable;
use Joranski\FilamentEmails\Contracts\FilamentPackageSettingsPanel;
use Joranski\FilamentComments\Filament\Schemas\CommentsSettingsForm;
use Joranski\FilamentComments\Services\FilamentCommentsSettings;
use Joranski\FilamentComments\Support\CommentAuthorization;

/**
 * Exportable settings panel for filament-comments (master Package Settings page).
 *
 * Requires `joranski/filament-emails` for {@see FilamentPackageSettingsPanel} registration.
 */
final class CommentsSettingsPanel implements FilamentPackageSettingsPanel
{
    public static function id(): string
    {
        return 'filament-comments';
    }

    public static function label(): string
    {
        return 'Comments';
    }

    public static function description(): ?string
    {
        return 'UI density, AI proofread defaults, and Shield integration notes.';
    }

    public static function sort(): int
    {
        return (int) config('filament-comments.settings.export.sort', 30);
    }

    public static function roles(): array
    {
        $roles = config('filament-comments.settings.export.roles');

        return is_array($roles) ? array_values($roles) : ['super_admin', 'admin'];
    }

    public static function canAccess(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        $roles = self::roles();

        if ($roles !== [] && method_exists($user, 'canAny')) {
            return $user->canAny($roles);
        }

        if ($roles !== [] && method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($roles);
        }

        return CommentAuthorization::canViewAny($user);
    }

    public static function schema(): array
    {
        return CommentsSettingsForm::components();
    }

    public static function embedSection(?string $statePath = null): Section
    {
        $section = Section::make(self::label())
            ->description(self::description())
            ->schema(self::schema());

        if ($statePath !== null && $statePath !== '') {
            $section->statePath($statePath);
        }

        return $section;
    }

    public static function defaultState(): array
    {
        $settings = app(FilamentCommentsSettings::class)->current();

        return [
            'compact_toolbar' => $settings->compact_toolbar,
            'compact_action_icons' => $settings->compact_action_icons,
            'ai_proofread_enabled' => $settings->ai_proofread_enabled,
            'ai_proofread_default' => $settings->ai_proofread_default,
            'ai_summarize_threads' => $settings->ai_summarize_threads,
        ];
    }

    public static function save(array $state): void
    {
        app(FilamentCommentsSettings::class)->update($state);
    }
}
