<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;

final class CommentComposerField
{
    /**
     * @return list<list<string>>
     */
    public static function toolbarButtons(?CommentAttachmentContext $context = null): array
    {
        $buttons = [
            ['bold', 'italic', 'underline', 'strike'],
            ['link', 'blockquote', 'codeBlock', 'bulletList', 'orderedList'],
            ['undo', 'redo'],
        ];

        if (CommentAttachments::enabled(context: $context)) {
            $buttons[] = ['attachFiles'];
        }

        return $buttons;
    }

    public static function bodyField(
        bool $useRichEditor,
        string $layout,
        ?string $placeholder = null,
        ?CommentAttachmentContext $context = null,
    ): RichEditor|Textarea {
        if ($useRichEditor) {
            $field = RichEditor::make('body')
                ->hiddenLabel()
                ->toolbarButtons(self::toolbarButtons(context: $context));

            if ($placeholder !== null) {
                $field->placeholder($placeholder);
            }

            return CommentAttachments::configureRichEditor(
                editor: $field,
                context: $context ?? new CommentAttachmentContext(composer: 'root'),
            );
        }

        $field = Textarea::make('body')
            ->hiddenLabel()
            ->rows($layout === 'compact' ? 3 : 4);

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

        return CommentAttachments::configureRichEditor(editor: $field, context: $context);
    }
}
