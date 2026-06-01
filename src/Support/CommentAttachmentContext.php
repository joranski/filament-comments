<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Database\Eloquent\Model;

final readonly class CommentAttachmentContext
{
    public function __construct(
        public ?Model $commentable = null,
        public ?Model $comment = null,
        public ?string $group = null,
        public ?string $topic = null,
        public string $composer = 'root',
    ) {}
}
