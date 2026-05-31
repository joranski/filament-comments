<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Joranski\FilamentComments\Support\CommentModels;

trait HasComments
{
    /**
     * @return MorphMany<Model, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(CommentModels::commentClass(), 'commentable');
    }
}
