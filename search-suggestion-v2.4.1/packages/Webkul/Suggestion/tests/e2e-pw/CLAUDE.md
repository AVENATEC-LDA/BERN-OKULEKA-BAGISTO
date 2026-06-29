# CLAUDE.md — Claude Code Instructions for `e2e-pw/`

This file provides guidance to Claude Code when working **inside the
`e2e-pw/` directory**. The host module's root `CLAUDE.md` (or the wider
Bagisto root `CLAUDE.md`, if any) does not apply here — this folder is
a standalone Playwright suite that must stay runnable after being
copied anywhere on disk.

For cross-agent rules (applicable to any AI tool), see `AGENTS.md` in
this same directory. This file adds Claude-Code-specific workflow
notes on top of those rules.

## Project overview

Playwright + TypeScript suite for the Bagisto **Suggestion** module,
built on the shared `bagisto-e2e-pw-skeleton`. The skeleton's three
seed specs are still in place and protected:

- `tests/admin/01-authentication/01-auth.setup.ts` — logs in once and
  saves storage state to `.state/admin-auth.json`.
- `tests/admin/02-dashboard/01-dashboard.spec.ts` — loads
  `/admin/dashboard` using the saved session.
- `tests/shop/homepage.spec.ts` — verifies the storefront is reachable.

On top of those seeds, the suite drives a sequential **admin sets up →
shop verifies** flow against the suggestion search dropdown:

1. `tests/admin/03-configuration/suggestion/01-enable-suggestion.spec.ts` —
   enables the module and applies the desired config from `data/`.
2. `tests/admin/04-catalog/categories/01-create-category.spec.ts` —
   creates a category, persists `suggestion-category` in runtime state.
3. `tests/admin/04-catalog/products/01-create-product.spec.ts` —
   creates a simple product and ticks the seeded category in the same
   create-form flow (no separate edit/attach step).
4. `tests/shop/suggestion/{01-product-visible,02-category-shown}.spec.ts` —
   types in the storefront search bar and verifies the dropdown row +
   the category line under the matched product.

Created entity names flow from admin specs to shop specs through
`utils/runtimeState.ts` (`.state/runtime/*.json`).

Pricing concerns (catalog rules, tax, customer-group prices) are
deliberately out of scope: the Suggestion dropdown does not render
prices. Those flows belong in the catalog/marketing module suites.

## Common commands

One-time setup:

```bash
npm install
npm run install:browsers
```

Day-to-day commands:

| Command                           | Purpose                              |
| --------------------------------- | ------------------------------------ |
| `npm test`                        | Headless, all tests                  |
| `npm run test:headed`             | Visible browser                      |
| `npm run test:ui`                 | UI mode (interactive debugging)      |
| `npm run test:debug`              | Inspector mode (step-through)        |
| `npm run typecheck`               | `tsc --noEmit`                       |
| `npm run format`                  | Prettier write (auto-fix every file) |
| `npm run format:check`            | Prettier check (CI-style)            |
| `npm run test:report`             | Open the last HTML report            |
| `npx playwright test tests/admin` | Only admin tests                     |
| `npx playwright test tests/shop`  | Only shop tests                      |

**Always run `npm run format` and `npm run typecheck` before marking
TypeScript changes complete.**

## Hard rules (do not violate)

These mirror `AGENTS.md`. Reproduced here so they apply even when only
`CLAUDE.md` is in context:

1. **No comments. Ever.** This project bans comments in every file —
   `.ts`, `.env`, JSON, everything. No `//`, no `/* */`, no JSDoc, no
   `#` in `.env*` files. Code must be self-explanatory through clear
   naming, small functions, and intent-revealing structure. If a
   comment feels necessary, rename, extract, or restructure until it
   isn't. **When you write or edit any file inside `e2e-pw/`, strip
   every comment before saving — even ones you wrote yourself moments
   ago.**
2. **Self-contained.** No imports/reads from outside `e2e-pw/`. The
   folder must run after being moved anywhere on disk.
3. **Single env source.** All `process.env` access goes through
   `utils/env.ts`. Never read `process.env` from a spec, page object,
   fixture, or config file.
