<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Comments\Livewire;

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
use Joranski\FilamentComments\Support\CommentAuthor;
use Joranski\FilamentComments\Support\CommentContentRenderer;
use Joranski\FilamentComments\Support\CommentMentionNotifier;
use Joranski\FilamentComments\Support\CommentMentionParser;
use Joranski\FilamentComments\Support\CommentModels;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CommentPanel extends Component implements HasForms
{
    use InteractsWithForms;

    public ?Model $record = null;

    public string $layout = 'full';

    public ?string $group = null;

    public ?string $topic = null;

    public ?string $excludeGroup = null;

    /** @var list<string> */
    public array $excludedGroups = [];

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

    public ?int $replyingToCommentId = null;

    public ?int $editingCommentId = null;

    /** @var array<string, mixed> */
    public array $commentFormData = [
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
    ): void {
        $this->record = $record;
        $this->layout = $layout;
        $this->group = $group;
        $this->topic = $topic;
        $this->excludeGroup = $excludeGroup;
        $this->excludedGroups = $excludedGroups ?? (array) config('filament-comments.excluded_groups', ['delay', 'chat']);
        if ($excludeGroup !== null && ! in_array($excludeGroup, $this->excludedGroups, true)) {
            $this->excludedGroups[] = $excludeGroup;
        }
        $this->heading = $heading;
        $this->placeholder = $placeholder;
        $this->addButtonLabel = $addButtonLabel;
        $this->showHeading = $showHeading;
        $this->allowReplies = $allowReplies ?? (bool) config('filament-comments.features.replies', true);
        $this->allowPins = $allowPins ?? (bool) config('filament-comments.features.pins', true);
        $this->allowMentions = $allowMentions ?? (bool) config('filament-comments.features.mentions', true);
        $this->allowSearch = $allowSearch ?? (bool) config('filament-comments.features.search', true);
        $this->allowEdit = $allowEdit ?? (bool) config('filament-comments.features.edit', true);

        $this->form->fill(['body' => null]);
    }

    public function form(Schema $schema): Schema
    {
        $editor = $this->usesRichEditor()
            ? RichEditor::make('body')
                ->hiddenLabel()
                ->placeholder($this->composerPlaceholder())
                ->toolbarButtons([
                    ['bold', 'italic', 'underline', 'strike'],
                    ['link', 'blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                    ['undo', 'redo'],
                ])
            : Textarea::make('body')
                ->hiddenLabel()
                ->placeholder($this->composerPlaceholder())
                ->rows($this->layout === 'compact' ? 3 : 4);

        return $schema
            ->components([$editor->columnSpanFull()])
            ->statePath('commentFormData');
    }

    public function editForm(Schema $schema): Schema
    {
        $editor = $this->usesRichEditor()
            ? RichEditor::make('body')
                ->hiddenLabel()
                ->toolbarButtons([
                    ['bold', 'italic', 'underline', 'strike'],
                    ['link', 'blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                    ['undo', 'redo'],
                ])
            : Textarea::make('body')
                ->hiddenLabel()
                ->rows($this->layout === 'compact' ? 3 : 4);

        return $schema
            ->components([$editor->columnSpanFull()])
            ->statePath('editFormData');
    }

    #[Computed]
    public function comments(): Collection
    {
        if (! $this->record?->exists || ! method_exists($this->record, 'comments')) {
            return collect();
        }

        $query = $this->record->comments()
            ->with(['user', 'replies.user'])
            ->roots()
            ->pinnedFirst();

        $this->applyScopeFilters($query);

        $comments = $query->get();

        if (! filled(trim($this->search))) {
            return $comments;
        }

        $needle = mb_strtolower(trim($this->search));

        return $comments
            ->filter(fn (Model $comment): bool => $this->commentMatchesSearch($comment, $needle))
            ->values();
    }

    #[Computed]
    public function replyingToComment(): ?Model
    {
        if ($this->replyingToCommentId === null || ! $this->record?->exists) {
            return null;
        }

        return $this->record->comments()->with('user')->find($this->replyingToCommentId);
    }

    public function addComment(): void
    {
        if (! auth()->user()?->can('create', CommentModels::commentClass())) {
            return;
        }

        if (! $this->record?->exists || ! method_exists($this->record, 'comments')) {
            Notification::make()
                ->title(__('Save this record before adding comments.'))
                ->warning()
                ->send();

            return;
        }

        $body = $this->normalizeBody($this->form->getState()['body'] ?? null);

        if (! $this->isValidBody($body)) {
            Notification::make()
                ->title(__('Comment must be at least 2 characters.'))
                ->warning()
                ->send();

            return;
        }

        $parentId = null;

        if ($this->allowReplies && $this->replyingToCommentId !== null) {
            $parent = $this->record->comments()->roots()->find($this->replyingToCommentId);

            if (! $parent instanceof Model) {
                Notification::make()
                    ->title(__('Unable to reply to that comment.'))
                    ->warning()
                    ->send();

                return;
            }

            $parentId = $parent->id;
        }

        $comment = $this->record->comments()->create(
            $this->commentAttributes(body: $body, parentId: $parentId),
        );

        $this->afterCommentSaved($comment);
        $this->resetComposer();

        Notification::make()
            ->title($parentId !== null ? __('Reply added.') : __('Comment added.'))
            ->success()
            ->send();
    }

    public function startReply(int $commentId): void
    {
        if (! $this->allowReplies) {
            return;
        }

        $comment = $this->record?->comments()->roots()->find($commentId);

        if (! $comment instanceof Model || ! CommentAuthor::canReply($comment)) {
            return;
        }

        $this->cancelEdit();
        $this->replyingToCommentId = $comment->id;
    }

    public function cancelReply(): void
    {
        $this->replyingToCommentId = null;
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
            Notification::make()
                ->title(__('Comment must be at least 2 characters.'))
                ->warning()
                ->send();

            return;
        }

        $comment->update(
            $this->commentAttributes(body: $body, parentId: $comment->parent_id, isEdit: true),
        );

        $this->afterCommentSaved($comment->fresh());
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

        $comment = $this->record?->comments()->roots()->find($commentId);

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

        if ($this->excludedGroups !== []) {
            $query->excludingGroups($this->excludedGroups);
        } elseif ($this->excludeGroup !== null) {
            $query->excludingGroup($this->excludeGroup);
        }
    }

    protected function normalizeBody(array|string|null $body): string
    {
        if (is_array($body)) {
            return trim(RichContentRenderer::make($body)->toHtml());
        }

        return trim((string) $body);
    }

    protected function isValidBody(string $body): bool
    {
        return filled(strip_tags($body)) && strlen(trim(strip_tags($body))) >= 2;
    }

    protected function composerPlaceholder(): string
    {
        if ($this->replyingToCommentId !== null) {
            return __('Write a reply…');
        }

        if ($this->allowMentions) {
            return __('Comments — use @Name to mention someone');
        }

        return $this->placeholder;
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

    protected function afterCommentSaved(Model $comment): void
    {
        unset($this->comments);

        if (! $this->allowMentions || ! auth()->user()) {
            return;
        }

        app(CommentMentionNotifier::class)->notify(
            comment: $comment,
            commentable: $this->record,
            author: auth()->user(),
        );
    }

    protected function resetComposer(): void
    {
        $this->form->fill(['body' => null]);
        $this->replyingToCommentId = null;
    }

    protected function findScopedComment(int $commentId): ?Model
    {
        if (! $this->record?->exists || ! method_exists($this->record, 'comments')) {
            return null;
        }

        $query = $this->record->comments()->with('replies');

        $this->applyScopeFilters($query);

        return $query->find($commentId);
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
            fn (Model $reply): bool => $this->textContains($reply->comment, $needle)
                || $this->textContains($reply->user?->name, $needle),
        );
    }

    protected function textContains(?string $value, string $needle): bool
    {
        if (! filled($value)) {
            return false;
        }

        return str_contains(mb_strtolower(strip_tags($value)), $needle);
    }
}
