<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Filament\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Reusable Filament schema for filament-comments package settings.
 */
final class CommentsSettingsForm
{
    /**
     * @return array<int, mixed>
     */
    public static function components(): array
    {
        $aiEnabled = (bool) config('filament-comments.ai.enabled', false);
        $usesShield = class_exists(\BezhanSalleh\FilamentShield\FilamentShield::class);

        return [
            Section::make('Comment UI density')
                ->description('Reduce toolbar and thread action icon sizes on every comment panel.')
                ->schema([
                    Toggle::make('compact_toolbar')
                        ->label('Compact RichEditor toolbar')
                        ->helperText('Smaller formatting buttons on full-layout composers.')
                        ->columnSpanFull(),
                    Toggle::make('compact_action_icons')
                        ->label('Compact thread action icons')
                        ->helperText('Pin, reply, edit, and delete buttons use a smaller Flux size.')
                        ->columnSpanFull(),
                ]),
            Section::make('AI compose')
                ->description('Optional proofread before comments are saved. Requires `FILAMENT_COMMENTS_AI_ENABLED=true` and a bound `CommentAiAssistant`.')
                ->schema([
                    Placeholder::make('ai_status')
                        ->label('AI provider')
                        ->content($aiEnabled
                            ? 'Enabled via environment. Bind `CommentAiAssistant` in your service provider to connect Gemini/OpenAI/etc.'
                            : 'Disabled — set `FILAMENT_COMMENTS_AI_ENABLED=true` and bind an assistant implementation.')
                        ->columnSpanFull(),
                    Toggle::make('ai_proofread_enabled')
                        ->label('Enable AI proofread')
                        ->helperText('Shows a proofread toggle on the root comment composer when a real assistant is bound.')
                        ->disabled(! $aiEnabled)
                        ->columnSpanFull(),
                    Toggle::make('ai_proofread_default')
                        ->label('Proofread toggle default ON')
                        ->visible(fn (): bool => $aiEnabled),
                    Toggle::make('ai_summarize_threads')
                        ->label('Enable thread summarization (API)')
                        ->helperText('Reserved for a future “Summarize thread” action in the panel header.')
                        ->disabled(! $aiEnabled)
                        ->visible(fn (): bool => $aiEnabled),
                ]),
            Section::make('Filament Shield')
                ->description('Authorization is configured in `config/filament-comments.php` (`authorization.mode`).')
                ->schema([
                    Placeholder::make('shield_status')
                        ->hiddenLabel()
                        ->content($usesShield
                            ? 'Shield detected. Keep `authorization.mode` as `auto`, publish `CommentPolicy` via `php artisan vendor:publish --tag=filament-comments-policy-shield`, then run `shield:generate` for Comment permissions. Pin/unpin requires `Update:Comment`.'
                            : 'Shield not installed. Use fallback rules or publish the standalone Comment policy stub.')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
