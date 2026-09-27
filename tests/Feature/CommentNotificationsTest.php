<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Joranski\FilamentComments\Comments\Livewire\CommentPanel;
use Joranski\FilamentComments\Contracts\SendsCommentNotifications;
use Joranski\FilamentComments\Notifications\DatabaseCommentNotificationSender;
use Joranski\FilamentComments\Support\CommentNotificationMessage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->author = \User::factory()->create(['name' => 'Avery Author']);
    $this->commentable = \TestCommentable::factory()->create();
    $this->actingAs($this->author);
});

test('the default sender stores a plain database notification with an html body', function (): void {
    expect(app(SendsCommentNotifications::class))->toBeInstanceOf(DatabaseCommentNotificationSender::class);

    $mentioned = \User::factory()->create(['name' => 'Jordan Analyst']);

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->set('commentFormData.body', '<p>Hi @Jordan Analyst</p>')
        ->call('addComment');

    $data = $mentioned->notifications()->sole()->data;

    expect($data)
        ->format->toBe('filament-comments')
        ->kind->toBe(CommentNotificationMessage::KIND_MENTION)
        ->title->toBe('You were mentioned in a comment')
        ->and($data['body'])->toContain('<strong>Avery Author</strong>');
});

test('self-mentions are skipped unless enabled in config', function (): void {
    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->set('commentFormData.body', '<p>Note to @Avery Author</p>')
        ->call('addComment');

    expect($this->author->notifications()->count())->toBe(0);

    config()->set('filament-comments.notifications.notify_self_mentions', true);

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->set('commentFormData.body', '<p>Another note to @Avery Author</p>')
        ->call('addComment');

    expect($this->author->notifications()->count())->toBe(1);
});

test('replies notify the parent author through the bound sender', function (): void {
    $sender = new class implements SendsCommentNotifications
    {
        /** @var list<array{recipient: int, kind: string}> */
        public array $sent = [];

        public function send(Model $recipient, CommentNotificationMessage $message): void
        {
            $this->sent[] = ['recipient' => (int) $recipient->getKey(), 'kind' => $message->kind];
        }
    };

    app()->instance(SendsCommentNotifications::class, $sender);

    $parentAuthor = \User::factory()->create();
    $parent = $this->commentable->comments()->create(['user_id' => $parentAuthor->id, 'comment' => '<p>Parent</p>', 'active' => true]);

    Livewire::test(CommentPanel::class, ['record' => $this->commentable])
        ->call('startReply', $parent->id)
        ->set('replyFormData.body', '<p>My reply</p>')
        ->call('submitReply');

    expect($sender->sent)->toBe([['recipient' => $parentAuthor->id, 'kind' => CommentNotificationMessage::KIND_REPLY]]);
});
