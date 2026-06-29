# AGENTS.md

Guidance for AI coding agents (Claude Code, Cursor, Continue, etc.) working on this package. Keep changes idiomatic to Bagisto and don't reintroduce the gotchas listed below.

## What this package is

A Bagisto storefront module that adds a typeahead "search suggestions" dropdown to the shop header search bar. As the customer types, the package hits an AJAX endpoint that returns up to N matching products (with images and category names) and renders them under the input. Falls back to the core search form if the module is disabled in admin config.

It is a **paid module** that ships on top of an existing Bagisto install. Customers download and drop it into `packages/Webkul/Suggestion/` — you cannot assume any particular Bagisto patch level beyond 2.4.x.

Stack:

- **PHP 8.3+**, **Laravel 12**
- **Vue 3** components mounted via Bagisto's `app.mount('#app')` after `window.load`, defined inline in the published Blade with `<script type="text/x-template">` + `app.component(...)`
- **Tailwind 3** (only `app.css` shipping `@tailwind base/components/utilities` — actual styling is in the Blade markup)
- **Vite 7** for the (essentially empty) asset bundle, registered with Bagisto's "viters" config
- **Pest 3** for tests (no Pest tests in this package yet)
- No Eloquent models, no migrations, no repositories of its own — reuses `Webkul\Product\Repositories\ProductRepository`

## Directory tour

```
src/
├── Config/
│   ├── bagisto-vite.php          'viters' entry registering this package's CSS bundle
│   └── system.php                Admin → Configure → Search Suggestion fields
├── Console/Commands/
│   └── Install.php               php artisan search-suggestion:install
├── Http/Controllers/Shop/
│   └── SuggestionController.php  GET /ajax-search → JsonResponse(['data' => [...]])
├── Providers/
│   ├── SuggestionServiceProvider.php   Entry point — boot/register
│   └── EventServiceProvider.php        Hooks `bagisto.shop.layout.head.before` → publishes <link> for vite asset
├── Resources/
│   ├── assets/css/app.css        Single tailwind entry, builds to publishable/default/build/
│   ├── lang/                     22 locale folders (ar, bn, ca, de, en, es, fa, fr, he, hi_IN, id, it, ja, nl, pl, pt_BR, ro, ru, sin, tr, uk, zh_CN)
│   └── views/shop/
│       ├── components/layouts/style.blade.php          Tiny include that renders @bagistoVite for the suggestion CSS bundle
│       └── components/layouts/header/{desktop,mobile}/  Forked Shop header layouts — see "The fork" below
└── Routes/web.php                Single route: GET /ajax-search → SuggestionController::search
publishable/default/build/        Pre-built CSS bundle served from /public/themes/suggestion/
```

## Installation flow

1. User adds `Webkul\Suggestion\Providers\SuggestionServiceProvider::class` to `bootstrap/providers.php`.
2. User adds the namespace `Webkul\\Suggestion\\` → `packages/Webkul/Suggestion/src` to root `composer.json`'s `autoload.psr-4` and runs `composer dump-autoload`.
3. User runs `php artisan search-suggestion:install` — this command:
   - publishes `publishable/default/build/` → `public/themes/suggestion/`
   - publishes `Resources/views/shop/components/layouts/header/{desktop,mobile}/...` → `resources/themes/default/views/...` (overrides core Shop's header layouts)
   - clears caches
4. User toggles **Configure → Search Suggestion → General → Status** to enable.

There is no migration; the package has no DB state of its own.

## The fork

This package's most important design decision is that it **publishes forks of two core Shop layout files** to `resources/themes/default/views/`:

- `components/layouts/header/desktop/bottom.blade.php`
- `components/layouts/header/mobile/index.blade.php`

The fork wraps the search input in:

```blade
@if (core()->getConfigData('suggestion.suggestion.general.status'))
    <v-suggestion-searchbar></v-suggestion-searchbar>
@else
    {{-- original core search form --}}
@endif
```

…and ships the `<v-suggestion-searchbar>` Vue component templates inline in the same files.

**Don't try to refactor this to use `view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.before')` listeners.** Those event hooks exist but they only inject siblings around the existing search input — they cannot *replace* it, which is what this package needs to do. The fork is the deliberate, accepted approach.

The cost of the fork is that this package has to track changes to core Shop's header layouts. When upgrading the underlying Bagisto version:

1. Diff the new core `packages/Webkul/Shop/src/Resources/views/components/layouts/header/desktop/bottom.blade.php` against the previous core version.
2. Apply the same diff to this package's forked copy at `src/Resources/views/shop/components/layouts/header/desktop/bottom.blade.php`, preserving the `<v-suggestion-searchbar>` toggle and Vue templates.
3. Repeat for `mobile/index.blade.php`.
4. Re-publish via `php artisan search-suggestion:install --force` on a test install and click through both desktop and mobile shells.

`--force` overwrites the published copy in `resources/themes/default/views/`. **Treat `--force` as destructive** — it overwrites whatever the merchant has there. The Install command defaults `--force` to false; only pass it when you know the target tree is yours to overwrite.

## Core patterns to follow

### Reuse the Product repository, not Eloquent directly

The controller flows through `Webkul\Product\Repositories\ProductRepository::setSearchEngine($engine)->getAll([...])`. Engine selection mirrors core `Webkul\Shop\Http\Controllers\SearchController::index`:

```php
$searchEngine = core()->getConfigData('catalog.products.search.engine') === 'elastic'
    ? core()->getConfigData('catalog.products.search.storefront_mode')
    : 'database';