4. **Domain via env only.** Never hardcode hosts. The target host
   comes from `env.baseUrl`. The only place a literal URL appears is
   `.env.example`.
5. **Specs import from `fixtures/test`**, never from `@playwright/test`
   directly. That guarantees they get the custom fixtures.
6. **No new top-level npm dependencies without approval.** Current set:
   `@playwright/test`, `@types/node`, `dotenv`, `prettier`,
   `typescript`.
7. **Never delete or rewrite the seed specs without explicit
   approval.** These three files are the canaries that prove the
   install is reachable and the auth pipeline works:
    - `tests/admin/01-authentication/01-auth.setup.ts`
    - `tests/admin/02-dashboard/01-dashboard.spec.ts`
    - `tests/shop/homepage.spec.ts`

    The matching page objects (`pages/admin/LoginPage.ts`,
    `pages/admin/DashboardPage.ts`, `pages/shop/HomePage.ts`) are
    protected for the same reason.

8. **Keep the three docs in sync.** See "Documentation sync" below.

## Architecture

### Layered design

```
tests/        ← What to verify (specs are declarative, data from JSON)
   ↓ uses
data/         ← Test data (JSON) + fixture images
   ↓ imported by
fixtures/     ← Shared setup/teardown (auth, env, page-init hooks)
   ↓ uses
pages/        ← How to interact with each screen (POM + image path resolution)
   ↓ uses
utils/        ← Pure helpers (env loader, paths, version check)
```

Specs should never call `page.locator(...)` directly — they should call
methods on a page object that hides the selectors. This keeps specs
readable and makes selector changes a one-file fix.

### Key files

| File                                                              | Purpose                                                                |
| ----------------------------------------------------------------- | ---------------------------------------------------------------------- |
| `playwright.config.ts`                                            | Three projects: `admin-setup`, `admin`, `shop`                         |
| `utils/env.ts`                                                    | Validates and normalizes all env vars                                  |
| `utils/paths.ts`                                                  | State dir, data path, `ensureStateDir()`                               |
| `utils/bagistoVersion.ts`                                         | Parses `BAGISTO_VERSION` + `isBagistoVersionAtLeast()`                 |
| `utils/runtimeState.ts`                                           | `writeRuntimeState` / `readRuntimeState` for cross-spec data           |
| `fixtures/test.ts`                                                | Custom `test` extension; add new fixtures here                         |
| `pages/BasePage.ts`                                               | Abstract POM parent — `visit()`, `dataPath()`                          |
| `pages/admin/LoginPage.ts`                                        | Admin login form (used **only** by `01-auth.setup.ts`)                 |
| `pages/admin/DashboardPage.ts`                                    | Admin dashboard landing                                                |
| `pages/admin/configuration/suggestion/SuggestionConfigPage.ts`         | Admin → Configure → Search Suggestion form                                |
| `pages/admin/catalog/categories/CategoryCreatePage.ts`                 | Admin → Catalog → Categories → Create                                     |
| `pages/admin/catalog/products/ProductCreatePage.ts`                    | Admin → Catalog → Products → Create + edit form (simple type, ticks category) |
| `pages/shop/HomePage.ts`                                               | Storefront landing page object                                            |
| `pages/shop/SuggestionSearchbarPage.ts`                                | Header search input + `#suggest` dropdown — owns every selector           |
| `tests/admin/01-authentication/01-auth.setup.ts`                       | Logs in once per run, saves `.state/admin-auth.json`                      |
| `tests/admin/02-dashboard/01-dashboard.spec.ts`                        | Dashboard loads using saved session                                       |
| `tests/admin/03-configuration/suggestion/01-enable-suggestion.spec.ts` | Enables the module + applies `data/configuration/suggestion/enabled.json` |
| `tests/admin/04-catalog/categories/01-create-category.spec.ts`         | Creates the category, persists `suggestion-category` runtime state        |
| `tests/admin/04-catalog/products/01-create-product.spec.ts`            | Creates the simple product (ticks the seeded category), persists `suggestion-product` |
| `tests/shop/homepage.spec.ts`                                          | `BAGISTO_BASE_URL` is reachable and serves the storefront                 |
| `tests/shop/suggestion/01-product-visible.spec.ts`                     | Verifies the dropdown row + highlighted match for the typed substring     |
| `tests/shop/suggestion/02-category-shown.spec.ts`                      | Verifies the attached category name shows under the product name          |

