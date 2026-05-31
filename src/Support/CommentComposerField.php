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
    public static function toolbarButtons(): array
    {
        return [
            ['bold', 'italic', 'underline', 'strike'],
            ['link', 'blockquote', 'codeBlock', 'bulletList', 'orderedList'],
            ['undo', 'redo'],
        ];
    }

    public static function bodyField(
        bool $useRichEditor,
        string $layout,
        ?string $placeholder = null,
    ): RichEditor|Textarea {
        if ($useRichEditor) {
            $field = RichEditor::make('body')
                ->hiddenLabel()
                ->toolbarButtons(self::toolbarButtons());

            if ($placeholder !== null) {
                $field->placeholder($placeholder);
            }

            return $field;
        }

        $field = Textarea::make('body')
            ->hiddenLabel()
            ->rows($layout === 'compact' ? 3 : 4);

        if ($placeholder !== null) {
            $field->placeholder($placeholder);
        }

        return $field;
    }

    public static function createPagePlaceholder(): RichEditor
    {
        return RichEditor::make('single_comment')
            ->hiddenLabel()
            ->placeholder(__('Comments'))
            ->toolbarButtons(self::toolbarButtons())
            ->visible(fn (string $operation): bool => $operation === 'create')
            ->dehydrated(false)
            ->columnSpanFull();
    }
}
