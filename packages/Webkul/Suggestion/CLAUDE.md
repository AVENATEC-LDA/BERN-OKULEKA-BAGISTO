# CLAUDE.md

Guidance for Claude Code working on the **Suggestion** package. The full agent reference lives in [`AGENTS.md`](./AGENTS.md) — read that first. This file documents Claude-specific workflow notes plus the highest-priority guardrails.

## Scope of this file

- Project-level Bagisto conventions live in `/CLAUDE.md` at the repository root.
- This file covers conventions specific to `packages/Webkul/Suggestion/`.
- Both files apply — when they conflict, the package-level file (this one) wins.

## Quick orientation

A Bagisto storefront module that adds a typeahead suggestions dropdown to the shop header search input. Customer types → debounced AJAX hits `GET /ajax-search` → controller queries `ProductRepository` → Vue component renders matching products inline below the input. Falls back to the core search form when admin disables the module.

```
Shopper types in header search bar
       │
       ▼
v-suggestion-searchbar.search()           (Vue, lives in published bottom.blade.php)
       │ axios GET ajax-search?term=…
       ▼
SuggestionController::search()             (Webkul/Suggestion/Http/Controllers/Shop)
       │ ProductRepository->setSearchEngine(...)->getAll([...])
       ▼
JsonResponse(['data' => [...products...]])
       │
       ▼
v-suggestion-searchbar.handleResponse()    (escapes name, builds highlightedName, sets suggestsResults)
```

There are **no models, no migrations, no admin UI** beyond the auto-generated config page. The whole module is one route, one controller method, two forked Blade layouts, and a system.php config.

## Top guardrails (the ones that bite hardest)

1. **The forked Shop layouts are intentional, not a smell.** This package publishes its own copies of `header/desktop/bottom.blade.php` and `header/mobile/index.blade.php` because the suggestion search bar must *replace* the core search input, not sit beside it. The `view_render_event('…search_bar.before/after')` hooks only allow sibling injection, so they don't fit. Don't propose refactoring this away. See [`AGENTS.md`](./AGENTS.md) → "The fork" for the upgrade workflow when core Shop changes.

2. **`v-html` only on `result.highlightedName`, never on `result.name`.** Product names are admin-controlled but not HTML-sanitized in storage; binding them with `v-html` is a textbook XSS sink. The current code routes through `stripHtml()` then `escapeHtml()` per slice, then wraps only the matched substring in `<span class="font-semibold">`. Don't rewrite the highlighter to inline raw `name` slices into a template literal.

3. **Search engine resolution must mirror core verbatim.** Use `engine === 'elastic' ? storefront_mode : 'database'` (strict `===`, value passthrough). Don't compare with `==` and don't hard-code `'elastic'` as the only accepted value — `storefront_mode` is forward-compatible with future engines.

4. **Numeric admin config values are strings in JS.** `{{ core()->getConfigData(...) }}` interpolates as a string. Wrap every numeric config in `Number(...)` with a fallback when reading into `data()`. String compare on lengths breaks at 10+.

5. **`aria-label` translation values must not contain `"`** — the inner `"` closes the HTML attribute and triggers a Vue compiler-2 error. Use `'` or no quotes.

6. **Translations live in 22 locales.** Adding a key in `en/app.php` means adding it in all 21 others. Run `php artisan bagisto:translations:check` — must show `Passed: 22 / Failed: 0` for the Suggestion row before committing. Reuse existing `shop::` keys (search/search-text/aria-labels) where they fit, before inventing new ones.

7. **`--force` on the install command is opt-in for a reason.** It overwrites the merchant's `resources/themes/default/views/...`, including any theme customizations they made. Default the flag to `false`; only pass `--force` when iterating on your own dev install.

Detailed rationale and code examples for each: see [`AGENTS.md`](./AGENTS.md).

## Common Claude Code workflows

### Modifying a storefront view

The two forked layouts live at:

- `src/Resources/views/shop/components/layouts/header/desktop/bottom.blade.php`
- `src/Resources/views/shop/components/layouts/header/mobile/index.blade.php`

The install command publishes them to `resources/themes/default/views/components/layouts/header/...`. The published copy is what's actually served.

When iterating locally: edit the published copy under `resources/themes/default/views/...` directly to avoid republishing on every change. Once stable, copy back to the package source so the publish step keeps in sync.