```

Don't hard-code `'elastic'` or compare with `==`. If `storefront_mode` ever grows new values (e.g. `'opensearch'`), passing it through verbatim is forward-compatible; matching `=== 'elastic'` quietly downgrades to database search.

### The AJAX response is intentionally minimal

`SuggestionController::search()` returns `JsonResponse(['data' => $items])` — a flat list of products with `images` and `categories` eager-loaded. The Vue components do all the highlighting / formatting client-side. Keep the controller dumb; if you need a new field on the dropdown row, eager-load it in the controller and read it client-side, rather than building HTML in PHP.

### Vue components live inline in Blade

Both `<v-suggestion-searchbar>` (desktop) and `<v-suggestion-searchbar-mobile>` are defined inline using:

```html
<script type="text/x-template" id="v-suggestion-searchbar-template"> ... </script>
<script type="module">
    app.component('v-suggestion-searchbar', { template: '#v-suggestion-searchbar-template', ... });
</script>
```

This is the same pattern the core Shop layout uses for `<v-desktop-category>`, `<v-mobile-drawer>`, etc. Keep the two components in sync (data shape, methods, watchers) — they share the same response handler, the same highlight escaping, the same `handleBlur`/`handleFocus`/`showDropdown` logic. If you change one, change the other.

### Translations live in 22 locale files

Every translatable string must exist in **all 22 locales** with the **exact same structure** (key paths, nesting). Note this package has 22 locales — including `ro` (Romanian) — which is one more than QuestionAnswer's 21. The set matches `packages/Webkul/Shop/src/Resources/lang/`.

After adding/changing keys, run:

```bash
php artisan bagisto:translations:check
```

Must report `Passed: 22 / Failed: 0` for the **Suggestion** package row. Reuse existing `shop::` keys where they fit (the search-bar labels and aria-labels all do) before inventing new ones — avoids needing to translate the same word into 22 languages.

### Settings live in system.php only

There's no admin route, controller, or view — admin configuration is purely the `Configure → Search Suggestion → General` page generated from `Config/system.php`. New settings = new entry in the `fields` array + new key in every locale's `app.php`.

## Landmines to avoid

These are real bugs that have been hit in this codebase. Don't reintroduce them.

### Don't `v-html` raw product names

`result.name` from the AJAX response is whatever the admin entered — it can contain HTML. Earlier versions of this package wrote a template literal `${result.name.slice(...)}<span>...${result.name.slice(...)}</span>` and bound it via `v-html`, which is the textbook Vue XSS sink.

The current code builds a `result.highlightedName` that:

1. Runs the name through `stripHtml(value)` (uses `document.createElement('div').textContent`) to strip any embedded markup.
2. Escapes each slice via `escapeHtml(value)` (replaces `& < > " '`).
3. Wraps **only** the matched substring in `<span class="font-semibold">…</span>`.

Don't shortcut this. If you need to display a product name verbatim (no highlighting), use `v-pre` or `:text-content` (or just `{{ }}` interpolation on the safe text) — never `v-html` on a value that originated server-side.

### Form submit elements must actually exist

A previous `submitForm()` reached for `document.getElementsByName('term')[0]` and `document.getElementById('search-form')`. Neither element existed in the rendered DOM, so the icon-button click threw `Cannot read properties of undefined`. The fix was to drop the JS handler entirely and use a hidden `<button type="submit">` inside the existing form, letting the browser submit naturally to `route('shop.search.index')` via the input's `name="query"`.

When wiring submission, prefer the form's native submit over JS DOM gymnastics. The component is inside an `<x-shop::form>` already.

### Config values from Blade are strings, not numbers

`{{ core()->getConfigData('suggestion.suggestion.general.min_search_terms') }}` interpolates as a string. `"5".length >= "5"` happens to work for single-digit lengths via JS coercion, but `"10".length >= "10"` is `false` because string compare is lexical (`"3" > "10"`). All numeric config values should be wrapped in `Number(...)` with a sensible fallback when read into the Vue `data()`:

```js
config: {
    minSearchTerms: Number("{{ core()->getConfigData('suggestion.suggestion.general.min_search_terms') }}") || 1,
    noOfTerms:      Number("{{ core()->getConfigData('suggestion.suggestion.general.products_limit') }}") || 5,
}
```

