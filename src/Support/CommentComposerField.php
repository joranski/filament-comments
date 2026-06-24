<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Joranski\FilamentComments\Forms\Components\CommentRichEditor;

final class CommentComposerField
{
    /**
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
            && ! static::toolbarIncludesAttachFiles(buttons: $buttons)
        ) {
            $buttons[] = ['attachFiles'];
        }

        return $buttons;
    }

    /**
     * @param  list<list<string>>  $buttons
     */
    protected static function toolbarIncludesAttachFiles(array $buttons): bool
    {
        foreach ($buttons as $group) {
            if (! is_array($group)) {
                continue;
            }

            if (in_array('attachFiles', $group, true)) {
                return true;
            }
        }

        return false;
    }

    public static function bodyField(
        bool $useRichEditor,
        string $layout,
        ?string $placeholder = null,
        ?CommentAttachmentContext $context = null,
        ?bool $compactProfile = null,
    ): RichEditor|Textarea {
        if ($useRichEditor) {
            $field = CommentRichEditor::make('body')
                ->hiddenLabel()
                ->toolbarButtons(self::toolbarButtons(context: $context));

            if ($placeholder !== null) {
                $field->placeholder($placeholder);
            }

            $field = CommentUi::configureRichEditor($field, compactProfile: $compactProfile);

            return CommentAttachments::configureRichEditor(
                editor: $field,
                context: $context ?? new CommentAttachmentContext(composer: 'root'),
            );
        }

        $rows = match (true) {
            $compactProfile === true => 2,
            $layout === 'compact' => 3,
            default => 4,
        };

        $field = Textarea::make('body')
            ->hiddenLabel()
            ->rows($rows);

        if ($placeholder !== null) {
            $field->placeholder($placeholder);
        }

        return $field;
    }

    public static function createPagePlaceholder(?CommentAttachmentContext $context = null): RichEditor
    {
        $context ??= new CommentAttachmentContext(composer: 'create');

        $field = RichEditor::make('single_comment')
            ->hiddenLabel()
            ->placeholder(__('Comments'))
            ->toolbarButtons(self::toolbarButtons(context: $context))
            ->visible(fn (string $operation): bool => $operation === 'create')
            ->dehydrated(false)
            ->columnSpanFull();

        $field = CommentUi::configureRichEditor($field);

        return CommentAttachments::configureRichEditor(editor: $field, context: $context);
    }
}
