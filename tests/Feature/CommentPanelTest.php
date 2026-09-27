<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Joranski\FilamentComments\Attachments\DefaultCommentAttachmentHandler;
use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Joranski\FilamentComments\Models\Comment;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->author = \User::factory()->create(['name' => 'Avery Author']);
    $this->commentable = \TestCommentable::factory()->create();

    $this->actingAs($this->author);
});

function panelComment(\TestCommentable $commentable, \User $user, string $body, ?int $parentId = null): Comment
{
    return $commentable->comments()->create([
        'user_id' => $user->id,
        'comment' => $body,
        'active' => true,
        'parent_id' => $parentId,
    ]);
}

test('panel is a plain livewire component without filament contracts', function (): void {
    $interfaces = array_map(strtolower(...), class_implements(CommentPanel::class));

    expect(array_filter($interfaces, fn (string $interface): bool => str_starts_with($interface, 'filament\\')))->toBe([]);
});

test('full layout renders a flux editor composer with the configured toolbar', function (): void {
    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->assertOk()
        ->assertSeeHtml('data-flux-editor')
        ->assertSeeHtml('wire:model="commentFormData.body"')
        ->assertSeeHtml('x-data="commentEditorMentions"')
        ->assertSee('No comments yet.')
        ->assertDontSeeHtml('fi-fo-rich-editor');
});

test('compact layout renders a textarea with mention autocomplete', function (): void {
    Livewire::test(CommentPanel::class, ['record' => $this->commentable, 'layout' => 'compact'])
        ->assertSeeHtml('data-flux-textarea')
        ->assertSeeHtml('commentMentionAutocomplete')
        ->assertDontSeeHtml('data-flux-editor');
});

test('unsaved records show a save-first callout instead of a composer', function (): void {
    Livewire::test(CommentPanel::class, ['record' => new \TestCommentable])
        ->assertSee('Save this record before adding comments.')
        ->assertDontSeeHtml('data-flux-editor');
});

test('adding a comment persists it, resets the composer and dispatches a toast', function (): void {
    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->set('commentFormData.body', '<p>First note</p>')
        ->call('addComment')
        ->assertHasNoErrors()
        ->assertSet('commentFormData.body', null)
        ->assertDispatched('toast-show', slots: ['heading' => 'Comment added.'])
        ->assertSee('First note');

    expect($this->commentable->comments()->sole())
        ->comment->toBe('<p>First note</p>')
        ->user_id->toBe($this->author->id);
});

test('a too-short comment is rejected with a validation error', function (): void {
    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->set('commentFormData.body', '<p>x</p>')
        ->call('addComment')
        ->assertHasErrors(['commentFormData.body'])
        ->assertDispatched('toast-show', dataset: ['variant' => 'warning']);

    expect($this->commentable->comments()->count())->toBe(0);
});

test('a non-string body fails validation', function (): void {
    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->set('commentFormData.body', ['type' => 'doc'])
        ->call('addComment')
        ->assertHasErrors(['commentFormData.body' => 'string']);
});

test('replies are threaded under the parent comment', function (): void {
    $parent = panelComment($this->commentable, $this->author, '<p>Parent</p>');

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->call('startReply', $parent->id)
        ->assertSet('replyingToCommentId', $parent->id)
        ->assertSeeHtml('wire:model="replyFormData.body"')
        ->set('replyFormData.body', '<p>Child reply</p>')
        ->call('submitReply')
        ->assertSet('replyingToCommentId', null)
        ->assertDispatched('toast-show', slots: ['heading' => 'Reply added.']);

    expect($parent->replies()->sole()->comment)->toBe('<p>Child reply</p>');
});

test('editing keeps existing attachments beside the editor and records the edit time', function (): void {
    $comment = panelComment(
        $this->commentable,
        $this->author,
        '<p>Original text</p><p><img src="https://cdn.test/files/scan.png" alt="scan.png"></p>',
    );

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->call('startEdit', $comment->id)
        ->assertSet('editFormData.body', '<p>Original text</p>')
        ->assertSet('editExistingAttachments', ['<img src="https://cdn.test/files/scan.png" alt="scan.png">'])
        ->assertSee('scan.png')
        ->set('editFormData.body', '<p>Updated text</p>')
        ->call('saveEdit')
        ->assertSet('editingCommentId', null)
        ->assertDispatched('toast-show', slots: ['heading' => 'Comment updated.']);

    $comment->refresh();

    expect($comment->comment)->toBe('<p>Updated text</p><p><img src="https://cdn.test/files/scan.png" alt="scan.png"></p>')
        ->and($comment->edited_at)->not->toBeNull();
});

test('removing an existing attachment drops it from the saved body', function (): void {
    $comment = panelComment($this->commentable, $this->author, '<p>Keep text</p><p><img src="https://cdn.test/a.png" alt="a.png"></p>');

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->call('startEdit', $comment->id)
        ->call('removeExistingAttachment', 0)
        ->call('saveEdit');

    expect($comment->refresh()->comment)->toBe('<p>Keep text</p>');
});

test('users cannot edit comments written by someone else', function (): void {
    $comment = panelComment($this->commentable, \User::factory()->create(), '<p>Not yours</p>');

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->call('startEdit', $comment->id)
        ->assertSet('editingCommentId', null);
});