### Test area split (mandatory)

The suite is organized around two top-level areas:

- **`tests/admin/`** — drives `/admin/...`. Page objects live in
  `pages/admin/`.
- **`tests/shop/`** — exercises the storefront from a customer's
  perspective. Page objects live in `pages/shop/`.

Never put admin specs under `tests/shop/` or vice versa. Anything
genuinely shared lives directly under `pages/` (e.g. `BasePage.ts`).

**Sub-folders mirror Bagisto URL paths.** Anything under
`/admin/settings/...` lives in `pages/admin/settings/` and
`tests/admin/settings/`. Same for other URL prefixes:

| Bagisto URL prefix     | Tests path               | Page objects path        |
| ---------------------- | ------------------------ | ------------------------ |
| `/admin/settings/...`  | `tests/admin/settings/`  | `pages/admin/settings/`  |
| `/admin/catalog/...`   | `tests/admin/catalog/`   | `pages/admin/catalog/`   |
| `/admin/customers/...` | `tests/admin/customers/` | `pages/admin/customers/` |
| `/admin/sales/...`     | `tests/admin/sales/`     | `pages/admin/sales/`     |
| `/admin/marketing/...` | `tests/admin/marketing/` | `pages/admin/marketing/` |

When asked for a new admin screen, always check the Bagisto URL first
and place files at the matching depth — don't flatten everything into
`pages/admin/` once the suite has more than a handful of screens.

### Entity folder structure (mandatory)

Inside any resource group (e.g. `settings/`), every Bagisto entity
gets **its own folder** named after the lowercase plural URL slug.
**Create the folder from the start, even when only one test file
exists** — restructuring later is more friction than starting in the
right shape.

```
tests/admin/03-settings/01-channels/
├── 01-listing.spec.ts     # GET    /admin/settings/channels
├── 02-create.spec.ts      # POST   /admin/settings/channels/create
├── 03-edit.spec.ts        # PUT    /admin/settings/channels/edit/{id}
└── 04-delete.spec.ts      # DELETE /admin/settings/channels/edit/{id}

pages/admin/settings/channels/
├── ChannelsPage.ts        # listing
├── ChannelCreatePage.ts   # create form
└── ChannelEditPage.ts     # edit form
```

**Rules:**

1. **Folder name** is the lowercase plural URL slug (`channels`,
   `currencies`, `attribute-families`).
2. **Spec files** use a two-digit numeric prefix followed by the
   action verb: `01-listing.spec.ts`, `02-create.spec.ts`,
   `03-edit.spec.ts`, `04-delete.spec.ts`. The prefix controls
   execution order — Playwright runs files alphabetically within a
   project. Never `channels-create.spec.ts` (redundant) or
   `index.spec.ts` (collides mentally with TS barrel files).
3. **Never flatten** an entity back out into the parent folder once
   the entity folder exists.
4. **`describe` block per file** uses the full breadcrumb so reports
   are readable: `"Admin — settings › channels › listing"`,
   `"Admin — settings › channels › create"`, etc. The numeric file
   prefix (`01-`, `02-`) is **not** part of the breadcrumb — use the
   bare action verb only. `01-listing.spec.ts` → `listing`, not
   `01-listing`.

### Sequential execution (mandatory)

The entire suite runs with `fullyParallel: false` and `workers: 1` in
`playwright.config.ts`. This guarantees strict sequential execution —
no concurrency between tests or projects. Every test sees the exact
state left by the previous one. **Never change these settings** — the
suite is a sequential flow, not independent regression tests.

### Execution order (mandatory)

Admin tests follow a **sequential human-like flow** — channels before
currencies, currencies before attribute families, etc. The `admin`
project uses a glob `admin/**/*.spec.ts` as its `testMatch` —
Playwright discovers all spec files automatically and runs them
**alphabetically by full path**. Execution order is controlled
entirely by **numeric prefixes** on both folders and spec file names:
`01-listing.spec.ts` runs before `02-edit.spec.ts`, and so on.

