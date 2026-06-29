<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Support;

use Joranski\FilamentComments\Contracts\CommentBodyTransformer;

final class CommentBodyTransformers
{
    public static function transform(string $html): string
    {
        foreach (static::transformers() as $transformerClass) {
            /** @var CommentBodyTransformer $transformer */
            $transformer = app($transformerClass);
            $html = $transformer->transform($html);
        }

        return $html;
    }

    /**
     * @return list<class-string<CommentBodyTransformer>>
     */
    protected static function transformers(): array
    {
        $transformers = config('filament-comments.body_transformers', []);

        if (! is_array($transformers)) {
            return [];
        }

        return array_values(array_filter(
            $transformers,
            fn (mixed $transformer): bool => is_string($transformer)
                && $transformer !== ''
                && is_subclass_of($transformer, CommentBodyTransformer::class),
        ));
    }
}
