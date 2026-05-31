<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\TestCommentable>
 */
class TestCommentableFactory extends Factory
{
    protected $model = \TestCommentable::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'rating_tot' => 0,
            'rating_avg' => 0,
        ];
    }
}