**When adding a new admin spec:**

1. Create the spec file in the correct entity folder with the next
   numeric prefix (`03-delete.spec.ts`, for example).
2. Run `npm test` and verify the test count increased by one.

**Hard rule:**

- **Never rely on `testMatch` array order** to control execution
  sequence — Playwright ignores it and always sorts alphabetically.
  Use numeric filename prefixes instead.

### Page object naming (mandatory)

Plural for listing pages, singular + action suffix for entity-specific
pages. Mirrors Laravel resource route naming:

| Bagisto route name               | URL                                  | Page object         |
| -------------------------------- | ------------------------------------ | ------------------- |
| `admin.settings.channels.index`  | `/admin/settings/channels`           | `ChannelsPage`      |
| `admin.settings.channels.create` | `/admin/settings/channels/create`    | `ChannelCreatePage` |
| `admin.settings.channels.edit`   | `/admin/settings/channels/edit/{id}` | `ChannelEditPage`   |

Same rule for every other entity:

| Entity           | Listing                 | Create                      | Edit / View               |
| ---------------- | ----------------------- | --------------------------- | ------------------------- |
| Channel          | `ChannelsPage`          | `ChannelCreatePage`         | `ChannelEditPage`         |
| Attribute        | `AttributesPage`        | `AttributeCreatePage`       | `AttributeEditPage`       |
| Attribute family | `AttributeFamiliesPage` | `AttributeFamilyCreatePage` | `AttributeFamilyEditPage` |
| Product          | `ProductsPage`          | `ProductCreatePage`         | `ProductEditPage`         |
| Order            | `OrdersPage`            | `OrderCreatePage`           | `OrderViewPage`           |

