# joranski/filament-comments

Audit-grade comment panels for Filament: nested threaded replies, inline reply composers, pins, `@mentions` with database notifications, in-panel search, edit timestamps, and delete guards.

Built for morphMany `comments()` on any Eloquent record — orders, customers, service orders, products, etc.

---

## Table of contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Host setup checklist](#host-setup-checklist)
4. [Ways to use comments](#ways-to-use-comments)
5. [Configuration reference](#configuration-reference)
6. [Layouts & composers](#layouts--composers)
7. [Threading & UI behavior](#threading--ui-behavior)
8. [Groups, topics & multiple panels](#groups-topics--multiple-panels)
9. [Authorization](#authorization)
10. [Features in detail](#features-in-detail)
11. [Customization](#customization)
12. [Live chat sibling package](#live-chat-sibling-package)
13. [Troubleshooting](#troubleshooting)
14. [Testing](#testing)

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP | ^8.3 |
| Laravel | ^12 |
| Filament | ^5 |
| Livewire | ^4 |

---

## Installation

```bash
composer require joranski/filament-comments
```

Publish config (recommended):

```bash
php artisan vendor:publish --tag=filament-comments-config
```

Publish migrations (optional — the package auto-loads them; publish only if you prefer vendor copies in `database/migrations`):

```bash
php artisan vendor:publish --tag=filament-comments-migrations
```

Publish views (optional overrides):

```bash
php artisan vendor:publish --tag=filament-comments-views
```

---

## Host setup checklist

Complete these steps once per application:

| Step | Action |
|------|--------|
| 1 | Run migrations (auto-loaded from the package, or publish with `--tag=filament-comments-migrations`) |
| 2 | Set `comment_model` and `user_model` in `config/filament-comments.php` |
| 3 | Implement `comments()` morphMany on commentable models (or use `HasComments`) |
| 4 | Optionally extend `Joranski\FilamentComments\Models\Comment` for app-specific behavior |
| 5 | Register a `CommentPolicy` (Filament Shield works well) |
| 6 | Map `commentable_urls` for `@mention` notification deep links |
| 7 | Choose one or more [integration patterns](#ways-to-use-comments) below |

**Minimal config:**

```php
// config/filament-comments.php
use App\Models\Comment;
use App\Models\User;

return [
    'comment_model' => Comment::class,
    'user_model' => User::class,
];
```

The package throws a `RuntimeException` at runtime if model bindings are missing.

---

## Ways to use comments

There are **four supported integration patterns**. Pick based on where staff should interact with comments.

### 1. Footer widget on edit pages (recommended default)

Best for record edit screens where comments sit below the main form. Filament passes `$record` to footer widgets automatically.

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

**Customize heading, layout, or scroll height:**

```php
protected function getFooterWidgets(): array
{
    return [
        CommentsWidget::make([
            'heading' => 'Internal notes',
            'layout' => 'full',           // RichEditor (default)
            'threadMaxHeight' => 800,     // px; null = no cap
            'excludedGroups' => ['chat', 'delay'],
        ]),
    ];
}
```

**Subclass for reusable defaults:**

```php
namespace App\Filament\Widgets;

use Joranski\FilamentComments\Comments\Widgets\CommentsWidget as BaseCommentsWidget;

class OrderCommentsWidget extends BaseCommentsWidget
{
    public string $heading = 'Order notes';

    public ?int $threadMaxHeight = 900;
}
```

---

### 2. Embedded in a Filament form schema

Best when comments should appear inline with other form sections (e.g. Order or Customer edit forms).

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

**Options:**

```php
CommentPanelSchema::embeddedForm(
    excludedGroups: ['chat'],  // null = config default
    heading: 'Comments',
);
```

**Create vs edit behavior:**

| Page | What users see |
|------|----------------|
| **Edit** | Full `CommentPanel` Livewire component — load thread, add root comments, reply inline, edit, pin, search |
| **Create** | Dehydrated RichEditor placeholder only (`single_comment`) — comments cannot persist until the parent record is saved |

The create-page placeholder uses the same toolbar as the live panel so the UI feels consistent; it is **not** wired to persistence.

---

### 3. Standalone Livewire component

Use anywhere you have a persisted Eloquent `$record` with `comments()`:

**Blade:**

```blade
<livewire:filament-comments.comment-panel
    :record="$order"
    layout="full"
    heading="Comments"
/>
```

**Filament `Livewire::make()` (custom pages, infolists, etc.):**

```php
use Filament\Schemas\Components\Livewire;
use Joranski\FilamentComments\Comments\Livewire\CommentPanel;

Livewire::make(
    component: CommentPanel::class,
    data: fn (Order $record): array => [
        'record' => $record,
        'layout' => 'full',
        'showHeading' => true,
    ],
)->columnSpanFull(),
```

**Registered alias:** `filament-comments.comment-panel`

---

### 4. Group- or topic-scoped panel

Use a **dedicated panel** for a business segment while keeping general comments separate. Comments are filtered by `group` and/or `topic` columns.

**Via widget configuration helper:**

```php
use Joranski\FilamentComments\Comments\Schemas\CommentPanelSchema;
use Joranski\FilamentComments\Comments\Widgets\CommentsWidget;

protected function getFooterWidgets(): array
{
    return [
        CommentsWidget::make(
            CommentPanelSchema::widgetConfiguration(
                group: 'delay',
                topic: 'shipping',
                layout: 'compact',
                heading: 'Delay notes',
                threadMaxHeight: 600,
            ),
        ),
    ];
}
```

**Direct Livewire props:**

```blade
<livewire:filament-comments.comment-panel
    :record="$serviceOrder"
    group="delay"
    topic="wop"
    layout="compact"
    heading="WOP delays"
    :excluded-groups="[]"
/>
```

| Prop | Effect |
|------|--------|
| `group` | Only comments where `group` matches |
| `topic` | Further filter by `topic` |
| `excludedGroups` | Hide groups (general panel uses config default to hide `delay` + `chat`) |
| `excludeGroup` | Legacy single-group exclude; merged into excluded list |

**Domain-specific panels:** Some apps build custom widgets (e.g. a delay tracker with topic toggles) that write to the same `comments` table with `group = 'delay'`. Exclude that group from the general audit panel via `excluded_groups`.

---

## Configuration reference

Full published config shape:

```php
return [
    // Required
    'comment_model' => \App\Models\Comment::class,
    'user_model' => \App\Models\User::class,

    // Feature toggles (can be overridden per panel via Livewire props)
    'features' => [
        'replies' => true,
        'pins' => true,
        'mentions' => true,
        'search' => true,
        'edit' => true,
        'reply_notifications' => true,
    ],

    // Hidden from the general comments panel
    'excluded_groups' => [
        \Joranski\FilamentComments\Support\CommentGroups::DELAY,
        \Joranski\FilamentComments\Support\CommentGroups::CHAT,
    ],

    // @mention user search limit
    'mention_search_limit' => 20,

    // Scrollable thread max height (px); null disables cap
    'thread_max_height' => 1000,

    // Reply thread visual indent per nest level (px)
    'reply_indent_px' => 15,

    // Max reply nesting depth (root = 0)
    'max_reply_depth' => 10,

    // Authorization: auto (policy if registered, else fallback), policy, or fallback
    'authorization' => [
        'mode' => 'auto',
        'fallback' => [
            'view_any' => true,
            'view' => true,
            'create' => true,
            'update_own' => true,
            'delete_own' => true,
            'pin' => false,
        ],
        'author_may_update_own' => true,
        'author_may_delete_own' => true,
    ],

    // Filament edit URLs for @mention / reply notification "View" links
    'commentable_urls' => [
        \App\Models\Order::class => fn (\App\Models\Order $record): string =>
            \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $record]),
    ],
];
```

### Per-panel property overrides

Pass to `CommentsWidget::make([...])` or the Livewire component:

| Property | Default | Description |
|----------|---------|-------------|
| `record` | null | Commentable Eloquent model (required for persistence) |
| `layout` | `full` | `full` = RichEditor; `compact` = Textarea |
| `group` | null | Scope to one group |
| `topic` | null | Scope to one topic |
| `excludeGroup` | null | Exclude one group (legacy) |
| `excludedGroups` | from config | Exclude multiple groups |
| `heading` | `Comments` | Panel title |
| `placeholder` | `Comments` | Root composer placeholder (non-mention mode) |
| `addButtonLabel` | `Add comment` | Root submit button |
| `showHeading` | `true` | Show heading inside panel (widget section may also show one) |
| `allowReplies` | from config | Nested replies + inline reply composer |
| `allowPins` | from config | Pin/unpin root comments |
| `allowMentions` | from config | Parse `@Name` and notify |
| `allowSearch` | from config | In-panel text filter |
| `allowEdit` | from config | Edit with `edited_at` audit |
| `threadMaxHeight` | from config | Thread scroll area height (px) |

---

## Layouts & composers

| Layout | Composer | Mentions |
|--------|----------|----------|
| `full` | Filament RichEditor with shared toolbar | Filament native mention dropdown (`@` trigger) |
| `compact` | Textarea | Alpine popup autocomplete (↑/↓/Enter/Escape) |

**Composer placement:**

| Action | Where the editor appears |
|--------|--------------------------|
| New root comment | Top of panel (always) |
| Reply | Inline RichEditor/textarea **directly under** the target comment; auto-scrolls into view |
| Edit | Inline under the comment being edited |

The top composer is **root-only**. Replies never hijack the top field.

Shared toolbar (bold, lists, link, etc.) is defined once in `CommentComposerField` for consistency across root, reply, edit, and create-page placeholder.

---

## Threading & UI behavior

- **Root comments** load with `parent_id = null`, pinned first, then newest.
- **Replies** nest up to `max_reply_depth` (default 10).
- Each level indents by `reply_indent_px` (default 15px) with connector lines; action buttons stay right-aligned at the thread edge.
- **Search** filters roots client-side; a root stays visible if any nested reply matches.
- **Delete** blocked when `hasReplies()` unless user has `deleteAny`.
- **Pins** apply to root comments only.

---

## Groups, topics & multiple panels

Use `CommentGroups` constants so panels stay aligned with [`joranski/filament-live-chat`](https://github.com/joranski/filament-live-chat):

```php
use Joranski\FilamentComments\Support\CommentGroups;

CommentGroups::DELAY;           // 'delay'
CommentGroups::CHAT;            // 'chat'
CommentGroups::STATE_PROMOTED;  // 'promoted' (chat → audit bridge)
```

**Typical service-order footer (SWM pattern):**

```php
return [
    LiveChatWidget::class,    // group = chat (ephemeral coordination)
    CommentsWidget::class,    // audit trail; excludes chat + delay
    DelayWidget::class,       // host-specific; group = delay, per-topic
];
```

New comments inherit the panel's `group` / `topic` scope when created.

---

## Host model contract

The package ships `Joranski\FilamentComments\Models\Comment` with migrations, factory, scopes, and rating hooks. Extend it when you need app-specific behavior:

```php
use Joranski\FilamentComments\Models\Comment as BaseComment;

class Comment extends BaseComment
{
    // e.g. MassPrunable, custom casts, VA legacy helpers
}
```

Or point `comment_model` directly at the package class.

### Commentable record

Use `HasComments` or a morphMany:

```php
use Joranski\FilamentComments\Concerns\HasComments;

class Product extends Model
{
    use HasComments;
}
```

```php
use Illuminate\Database\Eloquent\Relations\MorphMany;

public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

### Database

Migrations load automatically. Greenfield installs create the full `comments` table; existing apps receive missing collaboration columns only (`parent_id`, `is_pinned`, `edited_at`, `mentioned_user_ids`).

Publish copies with:

```bash
php artisan vendor:publish --tag=filament-comments-migrations
```

**Legacy column reference** (included in package migration): `old_va_id`, `parent_id`, `is_pinned`, `edited_at`, `mentioned_user_ids`, plus standard comment fields. Scopes, relationships, and rating hooks live on the package `Comment` model.

---

## Authorization

Comment UI permissions flow through **`CommentAuthorization`**, which supports [Filament Shield](https://github.com/bezhanSalleh/filament-shield) **and** apps without Shield.

### How it works

| `authorization.mode` | Behavior |
|------------------------|----------|
| **`auto`** (default) | Uses Laravel policies when a `CommentPolicy` is registered; otherwise uses fallback rules |
| **`policy`** | Always requires policy checks (denies when no policy) |
| **`fallback`** | Ignores policies; uses fallback rules only (useful for internal tools) |

```php
'authorization' => [
    'mode' => 'auto',
    'fallback' => [
        'view_any' => true,      // show comments panel / thread
        'view' => true,          // see individual comments
        'create' => true,        // any authenticated user
        'update_own' => true,    // author may edit own comment
        'delete_own' => true,    // author may delete own (no replies)
        'pin' => false,          // pinning requires policy / moderator
    ],
    'author_may_update_own' => true,  // when policy mode: also allow authors without Update permission
    'author_may_delete_own' => true,  // when policy mode: Delete permission + author, or DeleteAny
],
```

### Filament Shield permission map

The Shield policy stub (`CommentPolicyShield.php.stub`) implements **every default Shield resource ability** so `shield:generate` produces a complete permission set:

| Shield permission | Policy method | Used by comment panel |
|-------------------|---------------|------------------------|
| `ViewAny:Comment` | `viewAny` | Show thread panel, search, empty states |
| `View:Comment` | `view` | Render each comment (roots + replies); Livewire actions require view |
| `Create:Comment` | `create` | Add root comments and replies |
| `Update:Comment` | `update` | Edit comments; pin/unpin roots |
| `Delete:Comment` | `delete` | Delete own comment (no replies) when `author_may_delete_own` |
| `DeleteAny:Comment` | `deleteAny` | Delete any comment, including threads with replies |
| `Restore:Comment` | `restore` | Reserved (Filament Resource / soft deletes) |
| `ForceDelete:Comment` | `forceDelete` | Reserved |
| `ForceDeleteAny:Comment` | `forceDeleteAny` | Reserved |
| `RestoreAny:Comment` | `restoreAny` | Reserved |
| `Replicate:Comment` | `replicate` | Reserved |
| `Reorder:Comment` | `reorder` | Reserved |

The Livewire panel does not expose restore, replicate, or reorder — those methods exist so Shield permission generation stays aligned with a standard Filament resource. If you add soft deletes or a `CommentResource` admin UI later, the policy is already in place.

### UI action matrix

| UI action | Policy method(s) | Fallback (no policy) |
|-----------|------------------|----------------------|
| See panel / thread | `viewAny` | Authenticated users |
| See a comment | `view` | Authenticated users |
| Add root comment | `create` | Authenticated users |
| Reply | `create` + depth limit | Same as create |
| Edit | `update`, or author if `author_may_update_own` | Author only |
| Delete | `deleteAny`, or `delete` + author if `author_may_delete_own` | Author only, no replies |
| Pin | `update` on root | `fallback.pin` (default false) |

Delete is always blocked when `hasReplies()` is true unless the user has `deleteAny`.

### With Filament Shield (recommended for production)

1. Publish the Shield policy stub:

```bash
php artisan vendor:publish --tag=filament-comments-policy-shield
```

2. Register a **shield-only** `CommentResource` (no navigation) so Shield generates permissions:

```bash
php artisan shield:generate --resource=CommentResource --option=policies
```

3. Assign roles (e.g. `ViewAny:Comment`, `View:Comment`, `Create:Comment`, `Update:Comment`, `Delete:Comment`, `DeleteAny:Comment`).

4. Keep `authorization.mode` as **`auto`**.

The generated policy checks Shield permissions (`Create:Comment`, etc.). Super-admin bypass is handled by Shield's `Gate::before` — no extra package configuration needed.

**Author overrides:** By default, authors may still edit/delete their own comments even without `Update:Comment` / `Delete:Comment` when `author_may_update_own` / `author_may_delete_own` are true. Set either to `false` for strict role-only moderation.

### Without Filament Shield

**Option A — Rely on fallback (fastest):**

Leave `authorization.mode` as `auto` and **do not register** a `CommentPolicy`. Any authenticated staff member can comment; authors edit/delete their own.

**Option B — Publish standalone policy:**

```bash
php artisan vendor:publish --tag=filament-comments-policy
```

This registers explicit `create` / `update` / `delete` rules without Spatie permission strings. Customize the stub for your roles.

**Option C — Force fallback:**

```php
'authorization' => ['mode' => 'fallback'],
```

Ignores any registered policy — useful for trusted internal panels only.

### Policy registration

Laravel must resolve your policy to the comment model:

```php
// AppServiceProvider::boot()
Gate::guessPolicyNamesUsing(fn (string $model) => str_replace('Models', 'Policies', $model).'Policy');

// Or explicit registration:
Gate::policy(App\Models\Comment::class, App\Policies\CommentPolicy::class);
```

The package **does not** require `filament-shield` or `spatie/laravel-permission` as Composer dependencies.

---

## Features in detail

### Threaded replies

- Nest up to **`max_reply_depth`** (default **10**).
- Top composer = root only; **Reply** opens inline composer under that comment.
- **`reply_notifications`**: database notification to direct parent author (skips self-replies).
- Replies inherit panel `group` / `topic`.

### Pins

Root comments only. Pinned sort first, then by latest.

### @Mentions

- **`full` layout:** Filament RichEditor mention provider; type `@` after whitespace.
- **`compact` layout:** Textarea + Alpine popup (↑/↓/Enter/Escape).
- On save: `mentioned_user_ids` populated; Filament DB notifications sent.
- Configure **`commentable_urls`** for working "View" links.

### Search

Client-side filter on plain-text body and author name (includes nested replies when matching).

### Edit / delete

- Edits set `edited_at` and re-parse mentions.
- Delete blocked when replies exist (unless `deleteAny`).

### Read-only static partial

For simple delete-only lists outside the full panel:

```blade
@include('filament-comments::partials.list-item-static', ['comment' => $comment])
```

Requires a Livewire parent with `deleteComment()` (e.g. custom widget).

---

## Customization

### Disable features on one panel

```php
CommentsWidget::make([
    'record' => $record,
    'allowPins' => false,
    'allowMentions' => false,
    'allowSearch' => false,
]),
```

### Override views

Publish then edit under `resources/views/vendor/filament-comments/`:

| Partial | Purpose |
|---------|---------|
| `comment-panel.blade.php` | Panel shell |
| `partials/list-item.blade.php` | Single comment row |
| `partials/list-item-reply-form.blade.php` | Inline reply composer |
| `partials/thread-item.blade.php` | Root + reply tree |
| `partials/reply-nest.blade.php` | Nested reply branch |
| `components/mention-autocomplete.blade.php` | Compact mention popup |

### Programmatic helpers

| Class | Purpose |
|-------|---------|
| `CommentComposerField` | Shared RichEditor/textarea definitions |
| `CommentThreadDepth` | Depth limits + CSS indent helpers |
| `CommentMentionParser` | Parse `@Name` from HTML |
| `CommentContextResolver` | Resolve notification URLs |
| `CommentGroups` | Shared group constants |

---

## Live chat sibling package

For ephemeral coordination (polling chat, presence, promote-to-comments), install [`joranski/filament-live-chat`](https://github.com/joranski/filament-live-chat). It uses the same `comments` table with `group = 'chat'` and depends on this package.

Keep **`chat`** in `excluded_groups` on the audit `CommentsWidget` so live chat does not appear in the permanent thread.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `Configure filament-comments.comment_model` | Set `comment_model` and `user_model` in config |
| Comments don't save on create page | Expected — save the parent record first |
| Chat/delay notes in general panel | Add their groups to `excluded_groups` |
| Mention notifications missing URL | Add model class to `commentable_urls` |
| `@Name` not detected | User `name` must match (case-insensitive) |
| Reply button missing | Check `max_reply_depth` or `allowReplies` |
| Top composer still used for replies | Upgrade package — replies use inline composer |

---

## Testing

**Package** (authorization, threading, mentions, model, migrations):

```bash
cd path/to/filament-comments && composer test
```

**Host app** (Filament Livewire UI integration — CommentPanel, CommentsWidget, app-specific rating UI):

- Keep Pest feature tests under `tests/Feature/Filament/CommentPanelTest.php` in the consuming app
- See `tests/Feature/CommentPanelIntegration.md` in the package for why full UI tests run in the host

---

## Architecture

```
Host app                          Package
─────────                         ───────
Comment model (Eloquent)    ←──   config comment_model
User model                  ←──   config user_model
CommentPolicy             ←──   CommentAuthor + Gate checks
comments() morphMany      ←──   CommentPanel queries
Migrations                  ←──   documented schema above
```

The package ships **UI + collaboration logic**. Your app owns persistence, permissions, and migrations.

---

## License

MIT
