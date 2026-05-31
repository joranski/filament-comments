<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comments schema for {@see \Joranski\FilamentComments\Models\Comment}.
 *
 * Safe for greenfield installs and existing apps: skips create when the table
 * already exists and only adds collaboration columns when missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('old_va_id')->nullable()->comment('Legacy comment id from va-service-track');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->morphs('commentable');
                $table->foreignId('parent_id')
                    ->nullable()
                    ->constrained('comments')
                    ->nullOnDelete();
                $table->boolean('active')->default(false);
                $table->string('title')->nullable();
                $table->text('comment')->nullable();
                $table->string('group')->index()->nullable();
                $table->string('topic')->index()->nullable();
                $table->string('state')->nullable();
                $table->boolean('is_pinned')->default(false);
                $table->timestamp('edited_at')->nullable();
                $table->json('mentioned_user_ids')->nullable();
                $table->tinyInteger('rating')->nullable();
                $table->integer('likes')->default(0);
                $table->integer('dislikes')->default(0);
                $table->timestamps();

                $table->index('old_va_id');
                $table->index(['commentable_type', 'commentable_id', 'is_pinned']);
                $table->index('parent_id');
            });

            return;
        }

        Schema::table('comments', function (Blueprint $table): void {
            if (! Schema::hasColumn('comments', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('commentable_id')
                    ->constrained('comments')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('comments', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('state');
            }

            if (! Schema::hasColumn('comments', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('is_pinned');
            }

            if (! Schema::hasColumn('comments', 'mentioned_user_ids')) {
                $table->json('mentioned_user_ids')->nullable()->after('edited_at');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
