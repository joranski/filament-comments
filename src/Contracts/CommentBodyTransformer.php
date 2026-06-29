<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Contracts;

interface CommentBodyTransformer
{
    public function transform(string $html): string;
}
