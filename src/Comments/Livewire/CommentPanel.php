<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Comments\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Joranski\FilamentComments\Concerns\InteractsWithCommentMentionAutocomplete;
use Joranski\FilamentComments\Support\CommentAttachmentContext;
use Joranski\FilamentComments\Support\CommentAttachments;
use Joranski\FilamentComments\Support\CommentAuthor;
use Joranski\FilamentComments\Support\CommentAuthorization;
use Joranski\FilamentComments\Support\CommentComposerField;
use Joranski\FilamentComments\Support\CommentContentRenderer;
use Joranski\FilamentComments\Support\CommentMentionNotifier;
use Joranski\FilamentComments\Support\CommentMentionParser;
use Joranski\FilamentComments\Support\CommentMentionProvider;
use Joranski\FilamentComments\Support\CommentReplyNotifier;
use Joranski\FilamentComments\Support\CommentThreadDepth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CommentPanel extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithCommentMentionAutocomplete;
    use InteractsWithForms;

    public ?Model $record = null;

    public string $layout = 'full';

    public ?string $group = null;

    public ?string $topic = null;

    public ?string $excludeGroup = null;

    /** @var list<string>|null */
    public ?array $excludedGroups = null;

    public string $heading = 'Comments';

    public string $placeholder = 'Comments';

    public string $addButtonLabel = 'Add comment';

    public bool $showHeading = true;

    public bool $allowReplies = true;

    public bool $allowPins = true;

    public bool $allowMentions = true;

    public bool $allowSearch = true;

    public bool $allowEdit = true;

    public string $search = '';

    public ?int $threadMaxHeight = null;

    public ?int $replyingToCommentId = null;

    public ?int $editingCommentId = null;

    /** @var array<string, mixed> */
    public array $commentFormData = [
        'body' => null,
    ];

    /** @var array<string, mixed> */
    public array $replyFormData = [
        'body' => null,
    ];

    /** @var array<string, mixed> */
    public array $editFormData = [
        'body' => null,
    ];

    public function mount(
        ?Model $record = null,
        string $layout = 'full',
        ?string $group = null,
        ?string $topic = null,
        ?string $excludeGroup = null,
        ?array $excludedGroups = null,
        string $heading = 'Comments',
        string $placeholder = 'Comments',
        string $addButtonLabel = 'Add comment',
        bool $showHeading = true,
        ?bool $allowReplies = null,
        ?bool $allowPins = null,
        ?bool $allowMentions = null,
        ?bool $allowSearch = null,
        ?bool $allowEdit = null,
        ?int $threadMaxHeight = null,
    ): void {
        $this->record = $record;
        $this->layout = $layout;
        $this->group = $group;
        $this->topic = $topic;
        $this->excludeGroup = $excludeGroup;
        $this->excludedGroups = $this->resolveExcludedGroups($excludedGroups);
        $this->heading = $heading;
        $this->placeholder = $placeholder;
        $this->addButtonLabel = $addButtonLabel;
        $this->showHeading = $showHeading;
        $this->allowReplies = $allowReplies ?? (bool) config('filament-comments.features.replies', true);
        $this->allowPins = $allowPins ?? (bool) config('filament-comments.features.pins', true);
        $this->allowMentions = $allowMentions ?? (bool) config('filament-comments.features.mentions', true);
        $this->allowSearch = $allowSearch ?? (bool) config('filament-comments.features.search', true);
        $this->allowEdit = $allowEdit ?? (bool) config('filament-comments.features.edit', true);
        $this->threadMaxHeight = $threadMaxHeight;

        $this->form->fill(['body' => null]);
        $this->replyForm->fill(['body' => null]);
    }

    public function isReplyingToComment(int $commentId): bool
    {
        return $this->replyingToCommentId === $commentId;
    }

    public function resolvedThreadMaxHeight(): ?int
    {
        $height = $this->threadMaxHeight ?? config('filament-comments.thread_max_height');

        if (! is_numeric($height)) {
            return null;
        }

        $height = (int) $height;

        return $height > 0 ? $height : null;
    }

    public function showCommentSearch(): bool
    {
        if (! $this->allowSearch || ! $this->record?->exists) {
            return false;
        }

        return $this->comments->isNotEmpty() || filled(trim($this->search));
    }

    public function form(Schema $schema): Schema
    {
        return $this->bodyFormSchema(
            schema: $schema,
            statePath: 'commentFormData',
            placeholder: $this->rootComposerPlaceholder(),
        );
    }

    public function replyForm(Schema $schema): Schema
    {
        return $this->bodyFormSchema(
            schema: $schema,
            statePath: 'replyFormData',
            placeholder: $this->replyComposerPlaceholder(),
        );
    }

    public function editForm(Schema $schema): Schema
    {
        return $this->bodyFormSchema(
            schema: $schema,
            statePath: 'editFormData',
        );
    }

    protected function bodyFormSchema(Schema $schema, string $statePath, ?string $placeholder = null): Schema
    {
        $composer = match ($statePath) {
            'replyFormData' => 'reply',
            'editFormData' => 'edit',
            default => 'root',
        };

        $context = $this->attachmentContext(composer: $composer);

        $editor = CommentComposerField::bodyField(
            useRichEditor: $this->usesRichEditor(),
            layout: $this->layout,
            placeholder: $placeholder,
            context: $context,
        );

        $editor = $this->configureMentions($editor);

        return $schema
            ->components([$editor->columnSpanFull()])
            ->statePath($statePath);
    }

    protected function attachmentContext(string $composer): CommentAttachmentContext
    {
        $comment = null;

        if ($composer === 'edit' && $this->editingCommentId !== null) {
            $comment = $this->findScopedComment($this->editingCommentId);
        }

        return new CommentAttachmentContext(
            commentable: $this->record,
            comment: $comment instanceof Model ? $comment : null,
            group: $this->group,
            topic: $this->topic,
            composer: $composer,
        );
    }

    #[Computed]
    public function comments(): Collection
    {
        if (! $this->record?->exists || ! method_exists($this->record, 'comments')) {
            return collect();
        }

        if (! CommentAuthorization::canViewAny()) {
            return collect();
        }

        $query = $this->record->comments()
            ->with($this->nestedReplyEagerLoad())
            ->roots()
            ->pinnedFirst();

        $this->applyScopeFilters($query);

        $comments = CommentAuthorization::filterVisible($query->get());

        if (! filled(trim($this->search))) {
            return $comments;
        }

        $needle = mb_strtolower(trim($this->search));

        return $comments
            ->filter(fn (Model $comment): bool => $this->commentMatchesSearch($comment, $needle))
            ->values();
    }

    public function addComment(): void
    {
        $comment = $this->createComment(
            rawBody: $this->form->getState()['body'] ?? null,
            parentId: null,
        );

        if ($comment === null) {
            return;
        }

        $this->resetRootComposer();

        Notification::make()
            ->title(__('Comment added.'))
            ->success()
            ->send();
    }

    public function submitReply(): void
    {
        if (! $this->allowReplies || $this->replyingToCommentId === null) {
            return;
        }

        $comment = $this->createComment(
            rawBody: $this->replyForm->getState()['body'] ?? null,
            parentId: $this->replyingToCommentId,
        );

        if ($comment === null) {
            return;
        }

        $this->cancelReply();

        Notification::make()
            ->title(__('Reply added.'))
            ->success()
            ->send();
    }

    public function startReply(int $commentId): void
    {
        if (! $this->allowReplies) {
            return;
        }

        $comment = $this->findScopedComment($commentId);

        if (! $comment instanceof Model || ! CommentAuthor::canReply($comment)) {
            return;
        }

        $this->cancelEdit();
        $this->replyingToCommentId = $comment->id;
        $this->replyForm->fill(['body' => null]);
    }

    public function cancelReply(): void
    {
        $this->replyingToCommentId = null;
        $this->replyForm->fill(['body' => null]);
    }

    public function startEdit(int $commentId): void
    {
        if (! $this->allowEdit) {
            return;
        }

        $comment = $this->findScopedComment($commentId);

        if (! $comment instanceof Model || ! CommentAuthor::canEdit($comment)) {
            return;
        }

        $this->cancelReply();
        $this->editingCommentId = $comment->id;
        $this->editForm->fill(['body' => $comment->comment]);
    }

    public function cancelEdit(): void
    {
        $this->editingCommentId = null;
        $this->editForm->fill(['body' => null]);
    }

    public function saveEdit(): void
    {
        if (! $this->allowEdit || $this->editingCommentId === null) {
            return;
        }

        $comment = $this->findScopedComment($this->editingCommentId);

        if (! $comment instanceof Model || ! CommentAuthor::canEdit($comment)) {
            return;
        }

        $body = $this->normalizeBody($this->editForm->getState()['body'] ?? null);

        if (! $this->isValidBody($body)) {
            $this->notifyInvalidBody();

            return;
        }

        $comment->update(
            $this->commentAttributes(body: $body, parentId: $comment->parent_id, isEdit: true),
        );

        $this->afterCommentSaved(
            comment: $comment->fresh(),
            context: $this->attachmentContext(composer: 'edit'),
        );
        $this->cancelEdit();
        unset($this->comments);

        Notification::make()
            ->title(__('Comment updated.'))
            ->success()
            ->send();
    }

    public function togglePin(int $commentId): void
    {
        if (! $this->allowPins) {
            return;
        }

        $comment = $this->findScopedComment($commentId);

        if (! $comment instanceof Model || ! CommentAuthor::canPin($comment)) {
            return;
        }

        $comment->update([
            'is_pinned' => ! $comment->is_pinned,
        ]);

        unset($this->comments);

        Notification::make()
            ->title($comment->is_pinned ? __('Comment pinned.') : __('Comment unpinned.'))
            ->success()
            ->send();
    }

    public function deleteComment(int $commentId): void
    {
        if (! $this->record?->exists || ! method_exists($this->record, 'comments')) {
            return;
        }

        $comment = $this->findScopedComment($commentId);

        if (! $comment instanceof Model || ! CommentAuthor::canDelete($comment)) {
            if ($comment instanceof Model && $comment->hasReplies()) {
                Notification::make()
                    ->title(__('Delete replies before removing this comment.'))
                    ->warning()
                    ->send();
            }

            return;
        }

        CommentAttachments::beforeCommentDeleted(comment: $comment);

        $comment->delete();
        unset($this->comments);

        if ($this->editingCommentId === $commentId) {
            $this->cancelEdit();
        }

        if ($this->replyingToCommentId === $commentId) {
            $this->cancelReply();
        }

        Notification::make()
            ->title(__('Comment deleted.'))
            ->success()
            ->send();
    }

    public function renderCommentBody(Model $comment): string
    {
        return (string) app(CommentContentRenderer::class)->render(
            html: (string) ($comment->comment ?? ''),
            mentionedUserIds: $comment->mentioned_user_ids,
        );
    }

    public function usesRichEditor(): bool
    {
        return $this->layout === 'full';
    }

    public function usesTextareaMentionAutocomplete(): bool
    {
        return $this->allowMentions && ! $this->usesRichEditor();
    }

    protected function configureMentions(RichEditor|Textarea $editor): RichEditor|Textarea
    {
        if (! $this->allowMentions || ! $editor instanceof RichEditor) {
            return $editor;
        }

        return $editor->mentions([
            CommentMentionProvider::make(),
        ]);
    }

    public function render(): View
    {
        return view('filament-comments::comment-panel');
    }

    protected function applyScopeFilters(Builder|Relation $query): void
    {
        if ($this->group !== null) {
            $query->where('group', $this->group);
        }

        if ($this->topic !== null) {
            $query->where('topic', $this->topic);
        }

        $excludedGroups = $this->resolvedExcludedGroups();

        if ($excludedGroups !== []) {
            $query->excludingGroups($excludedGroups);
        } elseif ($this->excludeGroup !== null) {
            $query->excludingGroup($this->excludeGroup);
        }
    }

    /**
     * @return list<string>
     */
    protected function resolvedExcludedGroups(): array
    {
        return $this->excludedGroups ?? $this->resolveExcludedGroups();
    }

    /**
     * @return list<string>
     */
    protected function resolveExcludedGroups(?array $excludedGroups = null): array
    {
        $groups = $excludedGroups ?? (array) config('filament-comments.excluded_groups', ['delay', 'chat']);

        if ($this->excludeGroup !== null && ! in_array($this->excludeGroup, $groups, true)) {
            $groups[] = $this->excludeGroup;
        }

        return array_values($groups);
    }

    protected function normalizeBody(array|string|null $body): string
    {
        if (is_array($body)) {
            $renderer = RichContentRenderer::make($body);

            $renderer = CommentAttachments::configureRichContentRenderer(
                renderer: $renderer,
                context: $this->attachmentContext(composer: 'root'),
            );

            return trim($renderer->toHtml());
        }

        return trim((string) $body);
    }

    protected function isValidBody(string $body): bool
    {
        return filled(strip_tags($body)) && strlen(trim(strip_tags($body))) >= 2;
    }

    protected function rootComposerPlaceholder(): string
    {
        if ($this->allowMentions) {
            return __('Comments — use @Name to mention someone');
        }

        return $this->placeholder;
    }

    protected function replyComposerPlaceholder(): string
    {
        if ($this->allowMentions) {
            return __('Write a reply — use @Name to mention someone');
        }

        return __('Write a reply…');
    }

    /**
     * @return array<string, mixed>
     */
    protected function commentAttributes(string $body, ?int $parentId, bool $isEdit = false): array
    {
        $attributes = [
            'comment' => $body,
        ];

        if (! $isEdit) {
            $attributes['user_id'] = auth()->id();
            $attributes['active'] = true;
            $attributes['group'] = $this->group;
            $attributes['topic'] = $this->topic;
            $attributes['parent_id'] = $parentId;
        }

        if ($this->allowMentions) {
            $attributes['mentioned_user_ids'] = app(CommentMentionParser::class)->parseUserIds($body);
        }

        if ($isEdit) {
            $attributes['edited_at'] = now();
        }

        return $attributes;
    }

    protected function afterCommentSaved(Model $comment, ?CommentAttachmentContext $context = null): void
    {
        unset($this->comments);

        $context ??= $this->attachmentContext(composer: 'root');

        CommentAttachments::afterCommentSaved(comment: $comment, context: $context);

        $author = auth()->user();

        if (! $author) {
            return;
        }

        if ($comment->parent_id && $this->record instanceof Model) {
            $parent = $this->record->comments()->find($comment->parent_id);

            if ($parent instanceof Model) {
                app(CommentReplyNotifier::class)->notify(
                    reply: $comment,
                    parent: $parent,
                    commentable: $this->record,
                    author: $author,
                );
            }
        }

        if ($this->allowMentions) {
            app(CommentMentionNotifier::class)->notify(
                comment: $comment,
                commentable: $this->record,
                author: $author,
            );
        }
    }

    protected function createComment(array|string|null $rawBody, ?int $parentId): ?Model
    {
        if (! CommentAuthorization::canCreate()) {
            return null;
        }

        if (! $this->recordIsPersisted()) {
            $this->notifySaveRecordFirst();

            return null;
        }

        if ($parentId !== null) {
            $parent = $this->findScopedComment($parentId);

            if (! $parent instanceof Model || ! CommentAuthor::canReply($parent)) {
                Notification::make()
                    ->title(__('Unable to reply to that comment.'))
                    ->warning()
                    ->send();

                return null;
            }
        }

        $body = $this->normalizeBody($rawBody);

        if (! $this->isValidBody($body)) {
            $this->notifyInvalidBody();

            return null;
        }

        $comment = $this->record->comments()->create(
            $this->commentAttributes(body: $body, parentId: $parentId),
        );

        $composer = $parentId === null ? 'root' : 'reply';

        $this->afterCommentSaved(
            comment: $comment,
            context: $this->attachmentContext(composer: $composer),
        );

        return $comment;
    }

    protected function recordIsPersisted(): bool
    {
        return $this->record?->exists === true
            && method_exists($this->record, 'comments');
    }

    protected function notifySaveRecordFirst(): void
    {
        Notification::make()
            ->title(__('Save this record before adding comments.'))
            ->warning()
            ->send();
    }

    protected function notifyInvalidBody(): void
    {
        Notification::make()
            ->title(__('Comment must be at least 2 characters.'))
            ->warning()
            ->send();
    }

    protected function resetRootComposer(): void
    {
        $this->form->fill(['body' => null]);
    }

    protected function findScopedComment(int $commentId): ?Model
    {
        if (! $this->record?->exists || ! method_exists($this->record, 'comments')) {
            return null;
        }

        $query = $this->record->comments()->with($this->nestedReplyEagerLoad());

        $this->applyScopeFilters($query);

        $comment = $query->find($commentId);

        if (! $comment instanceof Model || ! CommentAuthorization::canView($comment)) {
            return null;
        }

        return $comment;
    }

    protected function commentMatchesSearch(Model $comment, string $needle): bool
    {
        if ($this->textContains($comment->comment, $needle)) {
            return true;
        }

        if ($this->textContains($comment->user?->name, $needle)) {
            return true;
        }

        return $comment->replies->contains(
            fn (Model $reply): bool => $this->commentMatchesSearch($reply, $needle),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function nestedReplyEagerLoad(?int $remainingDepth = null): array
    {
        $remainingDepth ??= CommentThreadDepth::maxReplyDepth();

        $with = ['user'];

        if ($remainingDepth > 0) {
            $with['replies'] = function (Builder|Relation $query) use ($remainingDepth): void {
                $this->applyScopeFilters($query);
                $query->oldest()->with($this->nestedReplyEagerLoad($remainingDepth - 1));
            };
        }

        return $with;
    }

    protected function textContains(?string $value, string $needle): bool
    {
        if (! filled($value)) {
            return false;
        }

        return str_contains(mb_strtolower(strip_tags($value)), $needle);
    }
}
