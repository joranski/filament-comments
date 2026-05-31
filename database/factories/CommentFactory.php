<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Database\Factories;

use Joranski\FilamentComments\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'comment' => fake()->paragraphs(2, true),
            'active' => true,
            'rating' => fake()->optional()->numberBetween(1, 5),
            'likes' => fake()->numberBetween(0, 22),
            'dislikes' => fake()->numberBetween(0, 12),
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'active' => false,
        ]);
    }

    public function reply(int $parentId): self
    {
        return $this->state(fn (): array => [
            'parent_id' => $parentId,
        ]);
    }
}