**Never** use a plain singular name like `ChannelPage` — it's
ambiguous about which screen you mean. **Never** use plural for
entity-specific pages (`ChannelsEditPage` reads as "edit many channels
at once", which isn't a thing in Bagisto).

### Admin authentication (login once per run) — mandatory

Admin tests **never log in themselves**. Login is performed exactly
once per `npm test` run by a Playwright **setup project** that saves
cookies + localStorage to `.state/admin-auth.json`. Every admin spec
then runs with that storage state preloaded — `page` is already
authenticated when the test starts.

Three projects in `playwright.config.ts`:

| Project       | testMatch             | Dependencies  | Storage state            |
| ------------- | --------------------- | ------------- | ------------------------ |
| `admin-setup` | `admin/**/*.setup.ts` | —             | none (fresh context)     |
| `admin`       | `admin/**/*.spec.ts`  | `admin-setup` | `.state/admin-auth.json` |
| `shop`        | `shop/**/*.spec.ts`   | `admin`       | none (anonymous user)    |

**The auth setup is already wired up.** The boilerplate ships with
`tests/admin/01-authentication/01-auth.setup.ts`, which uses
`AdminLoginPage` to log in, waits for the dashboard via
`AdminDashboardPage`, then saves storage state to
`ADMIN_AUTH_STATE_PATH` after calling `ensureStateDir()`. Don't add a
second auth setup unless you need a different admin user — talk to
the user before doing this.

**When writing a new admin spec:**

1. Add the page object under `pages/admin/<area>/<Screen>Page.ts`.
2. Add the spec under `tests/admin/<area>/<NN>-<verb>.spec.ts`. The
   `admin` project depends on `admin-setup`, so the `page` fixture is
   already authenticated — navigate straight to the `/admin/...` URL
   you need.

**Rules:**

- Just use the `page` fixture from `fixtures/test`. It is already
  authenticated. Navigate straight to whatever `/admin/...` page you
  need.
- **Do not import any `LoginPage`** from a `*.spec.ts` file. That
  class exists only for the auth setup file.
- **Do not call `page.context().clearCookies()`** — it wipes the saved
  auth and breaks every subsequent admin test in the run.
- **Do not call `page.goto("admin/login")`** expecting the login form
  — with the saved session you'll be redirected to the dashboard.
- The state file path is **always** `ADMIN_AUTH_STATE_PATH` from
  `utils/paths.ts`. Never duplicate the literal path elsewhere.
- The state directory is created with `ensureStateDir()` from
  `utils/paths.ts`. Use it; don't `mkdirSync` inline.

### Path aliases

`tsconfig.json` defines:

- `@fixtures/*` → `fixtures/*`
- `@pages/*` → `pages/*`
- `@utils/*` → `utils/*`
- `@data/*` → `data/*`

Prefer aliases once relative paths get deep (`../../../`).

## Conventions

### Imports

Specs always import from `fixtures/test`:

```ts
import { test, expect } from "../../fixtures/test";
```

Page objects import types from `@playwright/test` only:

```ts
import type { Page, Locator, Response } from "@playwright/test";
```

What you must never do inside a spec:

```ts
import { test, expect } from "@playwright/test";
```

### Test data and images (mandatory)

**Test data and fixture images are co-located under `data/` and
mirror the Bagisto URL path** — the same rule that governs `pages/`
and `tests/`. Organized as
`data/<resource-group>/<entity>/<id>/<meaningful-name>.json` with
images alongside in an `images/` subfolder:

```
data/settings/channels/1/
├── store-details.json
└── images/
    ├── logo.png
    └── favicon.png
```

The JSON stores **full relative paths from `data/`** for file
references:

```json
{
    "name": "Bagisto Store",
    "logo": "settings/channels/1/images/logo.png",
    "favicon": "settings/channels/1/images/favicon.png"
}
```

**Responsibility split:**

| Layer       | Knows about                         | Does NOT know about               |
| ----------- | ----------------------------------- | --------------------------------- |
| JSON data   | What values to use + relative paths | Where `data/` lives on disk       |
| Test spec   | Which data to pass to which method  | Path resolution, selectors        |
| Page object | Selectors + how to upload           | What the test data values are     |
| `BasePage`  | `data/` root path                   | Which entity or field is involved |

**Rules:**

1. **No `path.join`, `path.resolve`, or `DATA_PATH` in tests.** Path
   resolution is the page object's job via `this.dataPath()`.
2. **No hardcoded strings in specs.** All test values come from JSON.
3. **Co-locate JSON and images.** Never separate data files from their
   images.
4. **Data folders mirror Bagisto URL paths.** Same way `pages/` and
   `tests/` are split. No numeric prefixes on data folders —
   execution order doesn't apply to data.
5. **One folder per entity record.** JSON file name should be
   meaningful (e.g. `store-details.json`, not `data.json`).
6. **File paths in JSON are relative to `data/`.** The page object's
   `this.dataPath(relativePath)` resolves them to absolute paths.

### Page Object Model

- Each screen → one class in `pages/` extending `BasePage`.
- Methods are intent-revealing: `createChannel(data)`, not
  `clickChannelSubmitButton()`.
- Selectors live inside the page object, never in specs.
- Constructor takes a `Page` and passes it up: `super(page)`.
- `this.dataPath(relativePath)` resolves relative paths under `data/`.
  Use it in upload methods — tests should never see filesystem paths.

**Member ordering convention (mandatory):**

Members inside a page object class must follow this order:

| Order | Section                | Example members                                         |
| ----- | ---------------------- | ------------------------------------------------------- |
| 1     | Constructor            | `constructor(page)`                                     |
| 2     | Navigation             | `open()`, `openById(id)`                                |
| 3     | Locators by UI section | Grouped top-to-bottom matching the page's visual layout |
| 4     | Page-level locators    | `saveButton`, `successMessage`                          |
| 5     | Public actions         | `uploadLogo()`, `save()`, `delete()`                    |
| 6     | Private helpers        | `logoSection()`, `removeExistingImage()`                |

Locators are grouped by the form sections as they appear on screen
(e.g. General → Design → SEO for the channel edit page). This makes
the class read like a top-to-bottom scan of the UI. Public interface
comes first so consumers see what the POM offers at a glance. Private
implementation stays at the bottom.

**When creating or editing a page object, always check:** is the
member order correct? Constructor → navigation → locators (by visual
layout) → page-level locators → public actions → private helpers. If
not, reorder before saving.

### Fixtures

- Add to `fixtures/test.ts` whenever multiple specs need the same
  setup.
- Each fixture is an `async (deps, use) => { ... }` function that
  runs setup, calls `await use(value)` to hand the value to the test,
  and then runs teardown after `use` returns.
- **Currently exported:** an `env` fixture that wraps the validated
  env object from `utils/env.ts` (so specs can destructure `{ env }`
  instead of importing it directly), and an overridden `page` fixture
  that injects a global CSS rule hiding Bagisto's `.phpdebugbar` dev
  overlay — without this the debug bar can intercept clicks near the
  page footer on non-prod installs.

### Naming

| Kind         | Convention                      | Example                            |
| ------------ | ------------------------------- | ---------------------------------- |
| Spec files   | `NN-kebab-case.spec.ts`         | `01-listing.spec.ts`               |
| Page objects | `PascalCase.ts`                 | `LoginPage.ts`, `DashboardPage.ts` |
| Utilities    | `camelCase.ts`                  | `randomSku.ts`                     |
| Test areas   | `tests/admin/` or `tests/shop/` | `tests/admin/channels.spec.ts`     |
| Page areas   | `pages/admin/` or `pages/shop/` | `pages/admin/ChannelsPage.ts`      |

`describe` blocks and `setup`/`test` names must mirror the file path
as a breadcrumb using `" — "` (area separator) and `" › "` (depth
separator). The pattern:

```
"Area — path › segment › ... › action"
```

**Rules for `describe` blocks:**

- Start with the area (`Admin` or `Shop`), followed by `—`.
- Then the path segments joined by `›`, matching the folder/file
  structure under `tests/admin/` or `tests/shop/`.
- The numeric file prefix (`01-`, `02-`, …) is **not** part of the
  breadcrumb. Use the bare action verb: `listing`, not `01-listing`.
- For setup files without a `describe`, put the full breadcrumb in
  the `setup("...")` name.

**Rules for `test` descriptions:**

- **All lowercase** — only capitalize when genuinely necessary
  (e.g. a proper noun like `BAGISTO_BASE_URL`).
- **Short and direct** — describe the behavior, not the context. The
  `describe` block already provides the context.
- **No redundant auth context** — never write "authenticated admin"
  or "logged-in user" in a test name. The admin project guarantees
  authentication; restating it is noise.

### Comments

**Banned everywhere.** See Hard Rule #1.

When you find yourself wanting to write a comment:

| Instinct                              | Do this instead                                       |
| ------------------------------------- | ----------------------------------------------------- |
| "Explain what this regex does"        | Extract a named function: `stripTrailingSlashes(url)` |
| "Label a block of related logic"      | Extract the block into a method or helper             |
| "Note that this value comes from env" | Use a descriptive name: `actionTimeoutMs`             |
| "TODO: handle this later"             | Open an issue or just don't merge it                  |
| "Why we use `!` after `response`"     | Restructure with an early `if (!response)`            |

If after restructuring the code is still unclear, the design is
wrong — not the absence of comments. Fix the design.

### TypeScript

- `strict`, `noUnusedLocals`, `noUnusedParameters` are on. Don't
  relax them.
- `npm run typecheck` is the source of truth for type validity — run
  it before claiming work is done.
- Avoid `any`; use `unknown` + narrowing if a type is genuinely
  unknown.

### Formatting (Prettier)

This project uses **Prettier** as its formatter. Config lives in
`.prettierrc.json` (4-space indent, double quotes, semicolons,
trailing commas, 80-char print width, LF line endings).

- **Always run `npm run format` before marking work complete.**
  Prettier is the source of truth for formatting; do not hand-format
  around it.
- **`npm run format:check`** is the CI-style check that exits
  non-zero on drift.
- **Do not add per-file or per-directory Prettier overrides** without
  approval. The single config in `.prettierrc.json` applies to
  everything.
- **`.prettierignore`** excludes `node_modules`, `playwright-report`,
  `test-results`, `.state`, `package-lock.json`, and `.env*` (except
  `.env.example`).
- After **any** edit to a `.ts`, `.json`, or `.md` file inside
  `e2e-pw/`, run `npm run format` as part of finishing the change.

## Workflow expectations

When the user asks for changes inside `e2e-pw/`:

1. **Strip comments as you write.** Before saving any file you
   touched, re-read it mentally and remove every `//`, `/* */`,
   JSDoc block, and `#` line in `.env*` files. Treat this as part of
   "finishing" the change, not a separate cleanup step.
2. **Read before writing.** Always read the current file before
   editing.
3. **Run typecheck.** After TS edits, run `npm run typecheck` (or
   note that it should be run if `node_modules` isn't installed
   yet).
4. **Update docs.** If the change touches structure, scripts, env
   vars, conventions, or hard rules → update `README.md`,
   `AGENTS.md`, **and** this `CLAUDE.md` in the same response. See
   "Documentation sync".
5. **Update `.env.example`.** Any new env var must be added there
   too — the variable name itself must be self-explanatory, since no
   comment is allowed to describe it.
6. **Don't expand scope.** If the user asks for the channel-creation
   flow, don't also add attribute families "while you're at it".
7. **Surface assumptions.** If the user's request is ambiguous (e.g.
   "set up the shop") ask one clarifying question before generating
   code.