test('comments with replies cannot be deleted', function (): void {
    $parent = panelComment($this->commentable, $this->author, '<p>Parent</p>');
    panelComment($this->commentable, $this->author, '<p>Child</p>', $parent->id);

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->call('deleteComment', $parent->id)
        ->assertDispatched('toast-show', slots: ['heading' => 'Delete replies before removing this comment.']);

    expect(Comment::query()->find($parent->id))->not->toBeNull();
});

test('own leaf comments can be deleted', function (): void {
    $comment = panelComment($this->commentable, $this->author, '<p>Remove me</p>');

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->call('deleteComment', $comment->id)
        ->assertDispatched('toast-show', slots: ['heading' => 'Comment deleted.']);

    expect(Comment::query()->find($comment->id))->toBeNull();
});

test('pins toggle and search filters by body and author', function (): void {
    panelComment($this->commentable, $this->author, '<p>Shipment delayed</p>');
    $other = panelComment($this->commentable, \User::factory()->create(['name' => 'Blair Buyer']), '<p>Invoice sent</p>');

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->call('togglePin', $other->id)
        ->assertDispatched('toast-show', slots: ['heading' => 'Comment pinned.'])
        ->set('search', 'shipment')
        ->assertSee('Shipment delayed')
        ->assertDontSee('Invoice sent')
        ->set('search', 'blair')
        ->assertSee('Invoice sent')
        ->assertDontSee('Shipment delayed');

    expect($other->refresh()->is_pinned)->toBeTrue();
});

test('mentions are parsed from plain @Name text in editor html', function (): void {
    $mentioned = \User::factory()->create(['name' => 'Jordan Analyst']);

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->set('commentFormData.body', '<p>Please review @Jordan Analyst today</p>')
        ->call('addComment');

    expect($this->commentable->comments()->sole()->mentioned_user_ids)->toBe([$mentioned->id]);
});

describe('attachments', function (): void {
    beforeEach(function (): void {
        Storage::fake('comments-public');
        config()->set('filament-comments.features.attachments', true);
        config()->set('filament-comments.attachments.handler', DefaultCommentAttachmentHandler::class);
        config()->set('filament-comments.attachments.disk', 'comments-public');
        config()->set('filament-comments.attachments.max_size_kb', 1024);
    });

    test('the composer shows a flux file upload when attachments are enabled', function (): void {
        Livewire::test(CommentPanel::class, ['record' => $this->commentable])
            ->assertSeeHtml('data-flux-file-upload')
            ->assertSeeHtml('wire:model="commentAttachments"');
    });

    test('uploaded files are stored and embedded, even without text', function (): void {
        Livewire::test(CommentPanel::class, ['record' => $this->commentable])
            ->set('commentAttachments', [UploadedFile::fake()->create('report.pdf', 20, 'application/pdf')])
            ->assertSee('report.pdf')
            ->call('addComment')
            ->assertHasNoErrors()
            ->assertSet('commentAttachments', []);

        $body = $this->commentable->comments()->sole()->comment;

        expect($body)->toContain('fi-comment-attachment-link')->toContain('report.pdf');
        expect(Storage::disk('comments-public')->allFiles('comment-attachments'))->toHaveCount(1);
    });

    test('images are embedded as img tags next to the text', function (): void {
        Livewire::test(CommentPanel::class, ['record' => $this->commentable])
            ->set('commentFormData.body', '<p>See photo</p>')
            ->set('commentAttachments', [UploadedFile::fake()->image('photo.png')])
            ->call('addComment');

        expect($this->commentable->comments()->sole()->comment)
            ->toStartWith('<p>See photo</p><p><img src="')
            ->toContain('alt="photo.png"');
    });

    test('files over the size limit or of a disallowed type are rejected', function (): void {
        Livewire::test(CommentPanel::class, ['record' => $this->commentable])
            ->set('commentFormData.body', '<p>With files</p>')
            ->set('commentAttachments', [UploadedFile::fake()->create('huge.pdf', 5000, 'application/pdf')])
            ->call('addComment')
            ->assertHasErrors(['commentAttachments.0'])
            ->set('commentAttachments', [UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload')])
            ->call('addComment')
            ->assertHasErrors(['commentAttachments.0']);

        expect($this->commentable->comments()->count())->toBe(0);
    });

    test('queued uploads can be removed before submitting', function (): void {
        Livewire::test(CommentPanel::class, ['record' => $this->commentable])
            ->set('commentAttachments', [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.png')])
            ->call('removeUpload', 'root', 0)
            ->assertCount('commentAttachments', 1)
            ->assertDontSee('a.png')
            ->assertSee('b.png');
    });

    test('new files can be attached while editing', function (): void {
        $comment = panelComment($this->commentable, $this->author, '<p>Needs a file</p>');

        Livewire::test(CommentPanel::class, ['record' => $this->commentable])
            ->call('startEdit', $comment->id)
            ->set('editAttachments', [UploadedFile::fake()->image('added.png')])
            ->call('saveEdit')
            ->assertHasNoErrors();

        expect($comment->refresh()->comment)->toStartWith('<p>Needs a file</p><p><img src="')->toContain('alt="added.png"');
    });
});