```bash
# After editing the package source, re-publish to the theme:
php artisan search-suggestion:install --force
# or, more directly:
php artisan vendor:publish --provider="Webkul\Suggestion\Providers\SuggestionServiceProvider" --tag=public --force
```

Always end an editing session by clearing the view cache:

```bash
php artisan view:clear
```

### Keeping desktop and mobile in sync

The desktop and mobile Vue components share the same data shape, methods, and watchers. When you change one, change the other. Specifically:

- `data()` shape: `term`, `isSearching`, `suggestsResults`, `showDropdown`, `blurTimeout`, `config { noOfTerms, minSearchTerms, display }`
- Methods: `search`, `handleResponse`, `highlightMatch`, `escapeHtml`, `stripHtml`, `getProductImage`, `getCategoryNames`, `handleBlur`, `handleFocus`
- Bind product names via `result.highlightedName`, never `result.name`

If you add a new method to one, mirror it to the other in the same commit.

### Adding a translation key

1. Edit `Resources/lang/en/app.php`.
2. Add the same key to all other 21 locales — translated values where possible, EN fallback otherwise.
3. Run `php artisan bagisto:translations:check`. Must report `Passed: 22 / Failed: 0` for the Suggestion package row.

If the string is one the core Shop already has (e.g. "Search", "Search products here"), prefer reusing the core key over duplicating into this package's namespace.

### Adding an admin config field

1. Append a new entry to the `fields` array in `src/Config/system.php` under `suggestion.suggestion.general`. Set `type`, `validation`, and `channel_based: true` for per-channel toggles.
2. Add the title/info translation keys under `admin.configuration.index.search-suggestion.general.<your-key>` in every locale's `app.php`.
3. Read the value via `core()->getConfigData('suggestion.suggestion.general.<your-key>')` in either the controller or the Vue `data()` block.

### Running the install command

```bash
php artisan search-suggestion:install                # publish without overwriting customizations
php artisan search-suggestion:install --force        # overwrite whatever is in the theme — destructive
```

There is no `--no-migrate` flag — this package has no migrations.

### Verifying the route resolves

```bash
php artisan route:list --name=search_suggestion
# → GET|HEAD  ajax-search  search_suggestion.search.index › Webkul\Suggestion\…\SuggestionController@search
```

If the route doesn't appear, the service provider isn't registered. Check `bootstrap/providers.php` for `Webkul\Suggestion\Providers\SuggestionServiceProvider::class`.

### Tracking core Shop layout drift

When upgrading the underlying Bagisto Shop version:

```bash
# Diff the upstream core layout against this package's fork
diff packages/Webkul/Shop/src/Resources/views/components/layouts/header/desktop/bottom.blade.php \
     packages/Webkul/Suggestion/src/Resources/views/shop/components/layouts/header/desktop/bottom.blade.php
```

Re-apply non-suggestion changes from core into the fork, preserving the `@if (core()->getConfigData('suggestion.suggestion.general.status'))` toggle and the `<v-suggestion-searchbar>` Vue templates. Repeat for mobile. Re-publish, smoke-test both shells.

## Code-style reminders

- Run `vendor/bin/pint` before committing. Pint's `binary_operator_spaces` rule will rewrite `=> '...'` to align inside arrays — let it.
- Default to no comments. Reserve them for *why* the code is the way it is — typically a hidden constraint or a workaround for one of the landmines listed above. Don't restate what well-named code already says.
- PHP 8.3 idioms welcome: constructor promotion, named args, `match`, `readonly`, `fn` callables.
- Don't add `@var` / `@param` PHPDoc on every method — modern type hints suffice.
- No emoji in console output, no emoji in comments — project convention.

## When something is broken

- Check the project-level `/CLAUDE.md` for cross-cutting Bagisto conventions.
- Check `packages/Webkul/QuestionAnswer/AGENTS.md` for a more elaborate example of a paid Bagisto module that uses the same fork-and-publish pattern (and adds admin UI, migrations, repositories, notifications on top — none of which this package has).
- Check `packages/Webkul/Shop/src/Resources/views/components/layouts/header/...` to compare the forked copies against current core when investigating drift.
- Check `packages/Webkul/Shop/src/Http/Controllers/SearchController.php` for the canonical search-engine resolution this package mirrors.
