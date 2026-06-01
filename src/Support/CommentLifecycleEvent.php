<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Database\Eloquent\Model;

final class CommentLifecycleEvent
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?Model $commentable,
        public string $body,
        public CommentAttachmentContext $context,
        public ?Model $comment = null,
        public ?int $parentId = null,
        public array $metadata = [],
    ) {}
}
