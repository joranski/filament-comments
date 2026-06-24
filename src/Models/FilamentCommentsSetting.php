<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton-style package settings row (Package Settings page or embed).
 */
class FilamentCommentsSetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'compact_toolbar' => 'boolean',
        'compact_action_icons' => 'boolean',
        'ai_proofread_enabled' => 'boolean',
        'ai_proofread_default' => 'boolean',
        'ai_summarize_threads' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('filament-comments.tables.settings', 'filament_comments_settings');
    }
}