## Suggestion-specific notes

### Cross-spec runtime state

Created entity names and the recorded base price are persisted between
specs by `utils/runtimeState.ts`. Producers call `writeRuntimeState`,
consumers call `readRuntimeState` — never read or write JSON files in
`.state/runtime/` directly.

| Runtime key           | Producer (admin spec)                              | Consumers                                                                |
| --------------------- | -------------------------------------------------- | ------------------------------------------------------------------------ |
| `suggestion-category` | `04-catalog/categories/01-create-category.spec.ts` | `04-catalog/products/01-create-product.spec.ts`, `02-category-shown` |
| `suggestion-product`  | `04-catalog/products/01-create-product.spec.ts`    | every shop spec                                                          |

When you add a new admin → shop dependency:

1. Producer admin spec persists with `writeRuntimeState("<key>", value)`.
2. Consumer shop spec (or downstream admin spec) reads with
   `readRuntimeState<Shape>("<key>")`.
3. The helper rejects keys that aren't `[a-z0-9-]+`. Use feature-scoped
   kebab-case (`suggestion-product`).
4. The producer must ship before the consumer in alphabetical full-path
   order — that's why category creation lives at `04-catalog/categories`
   (alphabetically before `04-catalog/products` so its name is available
   when the product → category attachment runs).

