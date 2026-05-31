<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Joranski\FilamentComments\Concerns\HasComments;
use Joranski\FilamentComments\Tests\Database\Factories\TestCommentableFactory;
use Joranski\FilamentComments\Tests\Database\Factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}

class ExtendedComment extends \Joranski\FilamentComments\Models\Comment
{
    protected $table = 'comments';
}

class TestCommentable extends Model
{
    /** @use HasFactory<TestCommentableFactory> */
    use HasComments;
    use HasFactory;

    protected $table = 'test_commentables';

    protected $fillable = [
        'name',
        'rating_tot',
        'rating_avg',
    ];

    protected function casts(): array
    {
        return [
            'rating_tot' => 'integer',
            'rating_avg' => 'float',
        ];
    }

    public function recalculateRatings(): void
    {
        $comments = $this->comments()
            ->where('active', true)
            ->whereNotNull('rating')
            ->get();

        $this->update([
            'rating_tot' => $comments->count(),
            'rating_avg' => $comments->avg('rating') ?? 0,
        ]);
    }

    protected static function newFactory(): TestCommentableFactory
    {
        return TestCommentableFactory::new();
    }
}
