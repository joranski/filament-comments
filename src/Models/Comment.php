<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Models;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Joranski\FilamentComments\Database\Factories\CommentFactory;
use Joranski\FilamentComments\Support\CommentGroups;
use Joranski\FilamentComments\Support\CommentModels;

/**
 * @property int $id
 * @property int|null $old_va_id
 * @property int|null $user_id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property string|null $title
 * @property string|null $comment
 * @property string|null $group
 * @property string|null $topic
 * @property string|null $state
 * @property bool $active
 * @property int|null $rating
 * @property int|null $likes
 * @property int|null $dislikes
 * @property int|null $parent_id
 * @property bool $is_pinned
 * @property Carbon|null $edited_at
 * @property list<int>|null $mentioned_user_ids
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|null $user
 * @property-read Model $commentable
 * @property-read self|null $parent
 * @property-read Collection<int, self> $replies
 */
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    public const GROUP_DELAY = CommentGroups::DELAY;

    public const GROUP_CHAT = CommentGroups::CHAT;

    public const STATE_PROMOTED = CommentGroups::STATE_PROMOTED;

    protected $fillable = [
        'old_va_id',
        'user_id',
        'commentable_type',
        'commentable_id',
        'active',
        'title',
        'comment',
        'group',
        'topic',
        'state',
        'rating',
        'likes',
        'dislikes',
        'parent_id',
        'is_pinned',
        'edited_at',
        'mentioned_user_ids',
    ];

    protected $casts = [
        'active' => 'boolean',
        'rating' => 'integer',
        'likes' => 'integer',
        'dislikes' => 'integer',
        'is_pinned' => 'boolean',
        'edited_at' => 'datetime',
        'mentioned_user_ids' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (self $comment): void {
            $comment->syncCommentableRatingsIfNeeded(requireActive: true);
        });

        static::updated(function (self $comment): void {
            if ($comment->rating === null) {
                return;
            }

            if ($comment->wasChanged(['rating', 'active']) || $comment->active || $comment->getOriginal('active')) {
                $comment->syncCommentableRatingsIfNeeded();
            }
        });

        static::deleted(function (self $comment): void {
            if ($comment->rating === null || ! $comment->commentable_type || ! $comment->commentable_id) {
                return;
            }

            $commentableClass = $comment->commentable_type;
            $commentable = $commentableClass::find($comment->commentable_id);

            if ($commentable && method_exists($commentable, 'recalculateRatings')) {
                $commentable->recalculateRatings();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(CommentModels::userClass(), 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->user();
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function hasReplies(): bool
    {
        if ($this->relationLoaded('replies')) {
            return $this->replies->isNotEmpty();
        }

        return $this->replies()->exists();
    }

    public function repeaterItemLabel(int $maxLines = 2, int $lineLimit = 225): Htmlable
    {
        $comment = preg_replace(['/>/', '/[^^]<(p|div|hr|br)\b/'], ['> ', "\n\\0"], (string) $this->comment);

        $commentClip = str($comment)
            ->split('/\\n/')
            ->map(fn (string $line): string => (string) str(trim(strip_tags($line)))->limit($lineLimit, ' ...'))
            ->reject(fn (string $line, int $index): bool => $index >= $maxLines)
            ->implode('<br>');

        $label = '<table><tr>'
            .'<th style="min-width: 300px;">'.$this->created_at?->diffForHumans().'<br>'.($this->user?->name ?? '').'</th>'
            .'<td>'.$commentClip.'</td>'
            .'</tr></table>';

        return new HtmlString($label);
    }

    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    public function scopeTopic(Builder $query, string $topic): Builder
    {
        return $query->where('topic', $topic);
    }

    public function scopeExcludingGroup(Builder $query, string $group): Builder
    {
        return $query->where(function (Builder $scopedQuery) use ($group): void {
            $scopedQuery
                ->whereNull('group')
                ->orWhere('group', '!=', $group);
        });
    }

    /**
     * @param  list<string>  $groups
     */
    public function scopeExcludingGroups(Builder $query, array $groups): Builder
    {
        if ($groups === []) {
            return $query;
        }

        return $query->where(function (Builder $scopedQuery) use ($groups): void {
            $scopedQuery
                ->whereNull('group')
                ->orWhereNotIn('group', $groups);
        });
    }

    public function scopeActiveChat(Builder $query): Builder
    {
        return $query
            ->where('group', self::GROUP_CHAT)
            ->where(function (Builder $scopedQuery): void {
                $scopedQuery
                    ->whereNull('state')
                    ->orWhere('state', '!=', self::STATE_PROMOTED);
            });
    }

    public function isChatMessage(): bool
    {
        return $this->group === self::GROUP_CHAT;
    }

    public function isPromoted(): bool
    {
        return $this->state === self::STATE_PROMOTED;
    }

    public function scopeState(Builder $query, string $state): Builder
    {
        return $query->where('state', $state);
    }

    public function scopeWithRating(Builder $query): Builder
    {
        return $query->whereNotNull('rating');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopePinnedFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_pinned')
            ->latest();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('state', 'approved');
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    protected function syncCommentableRatingsIfNeeded(bool $requireActive = false): void
    {
        if ($this->rating === null) {
            return;
        }

        if ($requireActive && ! $this->active) {
            return;
        }

        if (method_exists($this->commentable, 'recalculateRatings')) {
            $this->commentable?->recalculateRatings();
        }
    }
}
