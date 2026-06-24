<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Services;

use Illuminate\Support\Facades\Cache;
use Joranski\FilamentComments\Models\FilamentCommentsSetting;

/**
 * Reads and persists package-level comment settings (UI density, AI toggles).
 */
class FilamentCommentsSettings
{
    private const CACHE_KEY = 'filament-comments.settings';

    public function current(): FilamentCommentsSetting
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): FilamentCommentsSetting {
            $existing = FilamentCommentsSetting::query()->first();

            if ($existing !== null) {
                return $existing;
            }

            return FilamentCommentsSetting::query()->create([
                'compact_toolbar' => (bool) config('filament-comments.ui.compact_toolbar', false),
                'compact_action_icons' => (bool) config('filament-comments.ui.compact_action_icons', false),
                'ai_proofread_enabled' => (bool) config('filament-comments.ai.proofread_enabled', false),
                'ai_proofread_default' => (bool) config('filament-comments.ai.proofread_default', true),
                'ai_summarize_threads' => (bool) config('filament-comments.ai.summarize_threads', false),
            ]);
        });
    }

    public function compactToolbar(): bool
    {
        return $this->current()->compact_toolbar;
    }

    public function compactActionIcons(): bool
    {
        return $this->current()->compact_action_icons;
    }

    public function aiProofreadEnabled(): bool
    {
        if (! (bool) config('filament-comments.ai.enabled', false)) {
            return false;
        }

        return $this->current()->ai_proofread_enabled;
    }

    public function aiProofreadDefault(): bool
    {
        return $this->current()->ai_proofread_default;
    }

    public function aiSummarizeThreads(): bool
    {
        if (! (bool) config('filament-comments.ai.enabled', false)) {
            return false;
        }

        return $this->current()->ai_summarize_threads;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): FilamentCommentsSetting
    {
        $settings = $this->current();

        $settings->fill([
            'compact_toolbar' => (bool) ($data['compact_toolbar'] ?? false),
            'compact_action_icons' => (bool) ($data['compact_action_icons'] ?? false),
            'ai_proofread_enabled' => (bool) ($data['ai_proofread_enabled'] ?? false),
            'ai_proofread_default' => (bool) ($data['ai_proofread_default'] ?? true),
            'ai_summarize_threads' => (bool) ($data['ai_summarize_threads'] ?? false),
        ])->save();

        Cache::forget(self::CACHE_KEY);

        return $settings->refresh();
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
