<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

final class CommentLifecycleResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public bool $proceed = true,
        public bool $defer = false,
        public ?string $deferKey = null,
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function proceed(array $metadata = []): self
    {
        return new self(proceed: true, defer: false, metadata: $metadata);
    }

    public static function abort(): self
    {
        return new self(proceed: false, defer: false);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function defer(string $deferKey, array $metadata = []): self
    {
        return new self(
            proceed: false,
            defer: true,
            deferKey: $deferKey,
            metadata: $metadata,
        );
    }
}