### Suggestion DOM contract

The shop searchbar POM (`pages/shop/SuggestionSearchbarPage.ts`)
targets a stable subset of the Suggestion module's published Blade
templates. If the storefront markup changes, fix the selectors here in
one place — never push selectors into specs:

| Concept                     | Selector                                                |
| --------------------------- | ------------------------------------------------------- |
| Header search input         | `getByPlaceholder("Search products here").first()`      |
| Suggestion dropdown root    | `#suggest`                                              |
| Result row                  | `#suggest a:has(div.min-w-0)`                           |
| Highlighted matched substr. | `…row… p span.font-semibold` (set by `highlightedName`) |
| Category line               | `…row… p.text-zinc-500`                                 |
| "Show all results" link     | `#suggest a:not(:has(div.min-w-0))` (last)              |

### Bagisto's hidden-input + checkbox toggle pattern

Bagisto renders boolean toggles as **two** form inputs sharing the same
`name` (a hidden `value="0"` input plus the actual checkbox), so any
`input[name="…"]` locator hits both and trips Playwright strict mode.
Always scope to the checkbox:

```ts
this.page.locator(
    'input[type="checkbox"][name="suggestion[suggestion][general][status]"]',
);
```

Same fix applies on the category and product create pages
(`name="status"`).

### Bagisto's `<v-field>` (VeeValidate) required inputs

Required text inputs are wrapped in `<v-field rules="required">` with
the inner `<input v-bind="field">`. On forms that just mounted (e.g.
the category create page after `createButton().click()`),
Playwright's `.fill()` can race VeeValidate's hydration: the DOM value
lands, but the validation tracker stays empty, and submit fails with
"name is required". Use keyboard input + Tab instead:

