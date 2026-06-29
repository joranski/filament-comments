<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Contracts;

use Joranski\FilamentComments\Support\CommentLifecycleEvent;

interface CommentSpreadHandler
{
    public function supports(CommentLifecycleEvent $event): bool;

    /**
     * Extract structured data from a saved comment and propagate it to the host app
     * (e.g. sync tracking numbers onto an order, enqueue webhooks).
     */
    public function spread(CommentLifecycleEvent $event): void;
}
