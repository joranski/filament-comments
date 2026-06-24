<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Contracts;

/**
 * Optional AI assistant for comment compose (proofread, thread summary).
 *
 * Bind a custom implementation or reuse your host app's LLM bridge.
 */
interface CommentAiAssistant
{
    /**
     * Polish HTML/plain comment body before save.
     */
    public function proofread(string $bodyHtml): string;

    /**
     * Summarize a comment thread for staff (future UI).
     *
     * @param  list<string>  $commentBodies  Plain-text or HTML snippets, oldest first
     */
    public function summarizeThread(array $commentBodies): string;
}