```ts
private async typeIntoVField(input: Locator, value: string): Promise<void> {
    await input.click();
    await input.fill("");
    await input.pressSequentially(value, { delay: 30 });
    await input.press("Tab");
}
```

Per-character `input` events plus a final `blur` (Tab) match what a
human would do, and VeeValidate commits the value reliably. Apply this
to required text fields on freshly-navigated forms; `.fill()` remains
fine for forms reached via a redirect (the product edit page after the
create modal save) where Vue has already hydrated.

### Bagisto's icon-checkbox label pattern

Filterable-attribute checkboxes (and similar boolean fields) render
**two** labels pointing at the same `for=` — an icon label and a
visible text label. Both forward clicks to the same checkbox, but
selecting `label[for="…"]` matches both. Scope to the icon variant:

```ts
this.page.locator(`label.icon-uncheckbox[for="${name}"]`);
```

### Why the legacy "status off" verification was dropped

The legacy suite had a "switch the module off, verify the dropdown is
gone" pair of tests. Reproducing this under the skeleton's three-project
pipeline (`admin-setup → admin → shop`) doesn't fit cleanly: every shop
spec runs after **all** admin specs, so an "enable → verify enabled →
disable → verify disabled" round-trip can't be expressed as alternating
admin/shop specs.

If you need this verification, add a single combined admin spec that
toggles the module off and verifies the storefront in the same admin
context (admin sessions are valid on the storefront). Don't call
`page.context().clearCookies()` to simulate an anonymous shopper —
that's banned by Hard Rule #7's spirit and breaks subsequent admin
tests that share the run.

### Why the legacy catalog-rule + discounted-price verification was dropped

The legacy suite had a "create a catalog rule, then verify the PDP
final price reflects the discount" test. That flow reaches the PDP via
the suggestion dropdown, but the suggestion dropdown itself never
renders prices — only image, name (highlighted), and optional category
line. The PDP-price assertion is a *catalog/marketing* concern, not a
Suggestion concern; it belongs in the suite for whichever module owns
catalog rules. Don't reintroduce it here unless the Suggestion module
itself ever starts displaying prices in the dropdown.

### Why we didn't replicate the old `tests/utils/myfunction.ts`

The legacy suite kept business logic in `utils/` — product creation,
category creation, catalog-rule creation, etc. — as free functions
that called the page directly. The skeleton's hard rule is "selectors
live inside page objects, never in specs (and never in `utils/`)". The
new suite moves every legacy `utils/` helper into a page object and
invokes it from the spec. `utils/` is now strictly pure helpers
(`env`, `paths`, `bagistoVersion`, `runtimeState`).

## Documentation sync (mandatory)

`README.md`, `AGENTS.md`, and `CLAUDE.md` overlap on purpose so each
audience gets a complete view from one entry point. Whenever you
change:

- The directory layout
- `package.json` scripts or dependencies
- Environment variables (add / rename / change default)
- Conventions (imports, naming, comments, fixtures, page objects)
- The list of test areas or what the suite does
- Hard rules

…you **must** update all three files in the same change. Keep
wording consistent across files so reviewers don't see drift. When a
section is genuinely audience-specific (e.g. "Quick start for humans"
vs "Workflow expectations for Claude") it can live in only one
file — but anything factual about the suite belongs in all three.

## Validation checklist (before marking work complete)

1. **Zero comments anywhere** in any file you touched. Grep for
   `//`, `/*`, and `#` in `.env*` and remove them all. This is the
   first thing to check, every time.
2. `npm run format` — Prettier auto-fixes any drift on touched files.
3. `npm run format:check` — succeeds with no diff.
4. `npm run typecheck` — no TS errors.
5. `npm test` — all tests pass against a real `BAGISTO_BASE_URL` (or
   document why skipped).
6. `README.md`, `AGENTS.md`, `CLAUDE.md` updated if behavior or
   structure changed.
7. `.env.example` updated if env vars changed.
8. No new top-level npm dependencies in `package.json`.
9. No imports reaching outside `e2e-pw/`.
10. No `process.env` reads outside `utils/env.ts`.
11. No hardcoded URLs anywhere except `.env.example`.
