<?php

declare(strict_types=1);

use Joranski\FilamentComments\Ai\NullCommentAiAssistant;
use Joranski\FilamentComments\Contracts\CommentAiAssistant;
use Joranski\FilamentComments\Services\CommentAiProcessor;
use Joranski\FilamentComments\Services\FilamentCommentsSettings;

test('comment ai processor skips proofread when disabled', function (): void {
    config(['filament-comments.ai.enabled' => false]);

    app(FilamentCommentsSettings::class)->update([
        'compact_toolbar' => false,
        'compact_action_icons' => false,
        'ai_proofread_enabled' => true,
        'ai_proofread_default' => true,
        'ai_summarize_threads' => false,
    ]);

    $processor = app(CommentAiProcessor::class);

    expect($processor->isAvailable())->toBeFalse()
        ->and($processor->applyProofreadIfRequested('<p>Hi</p>', true))->toBe('<p>Hi</p>');
});

test('comment ai processor proofreads when enabled and requested', function (): void {
    config(['filament-comments.ai.enabled' => true]);

    app(FilamentCommentsSettings::class)->update([
        'compact_toolbar' => false,
        'compact_action_icons' => false,
        'ai_proofread_enabled' => true,
        'ai_proofread_default' => true,
        'ai_summarize_threads' => false,
    ]);

    app()->bind(CommentAiAssistant::class, fn (): CommentAiAssistant => new class implements CommentAiAssistant
    {
        public function proofread(string $bodyHtml): string
        {
            return '<p>Polished</p>';
        }

        public function summarizeThread(array $commentBodies): string
        {
            return 'Summary';
        }
    });

    $processor = app(CommentAiProcessor::class);

    expect($processor->isAvailable())->toBeTrue()
        ->and($processor->applyProofreadIfRequested('<p>Hi</p>', true))->toBe('<p>Polished</p>')
        ->and($processor->applyProofreadIfRequested('<p>Hi</p>', false))->toBe('<p>Hi</p>');
});

test('comment ai processor summarize respects settings flag', function (): void {
    config(['filament-comments.ai.enabled' => true]);

    app(FilamentCommentsSettings::class)->update([
        'compact_toolbar' => false,
        'compact_action_icons' => false,
        'ai_proofread_enabled' => true,
        'ai_proofread_default' => true,
        'ai_summarize_threads' => true,
    ]);

    app()->bind(CommentAiAssistant::class, fn (): CommentAiAssistant => new class implements CommentAiAssistant
    {
        public function proofread(string $bodyHtml): string
        {
            return $bodyHtml;
        }

        public function summarizeThread(array $commentBodies): string
        {
            return 'Three updates from staff.';
        }
    });

    $processor = app(CommentAiProcessor::class);

    expect($processor->summarizeThreadIfEnabled(['First', 'Second']))->toBe('Three updates from staff.');
});

test('null comment ai assistant is bound by default', function (): void {
    expect(app(CommentAiAssistant::class))->toBeInstanceOf(NullCommentAiAssistant::class);
});
