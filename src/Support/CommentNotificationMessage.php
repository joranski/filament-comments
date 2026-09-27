<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final readonly class CommentNotificationMessage
{
    public const string KIND_MENTION = 'mention';

    public const string KIND_REPLY = 'reply';

    /**
     * @param  string  $body  Inline Markdown (e.g. `**Author** mentioned you: …`).
     */
    public function __construct(
        public string $kind,
        public string $title,
        public string $body,
        public ?string $url,
        public Model $comment,
        public Model $commentable,
        public Authenticatable $author,
    ) {}

    public function bodyHtml(): string
    {
        return (string) Str::inlineMarkdown($this->body, ['html_input' => 'escape']);
    }
}
