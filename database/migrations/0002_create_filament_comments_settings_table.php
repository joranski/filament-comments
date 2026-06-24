<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('filament-comments.tables.settings', 'filament_comments_settings');

        Schema::create($table, function (Blueprint $table): void {
            $table->id();
            $table->boolean('compact_toolbar')->default(false);
            $table->boolean('compact_action_icons')->default(false);
            $table->boolean('ai_proofread_enabled')->default(false);
            $table->boolean('ai_proofread_default')->default(true);
            $table->boolean('ai_summarize_threads')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-comments.tables.settings', 'filament_comments_settings'));
    }
};
