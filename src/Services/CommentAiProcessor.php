<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Services;

use Joranski\FilamentComments\Contracts\CommentAiAssistant;

/**
 * Applies optional AI enhancements to comment bodies before persistence.
 */
class CommentAiProcessor
{
    public function __construct(
        private readonly FilamentCommentsSettings $settings,
    ) {}

    private function assistant(): CommentAiAssistant
    {
        return app(CommentAiAssistant::class);
    }

    public function isAvailable(): bool
    {
        return (bool) config('filament-comments.ai.enabled', false)
            && $this->settings->aiProofreadEnabled();
    }

    public function showsProofreadToggle(): bool
    {
        return $this->isAvailable();
    }

    public function defaultProofreadToggle(): bool
    {
        return $this->settings->aiProofreadDefault();
    }

    public function applyProofreadIfRequested(string $bodyHtml, bool $requested): string
    {
        if (! $this->isAvailable() || ! $requested) {
            return $bodyHtml;
        }

        $proofread = trim($this->assistant()->proofread($bodyHtml));

        return $proofread !== '' ? $proofread : $bodyHtml;
    }

    public function summarizeThreadIfEnabled(array $commentBodies): ?string
    {
        if (! $this->settings->aiSummarizeThreads()) {
            return null;
        }

        $summary = trim($this->assistant()->summarizeThread($commentBodies));

        return $summary !== '' ? $summary : null;
    }
}
