# joranski/filament-comments

Audit-grade comment panels for Filament: threaded replies, pins, `@mentions` with database notifications, in-panel search, edit timestamps, and delete guards.

Built for morphMany `comments()` on any Eloquent record — orders, customers, service tickets, products, etc.

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP | ^8.3 |
| Laravel | ^12 |
| Filament | ^5 |
| Livewire | ^4 |

---

## Quick start

### 1. Install

```bash
composer require joranski/filament-comments
```

Publish config:

```bash
php artisan vendor:publish --tag=filament-comments-config
```

### 2. Wire models in config

```php
// config/filament-comments.php
use App\Models\Comment;
use App\Models\User;

return [
    'comment_model' => Comment::class,
    'user_model' => User::class,
    // ...
];
```

### 3. Ensure your database + model are ready

See [Host model contract](#host-model-contract) below.

### 4. Add to a Filament edit page

**Footer widget (most common):**

```php
// app/Filament/Resources/Orders/Pages/EditOrder.php
use Joranski\FilamentComments\Comments\Widgets\CommentsWidget;

protected function getFooterWidgets(): array
{
    return [
        CommentsWidget::class,
    ];
}
```

Filament passes `$record` to footer widgets automatically on edit pages.

**Embedded in a form schema:**

```php
use Joranski\FilamentComments\Comments\Schemas\CommentPanelSchema;

public static function configure(Schema $schema): Schema
{
    return $schema->components([
        // ...your fields
        CommentPanelSchema::embeddedForm(),
    ]);
}
```

### 5. Register a policy (recommended)

The panel checks Laravel policies on your comment model (`create`, `update`, `delete`, `deleteAny`). See [Authorization](#authorization).

---

## Architecture

```
Host app                          Package
─────────                         ───────
Comment model (Eloquent)    ←──   config comment_model
User model                  ←──   config user_model
CommentPolicy             ←──   CommentAuthor + Gate checks
comments() morphMany      ←──   CommentPanel queries
Migrations                  ←──   documented schema below
```

The package ships **UI + collaboration logic**. Your app owns persistence, permissions, and migrations.

---

## Host model contract

### Commentable record

Any model that receives comments needs a morphMany relationship:

```php
use Illuminate\Database\Eloquent\Relations\MorphMany;

public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

### Comment model

Minimum columns:

| Column | Type | Purpose |
|--------|------|---------|
| `user_id` | FK | Author |
| `commentable_type` / `commentable_id` | morph | Parent record |
| `comment` | text | Body (HTML from RichEditor) |
| `group` | string, nullable | Segment threads (`delay`, `chat`, etc.) |
| `topic` | string, nullable | Optional sub-thread label |
| `state` | string, nullable | e.g. `promoted` for chat bridge |
| `parent_id` | FK nullable | Reply threading |
| `is_pinned` | boolean | Pin to top |
| `edited_at` | timestamp nullable | Edit audit |
| `mentioned_user_ids` | json nullable | `@mention` targets |

**Collaboration migration example:**

```php
Schema::table('comments', function (Blueprint $table): void {
    $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
    $table->boolean('is_pinned')->default(false);
    $table->timestamp('edited_at')->nullable();
    $table->json('mentioned_user_ids')->nullable();
});
```

**Recommended scopes** (copy from a host app or implement your own):

```php
use Joranski\FilamentComments\Support\CommentGroups;

public const GROUP_DELAY = CommentGroups::DELAY;
public const GROUP_CHAT = CommentGroups::CHAT;

public function scopeRoots($query) { return $query->whereNull('parent_id'); }
public function scopePinnedFirst($query) { return $query->orderByDesc('is_pinned')->latest(); }
public function scopeExcludingGroups($query, array $groups) { /* ... */ }
public function hasReplies(): bool { return $this->replies()->exists(); }
public function isReply(): bool { return $this->parent_id !== null; }
```

Use `CommentGroups` constants so live chat and delay panels stay aligned with [`joranski/filament-live-chat`](https://github.com/joranski/filament-live-chat).

---

## Configuration reference

```php
// config/filament-comments.php
return [
    // Required — package throws RuntimeException if missing
    'comment_model' => \App\Models\Comment::class,
    'user_model' => \App\Models\User::class,

    // Toggle panel features globally
    'features' => [
        'replies' => true,
        'pins' => true,
        'mentions' => true,
        'search' => true,
        'edit' => true,
    ],

    // Groups hidden from the general comments panel
    'excluded_groups' => [
        \Joranski\FilamentComments\Support\CommentGroups::DELAY,
        \Joranski\FilamentComments\Support\CommentGroups::CHAT,
    ],

    // Filament edit URLs for @mention notification deep links
    'commentable_urls' => [
        \App\Models\Order::class => fn (\App\Models\Order $record): string =>
            \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $record]),
    ],
];
```

---

## Integration patterns

### A. Footer widget (edit pages)

```php
protected function getFooterWidgets(): array
{
    return [
        CommentsWidget::class,
    ];
}
```

Customize via widget properties in a subclass, or pass config from the page:

```php
CommentsWidget::make([
    'heading' => 'Internal notes',
    'layout' => 'compact',
    'excludedGroups' => ['chat', 'delay'],
]),
```

### B. Embedded form section

```php
CommentPanelSchema::embeddedForm(
    excludedGroups: ['chat'],
    heading: 'Comments',
);
```

On **create** pages, a dehydrated RichEditor placeholder is shown; users must save the record before comments persist.

### C. Standalone Livewire

Registered component: `filament-comments.comment-panel`

```blade
<livewire:filament-comments.comment-panel
    :record="$order"
    layout="full"
    heading="Comments"
