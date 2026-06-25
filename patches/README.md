# Filament RichEditor mention workaround

Filament v5's `extension-mention.js` registers TipTap suggestion plugins in
`addProseMirrorPlugins()` from `this.storage.suggestions`, but TipTap's
`storage` getter returns a fresh object on each access. Assignments in
`onBeforeCreate()` do not survive, so `@` mentions never register a suggestion
plugin and Filament's built-in dropdown never opens.

## Package fix (recommended)

`joranski/filament-comments` **v0.4.3+** ships a package-owned mention bridge for
`CommentRichEditor`:

- `resources/views/components/rich-editor-mention-bridge.blade.php`
- Wrapped automatically from `resources/views/components/rich-editor.blade.php`
  when mentions are configured

The bridge listens for the editor load event, watches the ProseMirror surface for
`@query`, calls `CommentPanel::searchMentionUsers()` via Livewire, and inserts
proper TipTap `mention` nodes. **No Filament vendor patch is required.**

Compact layout (`Textarea` + `mention-autocomplete`) was already package-owned and
unaffected.

## Optional Filament vendor patch

If you need `@` mentions on **stock** Filament `RichEditor` fields outside this
package, you can still patch Filament's mention extension manually. See
`filament-extension-mention.js.patch-reference` for reference source.

Apply in:

- `vendor/filament/forms/resources/js/components/rich-editor/extension-mention.js`
- Rebuild or patch `vendor/filament/forms/dist/components/rich-editor.js`
- Run `php artisan filament:assets` so `public/js/filament/forms/components/rich-editor.js` updates.

**Do not patch vendor JS in apps that only use `CommentRichEditor` for mentions.**
