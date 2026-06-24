<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Forms\Components;

use Filament\Forms\Components\RichEditor;

/**
 * Rich editor tuned for {@see \Joranski\FilamentComments\Comments\Livewire\CommentPanel} mentions.
 */
class CommentRichEditor extends RichEditor
{
    /**
     * @var view-string
     */
    protected string $view = 'filament-comments::components.rich-editor';
}
