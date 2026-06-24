<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Ai;

use Joranski\FilamentComments\Contracts\CommentAiAssistant;

/**
 * Default no-op assistant when AI is disabled or unconfigured.
 */
final class NullCommentAiAssistant implements CommentAiAssistant
{
    public function proofread(string $bodyHtml): string
    {
        return $bodyHtml;
    }

    public function summarizeThread(array $commentBodies): string
    {
        return '';
    }
}
