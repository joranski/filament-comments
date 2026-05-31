<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Comments\Schemas;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Livewire;
use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Joranski\FilamentComments\Support\CommentComposerField;
use Joranski\FilamentComments\Support\CommentGroups;

/**
 * Embeds the shared comment panel inside Filament resource forms.
 *
 * Host applications register Shield permissions against their comment model.
 */
final class CommentPanelSchema
{
    public static function embeddedForm(
        ?array $excludedGroups = null,
        string $heading = 'Comments',
    ): Component {
        return Group::make()
            ->schema([
                Livewire::make(
                    component: CommentPanel::class,
                    data: fn (): array => [
                        'layout' => 'full',
                        'excludedGroups' => $excludedGroups ?? (array) config('filament-comments.excluded_groups', [
                            CommentGroups::DELAY,
                            CommentGroups::CHAT,
                        ]),
                        'heading' => $heading,
                        'showHeading' => false,
                    ],
                )->columnSpanFull(),

                CommentComposerField::createPagePlaceholder(),
            ])
            ->columnSpanFull();
    }

    public static function widgetConfiguration(
        ?string $group = null,
        ?string $topic = null,
        ?array $excludedGroups = null,
        string $layout = 'full',
        string $heading = 'Comments',
        ?int $threadMaxHeight = null,
    ): array {
        return [
            'layout' => $layout,
            'group' => $group,
            'topic' => $topic,
            'excludedGroups' => $excludedGroups ?? (array) config('filament-comments.excluded_groups', [
                CommentGroups::DELAY,
                CommentGroups::CHAT,
            ]),
            'heading' => $heading,
            'showHeading' => true,
            'threadMaxHeight' => $threadMaxHeight,
        ];
    }
}
