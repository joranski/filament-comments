<?php

declare(strict_types=1);

namespace Joranski\FilamentComments\Comments\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Joranski\FilamentComments\Concerns\InteractsWithCommentMentionAutocomplete;
use Joranski\FilamentComments\Services\CommentAiProcessor;
use Joranski\FilamentComments\Support\CommentAttachmentContext;
use Joranski\FilamentComments\Support\CommentAttachmentHtmlTransformer;
use Joranski\FilamentComments\Support\CommentAttachments;
use Joranski\FilamentComments\Support\CommentAuthor;
use Joranski\FilamentComments\Support\CommentAuthorization;
use Joranski\FilamentComments\Support\CommentBodyAttachments;
use Joranski\FilamentComments\Support\CommentBodyValidator;
use Joranski\FilamentComments\Support\CommentComposerField;
use Joranski\FilamentComments\Support\CommentContentRenderer;
use Joranski\FilamentComments\Support\CommentLifecycle;
use Joranski\FilamentComments\Support\CommentLifecycleEvent;
use Joranski\FilamentComments\Support\CommentMentionNotifier;
use Joranski\FilamentComments\Support\CommentMentionParser;
use Joranski\FilamentComments\Support\CommentReplyNotifier;
use Joranski\FilamentComments\Support\CommentThreadDepth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Comment thread + composer for any model using {@see \Joranski\FilamentComments\Concerns\HasComments}.
 *
 * Pure Livewire + Flux: the composers are `flux:editor` (full layout) or `flux:textarea`
 * (compact layout) bound to plain array properties, attachments are Livewire uploads
 * stored through the configured {@see \Joranski\FilamentComments\Contracts\CommentAttachmentHandler}.
 * Embed with `<livewire:filament-comments.comment-panel :record="$model" />`.
 */
class CommentPanel extends Component
{
    use InteractsWithCommentMentionAutocomplete;
    use WithFileUploads;

    public const int MAX_BODY_LENGTH = 65000;

    public const int MAX_ATTACHMENTS = 10;

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

    /** @var array{body: ?string} */
    public array $commentFormData = [
        'body' => null,
    ];

    /** @var array{body: ?string} */
    public array $replyFormData = [
        'body' => null,
    ];

    /** @var array{body: ?string} */
    public array $editFormData = [
        'body' => null,
    ];

    /** @var array<int, mixed> Livewire temporary uploads queued on the root composer. */
    public array $commentAttachments = [];

    /** @var array<int, mixed> */
    public array $replyAttachments = [];

    /** @var array<int, mixed> */
    public array $editAttachments = [];

    /**
     * Attachment markup already stored on the comment being edited; kept beside the
     * editor (which has no image node) and re-appended on save.
     *
     * @var list<string>
     */
    public array $editExistingAttachments = [];

    public bool $showLifecyclePrompt = false;

    public ?string $lifecyclePromptKey = null;

    /** @var array<string, mixed>|null */
    public ?array $pendingCommentPayload = null;

    public bool $proofreadWithAi = false;

    /**
     * Per-panel condensed UI (smaller icons, text, padding). Distinct from {@see $layout} `compact` (textarea).
     */
    public bool $compactProfile = false;

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
        bool $compactProfile = false,
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
        $this->compactProfile = $compactProfile;