### Don't write relative URLs in JS handlers

`'search?query=' + term` looks fine on the homepage and is broken on `/checkout/onepage` (resolves to `/checkout/search?...`). Always build absolute URLs from named routes:

```js
:href="'{{ route('shop.search.index') }}?query=' + encodeURIComponent(term) + '&...'"
```

`encodeURIComponent` matters — un-encoded `&` or `#` in the query string silently corrupt the URL.

### `aria-label` translation values must not contain `"`

If a translation contains `"`, Blade outputs it literally inside an HTML attribute, the inner `"` ends the attribute, and Vue's compiler chokes on the trailing fragment. Use `'` or no quotes inside such strings — never `"`.

### Don't add a `--no-migrate` flag

QuestionAnswer's install command has `--no-migrate` because it ships migrations. **This package has no migrations.** Don't copy that flag across; the install only republishes assets and clears caches.

### Don't drop the `--force` opt-in

`vendor:publish --force=true` overwrites whatever the merchant has in `resources/themes/default/views/...`, which may be their own customizations of the Shop header. The Install command exposes `--force` as a flag (default `false`) so Composer post-install scripts don't silently nuke a customer's theme. Don't change the default to `true`.

### Don't add comments for the sake of it

Project convention: default to no comments. Reserve them for *why* the code is the way it is — the Vue highlighter has a surprising shape because of XSS concerns; the engine selection mirrors core's exact expression for forward-compat. If you add a comment, make it about the constraint, not the mechanic.

## Common change patterns

### Adding a new admin config field

1. Append to the `fields` array in `src/Config/system.php`.
2. Add the new translation key under `admin.configuration.index.search-suggestion.general.<your-key>` in `Resources/lang/en/app.php`.
3. Propagate the same key to all 21 other locales.
4. Read it via `core()->getConfigData('suggestion.suggestion.general.<your-key>')` in either the controller or the Vue `data()` block.
5. Run `php artisan bagisto:translations:check` — must show `Passed: 22 / Failed: 0` for Suggestion.

### Adding a new field to the dropdown rows

1. Eager-load the relation/column in `SuggestionController::search()` via `->with([...])`.
2. Read `result.<field>` directly in the Vue template — don't add a method just to project a property.
3. If the value is HTML-influenced, route it through `escapeHtml()` (or `stripHtml()` first if it could contain markup) before binding.
4. Update **both** desktop and mobile templates so they stay in sync.

### Modifying the Vue components

Edit the Blade source in `src/Resources/views/shop/components/layouts/header/{desktop,mobile}/...` and re-publish:

```bash
php artisan search-suggestion:install --force
php artisan view:clear
```

For local iteration, edit the published copy at `resources/themes/default/views/components/layouts/header/...` directly to skip the publish round-trip — but copy back to the package source when stable, or your changes get lost on the next install.

### Tracking core Shop layout changes

When you bump Bagisto's Shop package: see "The fork" above. Diff core, apply the same diff to the package source, re-publish, smoke test desktop + mobile.

## Code style

- Run `vendor/bin/pint` before committing — Bagisto's house style. Pay attention to `binary_operator_spaces` (Pint will rewrite `=> '...'` to align inside arrays).
- PHP 8.3 idioms encouraged: constructor property promotion (`public function __construct(protected ProductRepository $productRepository) {}`), named args, `readonly`, `match`, first-class callables.
- No emoji in console output, no emoji in comments. The project-level CLAUDE.md is explicit about this.
- File naming: `<Class>Controller`, `Install` (not `InstallSuggestion`), service providers named `<Module>ServiceProvider`.

## Testing

There are no Pest tests in this package yet, only Playwright e2e tests under `tests/e2e-pw/`. If adding behaviour, add tests alongside (Pest under `tests/Feature` or `tests/Unit`, Playwright under `tests/e2e-pw/tests`).

```bash
# Pest (when added)
vendor/bin/pest packages/Webkul/Suggestion/tests

# Playwright e2e (the package already ships these)
cd packages/Webkul/Suggestion/tests/e2e-pw
npm install
npx playwright install --with-deps chromium
npx playwright test
```

Playwright tests need a running `php artisan serve` and a seeded database. The existing specs cover module on/off, product visibility, category visibility, and catalog rule behaviour.

## When in doubt

- Check the project-level `/CLAUDE.md` for cross-cutting Bagisto conventions.
- Check `packages/Webkul/QuestionAnswer/AGENTS.md` for a more elaborate example of a paid Bagisto module that follows the same fork-and-publish approach (with admin UI, migrations, repositories, notifications — none of which this package has).
- Check `packages/Webkul/Shop/src/Resources/views/components/layouts/header/{desktop,mobile}/` to compare the forked copies against current core when investigating drift.
- Check `packages/Webkul/Shop/src/Http/Controllers/SearchController.php` for the canonical search-engine resolution this package mirrors.