/>
```

**Public properties:**

| Property | Default | Description |
|----------|---------|-------------|
| `layout` | `full` | `full` or `compact` |
| `group` | null | Show only this group |
| `topic` | null | Filter by topic |
| `excludedGroups` | from config | Hide groups |
| `allowReplies` | from config | Thread replies |
| `allowPins` | from config | Pin/unpin roots |
| `allowMentions` | from config | Parse `@Name` |
| `allowSearch` | from config | In-panel filter |
| `allowEdit` | from config | Edit own comments |

### D. Group- or topic-specific panel

Use a dedicated panel for a business topic (e.g. delays) while keeping general comments separate:

```php
CommentPanelSchema::widgetConfiguration(
    group: 'delay',
    topic: 'shipping',
    layout: 'compact',
    heading: 'Delay notes',
);
```

Pass that array to `CommentsWidget::make([...])` or the Livewire component.

---

## Authorization

The package delegates to **your** `CommentPolicy`:

| Action | Policy method | Who typically passes |
|--------|---------------|----------------------|
| Add / reply | `create` | Any authorized staff |
| Edit | `update` or author match | Author or moderator |
| Delete | `delete` + author, or `deleteAny` | Author if no replies; moderators |
| Pin | `update` on root comments | Moderators |

Delete is blocked when `hasReplies()` returns true — users must remove replies first.

With [Filament Shield](https://github.com/bezhanSalleh/filament-shield), permissions like `Create:Comment`, `Update:Comment`, `Delete:Comment` map cleanly to the policy above.

---

## Features in detail

### Threaded replies

- Only **root** comments accept replies (no nested depth > 1).
- Replies inherit `group` / `topic` from the parent thread scope.

### Pins

- Root comments only.
- Pinned comments sort first, then by latest.

### @Mentions

- Type `@Full Name` matching a user’s `name` column.
- On save, `mentioned_user_ids` is populated and Filament database notifications are sent.
- Configure `commentable_urls` so “View” links open the correct Filament edit page.

### Search

- Filters loaded comments client-side by plain-text body and author name.

### Edit / delete guards

- Edits set `edited_at` and re-parse mentions.
- Delete requires no replies unless the user has `deleteAny`.

---

## Customization

### Disable features per panel

```php
@livewire('filament-comments.comment-panel', [
    'record' => $record,
    'allowPins' => false,
    'allowMentions' => false,
])
```

### Publish views

```bash
php artisan vendor:publish --tag=filament-comments-views
```

Override partials under `resources/views/vendor/filament-comments/`.

---

## Live chat sibling package

For ephemeral coordination (polling chat, presence avatars, promote-to-comments), install [`joranski/filament-live-chat`](https://github.com/joranski/filament-live-chat). It stores chat in the same `comments` table with `group = 'chat'` and depends on this package.

Typical footer order:

```php
return [
    LiveChatWidget::class,   // ephemeral
    CommentsWidget::class,   // audit trail (excludes chat group)
    // ...domain-specific panels
];
```

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `Configure filament-comments.comment_model` | Set `comment_model` and `user_model` in config |
| Comments don’t save on create page | Expected — save the parent record first |
| Chat/delay notes appear in general panel | Add their groups to `excluded_groups` |
| Mention notifications missing URL | Add model class to `commentable_urls` |
| `@Name` not detected | User `name` must match exactly (case-insensitive) |

---

## Testing

Host app feature tests should cover:

- Scoped queries (group / excludedGroups)
- Mention parsing and notifications
- Reply, pin, edit, delete guard behavior

Package smoke tests: `composer test` inside `packages/filament-comments`.

---

## License

MIT
