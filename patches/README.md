# Filament RichEditor mention patch

Filament v5's `extension-mention.js` registers TipTap suggestion plugins in
`addProseMirrorPlugins()` from `this.storage.suggestions`, but TipTap's
`storage` getter returns a fresh object on each access. Assignments in
`onBeforeCreate()` do not survive, so `@` mentions never register a suggestion
plugin and the dropdown never opens.

## Fix

1. Add `buildConfiguredSuggestions()` (same logic as the old `onBeforeCreate` body).
2. In `addProseMirrorPlugins()`, return only the hydrate plugin (do **not** map suggestions there).
3. In `onCreate()`, call `buildConfiguredSuggestions()` and register each config with
   `this.editor.registerPlugin(Suggestion(config))`.
4. Wire `getSuggestionFromChar` on `extensionStorage.mention`.

Apply in:

- `vendor/filament/forms/resources/js/components/rich-editor/extension-mention.js`
- `vendor/filament/forms/dist/components/rich-editor.js`
- Run `php artisan filament:assets` so `public/js/filament/forms/components/rich-editor.js` updates.

See `filament-extension-mention.js.patch-reference` for the patched source file.
