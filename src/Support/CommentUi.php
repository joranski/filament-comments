<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Filament\Forms\Components\RichEditor;
use Joranski\FilamentComments\Services\FilamentCommentsSettings;

/**
 * UI density helpers for RichEditor toolbars, thread action buttons, and condensed panels.
 *
 * Pass `$compactProfile = true` to force a per-panel condensed profile (via {@see CommentPanel::compact()}).
 * When omitted, global Package Settings / config toggles apply.
 */
final class CommentUi
{
    public static function compactToolbar(?bool $compactProfile = null): bool
    {
        if ($compactProfile === true) {
            return true;
        }

        if (app()->bound(FilamentCommentsSettings::class)) {
            return app(FilamentCommentsSettings::class)->compactToolbar();
        }

        return (bool) config('filament-comments.ui.compact_toolbar', false);
    }

    public static function compactActionIcons(?bool $compactProfile = null): bool
    {
        if ($compactProfile === true) {
            return true;
        }

        if (app()->bound(FilamentCommentsSettings::class)) {
            return app(FilamentCommentsSettings::class)->compactActionIcons();
        }

        return (bool) config('filament-comments.ui.compact_action_icons', false);
    }

    public static function isCondensed(?bool $compactProfile = null): bool
    {
        return $compactProfile === true;
    }

    /**
     * Flux button size for pin/reply/edit/delete actions.
     */
    public static function actionButtonSize(?bool $compactProfile = null): string
    {
        if (self::isCondensed($compactProfile)) {
            return 'xs';
        }

        return self::compactActionIcons($compactProfile) ? 'xs' : 'sm';
    }

    /**
     * Flux avatar size for root-level comments.
     */
    public static function rootAvatarSize(?bool $compactProfile = null): string
    {
        return self::isCondensed($compactProfile) ? 'xs' : 'sm';
    }

    /**
     * Wrapper classes applied to the comment panel root element.
     *
     * @return list<string>
     */
    public static function panelClasses(?bool $compactProfile = null): array
    {
        $classes = [];

        if (self::compactToolbar($compactProfile)) {
            $classes[] = 'fi-comments-ui-compact-toolbar';
        }

        if (self::compactActionIcons($compactProfile)) {
            $classes[] = 'fi-comments-ui-compact-actions';
        }

        if (self::isCondensed($compactProfile)) {
            $classes[] = 'fi-comments-ui-condensed';
        }

        return $classes;
    }

    public static function configureRichEditor(RichEditor $editor, ?bool $compactProfile = null): RichEditor
    {
        if (! self::compactToolbar($compactProfile)) {
            return $editor;
        }

        return $editor->extraAttributes([
            'class' => 'fi-comments-compact-rich-editor',
        ]);
    }
}
