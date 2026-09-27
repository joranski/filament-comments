<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

/**
 * Composer configuration shared by the Flux comment panel and host-app rich editors.
 */
final class CommentComposerField
{
    /**
     * Rich editor button names (config `rich_editor.toolbar_buttons`) mapped to Flux editor toolbar items.
     *
     * @var array<string, string>
     */
    private const FLUX_TOOLBAR_ITEMS = [
        'heading' => 'heading',
        'h1' => 'heading',
        'h2' => 'heading',
        'h3' => 'heading',
        'bold' => 'bold',
        'italic' => 'italic',
        'underline' => 'underline',
        'strike' => 'strike',
        'subscript' => 'subscript',
        'superscript' => 'superscript',
        'highlight' => 'highlight',
        'code' => 'code',
        'codeBlock' => 'code',
        'link' => 'link',
        'blockquote' => 'blockquote',
        'bulletList' => 'bullet',
        'orderedList' => 'ordered',
        'alignStart' => 'align',
        'alignCenter' => 'align',
        'alignEnd' => 'align',
        'undo' => 'undo',
        'redo' => 'redo',
    ];

    /**
     * Toolbar button groups, with `attachFiles` appended when attachments are enabled.
     *
     * @return list<list<string>>
     */
    public static function toolbarButtons(?CommentAttachmentContext $context = null): array
    {
        $configured = config('filament-comments.rich_editor.toolbar_buttons');

        $buttons = is_array($configured) && $configured !== []
            ? $configured
            : CommentAttachmentDefaults::toolbarButtons();

        if (
            CommentAttachments::enabled(context: $context)
            && (bool) config('filament-comments.rich_editor.append_attach_files_when_enabled', true)
            && ! self::toolbarIncludesAttachFiles(buttons: $buttons)
        ) {
            $buttons[] = ['attachFiles'];
        }

        return $buttons;
    }

    /**
     * Toolbar definition for `<flux:editor toolbar="...">`: groups are separated by `|`,
     * duplicate and unsupported buttons (e.g. `attachFiles`) are dropped.
     */
    public static function fluxToolbar(): string
    {
        $seen = [];
        $groups = [];

        foreach (self::toolbarButtons() as $group) {
            $items = [];

            foreach ((array) $group as $button) {
                $item = self::FLUX_TOOLBAR_ITEMS[(string) $button] ?? null;

                if ($item === null || isset($seen[$item])) {
                    continue;
                }

                $seen[$item] = true;
                $items[] = $item;
            }

            if ($items !== []) {
                $groups[] = implode(' ', $items);
            }
        }

        return implode(' | ', $groups);
    }

    public static function textareaRows(string $layout, ?bool $compactProfile = null): int
    {
        return match (true) {
            $compactProfile === true => 2,
            $layout === 'compact' => 3,
            default => 4,
        };
    }

    /**
     * @param  list<list<string>>  $buttons
     */
    private static function toolbarIncludesAttachFiles(array $buttons): bool
    {
        foreach ($buttons as $group) {
            if (is_array($group) && in_array('attachFiles', $group, true)) {
                return true;
            }
        }

        return false;
    }
}