        $processor = app(CommentAiProcessor::class);
        $this->proofreadWithAi = $processor->showsProofreadToggle()
            && $processor->defaultProofreadToggle();
    }

    /**
     * Enable a condensed panel profile (smaller icons, typography, and spacing).
     */
    public function compact(bool $compact = true): static
    {
        $this->compactProfile = $compact;

        return $this;
    }

    /**
     * When true, per-panel condensed styling overrides global density settings.
     */
    public function usesCompactProfile(): bool
    {
        return $this->compactProfile;
    }

    public function uiCompactProfileContext(): ?bool
    {
        return $this->compactProfile ? true : null;
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

    public function showsProofreadToggle(): bool
    {
        return app(CommentAiProcessor::class)->showsProofreadToggle();
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
            rawBody: $this->commentFormData['body'] ?? null,
            parentId: null,
        );

        if ($comment === null) {
            return;
        }

        $this->resetRootComposer();

        $this->toast(heading: __('Comment added.'));
    }

    public function confirmDeferredComment(array $metadata = []): void
    {
        if ($this->pendingCommentPayload === null) {
            $this->clearLifecycleDefer();

            return;
        }

        $payload = $this->pendingCommentPayload;
        $mergedMetadata = array_merge($payload['metadata'] ?? [], $metadata);
        $action = (string) ($payload['action'] ?? '');

        $this->clearLifecycleDefer();

        match ($action) {
            'create' => $this->finalizeDeferredCreate(payload: $payload, metadata: $mergedMetadata),
            'update' => $this->finalizeDeferredUpdate(payload: $payload, metadata: $mergedMetadata),
            default => null,
        };
    }

    public function cancelDeferredComment(): void
    {
        $this->clearLifecycleDefer();
    }

    public function lifecycleDeferPromptView(): ?string
    {
        return CommentLifecycle::deferPromptView(deferKey: $this->lifecyclePromptKey);
    }

    public function submitReply(): void
    {
        if (! $this->allowReplies || $this->replyingToCommentId === null) {
            return;
        }

        $comment = $this->createComment(
            rawBody: $this->replyFormData['body'] ?? null,
            parentId: $this->replyingToCommentId,
        );

        if ($comment === null) {
            return;
        }

        $this->cancelReply();

        $this->toast(heading: __('Reply added.'));
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
        $this->replyFormData = ['body' => null];
        $this->replyAttachments = [];
        $this->resetValidation(['replyFormData.body', 'replyAttachments', 'replyAttachments.*']);
    }

    public function cancelReply(): void
    {
        $this->replyingToCommentId = null;
        $this->replyFormData = ['body' => null];
        $this->replyAttachments = [];
        $this->resetValidation(['replyFormData.body', 'replyAttachments', 'replyAttachments.*']);
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

        $parts = CommentBodyAttachments::split(html: (string) ($comment->comment ?? ''));

        $this->editingCommentId = $comment->id;
        $this->editFormData = ['body' => $parts['body']];
        $this->editExistingAttachments = $parts['attachments'];
        $this->editAttachments = [];
        $this->resetValidation(['editFormData.body', 'editAttachments', 'editAttachments.*']);
    }

    public function cancelEdit(): void
    {
        $this->editingCommentId = null;
        $this->editFormData = ['body' => null];
        $this->editExistingAttachments = [];
        $this->editAttachments = [];
        $this->resetValidation(['editFormData.body', 'editAttachments', 'editAttachments.*']);
    }

    public function removeExistingAttachment(int $index): void
    {
        unset($this->editExistingAttachments[$index]);

        $this->editExistingAttachments = array_values($this->editExistingAttachments);
    }

    public function removeUpload(string $composer, int $index): void
    {
        $property = $this->attachmentProperty(composer: $composer);
        $uploads = $this->{$property};

        unset($uploads[$index]);

        $this->{$property} = array_values($uploads);
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

        $this->validate($this->composerRules(composer: 'edit'), attributes: $this->composerValidationAttributes(composer: 'edit'));

        $body = $this->normalizeBody($this->editFormData['body'] ?? null);
        $body = $this->applyAiProofread(body: $body, composer: 'edit');
        $body = CommentBodyAttachments::append(body: $body, attachments: $this->editExistingAttachments);

        if (! $this->isValidBody($body) && ! $this->hasQueuedAttachments(composer: 'edit')) {
            $this->notifyInvalidBody(composer: 'edit');

            return;
        }

        $context = $this->attachmentContext(composer: 'edit');
        $body = $this->appendQueuedAttachments(body: $body, composer: 'edit', context: $context);

        $beforeResult = CommentLifecycle::beforeUpdate(new CommentLifecycleEvent(
            commentable: $this->record,
            body: $body,
            context: $context,
            comment: $comment,
            parentId: $comment->parent_id,
        ));

        if ($beforeResult->defer) {
            $this->pendingCommentPayload = [
                'action' => 'update',
                'commentId' => $comment->id,
                'body' => $body,
                'composer' => 'edit',
                'metadata' => $beforeResult->metadata,
            ];
            $this->lifecyclePromptKey = $beforeResult->deferKey;
            $this->showLifecyclePrompt = true;

            return;
        }

        if (! $beforeResult->proceed) {
            return;
        }

        $this->persistUpdatedComment(
            comment: $comment,
            body: $body,
            context: $context,
            metadata: $beforeResult->metadata,
        );
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

        $this->toast(heading: $comment->is_pinned ? __('Comment pinned.') : __('Comment unpinned.'));
    }

    public function deleteComment(int $commentId): void
    {
        if (! $this->record?->exists || ! method_exists($this->record, 'comments')) {
            return;
        }

        $comment = $this->findScopedComment($commentId);

        if (! $comment instanceof Model || ! CommentAuthor::canDelete($comment)) {
            if ($comment instanceof Model && $comment->hasReplies()) {
                $this->toast(heading: __('Delete replies before removing this comment.'), variant: 'warning');
            }

            return;
        }

        CommentAttachments::beforeCommentDeleted(comment: $comment);

        $deleteEvent = new CommentLifecycleEvent(
            commentable: $this->record,
            body: (string) ($comment->comment ?? ''),
            context: $this->attachmentContext(composer: 'root'),
            comment: $comment,
            parentId: $comment->parent_id,
        );

        $beforeResult = CommentLifecycle::beforeDelete($deleteEvent);

        if (! $beforeResult->proceed) {
            return;
        }

        $comment->delete();
        unset($this->comments);

        CommentLifecycle::afterDelete($deleteEvent);

        if ($this->editingCommentId === $commentId) {
            $this->cancelEdit();
        }

        if ($this->replyingToCommentId === $commentId) {
            $this->cancelReply();
        }

        $this->toast(heading: __('Comment deleted.'));
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

    public function usesEditorMentionAutocomplete(): bool
    {
        return $this->allowMentions && $this->usesRichEditor();
    }

    public function editorToolbar(): string
    {
        return CommentComposerField::fluxToolbar();
    }

    public function textareaRows(): int
    {
        return CommentComposerField::textareaRows(layout: $this->layout, compactProfile: $this->uiCompactProfileContext());
    }

    public function composerPlaceholder(string $composer): string
    {
        return match ($composer) {
            'reply' => $this->replyComposerPlaceholder(),
            'edit' => '',
            default => $this->rootComposerPlaceholder(),
        };
    }

    public function attachmentsEnabled(string $composer): bool
    {
        return CommentAttachments::enabled(context: $this->attachmentContext(composer: $composer));
    }

    /**
     * Comma-separated `accept` value for the composer file input.
     */
    public function attachmentAccept(): string
    {
        return implode(',', CommentAttachments::acceptedFileTypes());
    }

    /**
     * @return list<string>
     */
    public function existingAttachmentLabels(): array
    {
        return array_map(
            fn (string $html): string => CommentBodyAttachments::label(attachmentHtml: $html),
            $this->editExistingAttachments,
        );
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function queuedAttachments(string $composer): array
    {
        return array_filter(
            $this->{$this->attachmentProperty(composer: $composer)},
            fn (mixed $file): bool => $file instanceof UploadedFile,
        );
    }

    public function render(): View
    {
        return view('filament-comments::comment-panel');
    }

    /**
     * User feedback after an action. Dispatches a Flux toast (`<flux:toast />` must be on
     * the page); override to route feedback elsewhere.
     */
    protected function toast(string $heading, string $variant = 'success'): void
    {
        $this->dispatch('toast-show', duration: 5000, slots: ['heading' => $heading], dataset: ['variant' => $variant]);
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

    protected function formProperty(string $composer): string
    {
        return match ($composer) {
            'reply' => 'replyFormData',
            'edit' => 'editFormData',
            default => 'commentFormData',
        };
    }

    protected function attachmentProperty(string $composer): string
    {
        return match ($composer) {
            'reply' => 'replyAttachments',
            'edit' => 'editAttachments',
            default => 'commentAttachments',
        };
    }

    /**
     * @return array<string, list<string>>
     */
    protected function composerRules(string $composer): array
    {
        $attachments = $this->attachmentProperty(composer: $composer);

        return [
            $this->formProperty(composer: $composer).'.body' => ['nullable', 'string', 'max:'.static::MAX_BODY_LENGTH],
            $attachments => ['array', 'max:'.static::MAX_ATTACHMENTS],
            $attachments.'.*' => CommentAttachments::fileRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function composerValidationAttributes(string $composer): array
    {
        $attachments = $this->attachmentProperty(composer: $composer);

        return [
            $this->formProperty(composer: $composer).'.body' => __('comment'),
            $attachments => __('attachments'),
            $attachments.'.*' => __('attachment'),
        ];
    }

    protected function hasQueuedAttachments(string $composer): bool
    {
        return $this->queuedAttachments(composer: $composer) !== []
            && $this->attachmentsEnabled(composer: $composer);
    }

    /**
     * Store the composer's queued uploads and append their markup to the body.
     */
    protected function appendQueuedAttachments(string $body, string $composer, CommentAttachmentContext $context): string
    {
        $files = $this->queuedAttachments(composer: $composer);

        if ($files === [] || ! CommentAttachments::enabled(context: $context)) {
            return $body;
        }

        $markup = array_map(
            fn (UploadedFile $file): string => CommentAttachments::storeAsHtml(file: $file, context: $context),
            array_values($files),
        );

        $this->{$this->attachmentProperty(composer: $composer)} = [];

        return $this->normalizeBody(CommentBodyAttachments::append(body: $body, attachments: $markup));
    }

    protected function persistUpdatedComment(
        Model $comment,
        string $body,
        CommentAttachmentContext $context,
        array $metadata = [],
    ): void {
        $comment->update(
            $this->commentAttributes(body: $body, parentId: $comment->parent_id, isEdit: true),
        );

        $comment = $comment->fresh();

        $this->afterCommentSaved(
            comment: $comment,
            context: $context,
        );

        CommentLifecycle::afterUpdate(new CommentLifecycleEvent(
            commentable: $this->record,
            body: $body,
            context: $context,
            comment: $comment,
            parentId: $comment->parent_id,
            metadata: $metadata,
        ));

        $this->cancelEdit();
        unset($this->comments);

        $this->toast(heading: __('Comment updated.'));
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

    protected function normalizeBody(mixed $body): string
    {
        if (! is_string($body)) {
            return '';
        }

        return trim(CommentAttachmentHtmlTransformer::transform(html: $body));
    }

    protected function isValidBody(string $body): bool
    {
        return CommentBodyValidator::isValid($body);
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

    protected function createComment(mixed $rawBody, ?int $parentId): ?Model
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
                $this->toast(heading: __('Unable to reply to that comment.'), variant: 'warning');

                return null;
            }
        }

        $composer = $parentId === null ? 'root' : 'reply';

        $this->validate($this->composerRules(composer: $composer), attributes: $this->composerValidationAttributes(composer: $composer));

        $body = $this->normalizeBody($rawBody);
        $body = $this->applyAiProofread(body: $body, composer: $composer);

        if (! $this->isValidBody($body) && ! $this->hasQueuedAttachments(composer: $composer)) {
            $this->notifyInvalidBody(composer: $composer);

            return null;
        }

        $context = $this->attachmentContext(composer: $composer);
        $body = $this->appendQueuedAttachments(body: $body, composer: $composer, context: $context);

        $beforeResult = CommentLifecycle::beforeCreate(new CommentLifecycleEvent(
            commentable: $this->record,
            body: $body,
            context: $context,
            parentId: $parentId,
        ));

        if ($beforeResult->defer) {
            $this->pendingCommentPayload = [
                'action' => 'create',
                'parentId' => $parentId,
                'body' => $body,
                'composer' => $composer,
                'metadata' => $beforeResult->metadata,
            ];
            $this->lifecyclePromptKey = $beforeResult->deferKey;
            $this->showLifecyclePrompt = true;

            return null;
        }

        if (! $beforeResult->proceed) {
            return null;
        }

        return $this->persistCreatedComment(
            body: $body,
            parentId: $parentId,
            composer: $composer,
            metadata: $beforeResult->metadata,
        );
    }

    protected function applyAiProofread(string $body, string $composer): string
    {
        $processor = app(CommentAiProcessor::class);

        $requested = match ($composer) {
            'root' => $this->proofreadWithAi,
            'edit' => $processor->defaultProofreadToggle(),
            default => false,
        };

        return $processor->applyProofreadIfRequested(bodyHtml: $body, requested: $requested);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $metadata
     */
    protected function finalizeDeferredCreate(array $payload, array $metadata): void
    {
        $comment = $this->persistCreatedComment(
            body: (string) ($payload['body'] ?? ''),
            parentId: isset($payload['parentId']) ? (int) $payload['parentId'] : null,
            composer: (string) ($payload['composer'] ?? 'root'),
            metadata: $metadata,
        );

        if ($comment === null) {
            return;
        }

        $composer = (string) ($payload['composer'] ?? 'root');

        if ($composer === 'root') {
            $this->resetRootComposer();
        }

        if ($composer === 'reply') {
            $this->cancelReply();
        }

        $this->toast(heading: $composer === 'reply' ? __('Reply added.') : __('Comment added.'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $metadata
     */
    protected function finalizeDeferredUpdate(array $payload, array $metadata): void
    {
        $commentId = isset($payload['commentId']) ? (int) $payload['commentId'] : null;

        if ($commentId === null) {
            return;
        }

        $comment = $this->findScopedComment($commentId);

        if (! $comment instanceof Model || ! CommentAuthor::canEdit($comment)) {
            return;
        }

        $this->persistUpdatedComment(
            comment: $comment,
            body: (string) ($payload['body'] ?? ''),
            context: $this->attachmentContext(composer: 'edit'),
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function persistCreatedComment(
        string $body,
        ?int $parentId,
        string $composer,
        array $metadata = [],
    ): ?Model {
        $comment = $this->record->comments()->create(
            $this->commentAttributes(body: $body, parentId: $parentId),
        );

        $context = $this->attachmentContext(composer: $composer);

        $this->afterCommentSaved(
            comment: $comment,
            context: $context,
        );

        CommentLifecycle::afterCreate(new CommentLifecycleEvent(
            commentable: $this->record,
            body: $body,
            context: $context,
            comment: $comment,
            parentId: $parentId,
            metadata: $metadata,
        ));

        return $comment;
    }

    protected function clearLifecycleDefer(): void
    {
        $this->showLifecyclePrompt = false;
        $this->lifecyclePromptKey = null;
        $this->pendingCommentPayload = null;
    }

    protected function recordIsPersisted(): bool
    {
        return $this->record?->exists === true
            && method_exists($this->record, 'comments');
    }

    protected function notifySaveRecordFirst(): void
    {
        $this->toast(heading: __('Save this record before adding comments.'), variant: 'warning');
    }

    protected function notifyInvalidBody(string $composer = 'root'): void
    {
        $message = __('Comment must be at least 2 characters.');

        $this->addError($this->formProperty(composer: $composer).'.body', $message);

        $this->toast(heading: $message, variant: 'warning');
    }

    protected function resetRootComposer(): void
    {
        $this->commentFormData = ['body' => null];
        $this->commentAttachments = [];
        $this->resetValidation(['commentFormData.body', 'commentAttachments', 'commentAttachments.*']);
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
