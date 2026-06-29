# AGENTS.md — Cross-Agent Instructions for `e2e-pw/`

Scope: this file applies **only inside this `e2e-pw/` directory**. The
host module repository (and the wider Bagisto repository, if any) has
its own `AGENTS.md` — that one does not apply here.

## What this folder is

The Playwright suite for the Bagisto **Suggestion** module, built on
the shared `bagisto-e2e-pw-skeleton`. It runs against a live Bagisto
install pointed at via `BAGISTO_BASE_URL` with the Suggestion module
installed and registered.

The folder still ships with the skeleton's three seed specs (which
prove the install is reachable end-to-end):

- `tests/admin/01-authentication/01-auth.setup.ts` — logs in once and
  saves storage state to `.state/admin-auth.json`.
- `tests/admin/02-dashboard/01-dashboard.spec.ts` — loads
  `/admin/dashboard` using the saved session.
- `tests/shop/homepage.spec.ts` — verifies the storefront is reachable.

On top of those seeds, the suite drives a sequential **admin sets up →
shop verifies** flow against the suggestion search dropdown. The
Suggestion-specific specs are listed in "Repository map" below.

**The folder is intentionally self-contained** — it must remain
runnable after being copied anywhere on disk.

## Hard rules

1. **No comments. Ever.** This project bans comments in every file —
   `.ts`, `.env`, JSON, everything. No `//`, no `/* */`, no JSDoc, no
   `#` in `.env*` files. Code must be self-explanatory through clear
   naming, small functions, and intent-revealing structure. If a
   comment feels necessary, rename the variable, extract a helper, or
   split the function until the code reads on its own. When editing or
   adding a file, **strip every comment** before saving — even if you
   wrote them yourself moments ago.
2. **No paths reaching outside `e2e-pw/`.** Never
   `import`/`require` from `../`, never resolve files from the host
   module's source, never read `.env` files outside this folder. The
   folder must keep working after being moved anywhere on disk.
3. **No new top-level dependencies without approval.** `package.json`
   is intentionally minimal: `@playwright/test`, `@types/node`,
   `dotenv`, `prettier`, `typescript`. Adding anything else needs an
   explicit ask.
4. **Never read `process.env` outside `utils/env.ts`.** All
   configuration flows through the validated `env` object so there is
   exactly one place to add or rename variables.
5. **Never hardcode the domain.** The target host always comes from
   `BAGISTO_BASE_URL` via `env.baseUrl`. No string literals like
   `"http://bagisto.test"` anywhere except `.env.example`.
6. **Never commit `.env`.** Only `.env.example` is tracked. `.env` is
   gitignored — it carries per-developer host and credentials.
7. **Never delete or rewrite the seed specs without explicit
   approval.** These three files are the canaries that prove the
   install is reachable and the auth pipeline works:
    - `tests/admin/01-authentication/01-auth.setup.ts`
    - `tests/admin/02-dashboard/01-dashboard.spec.ts`
    - `tests/shop/homepage.spec.ts`

    The matching page objects (`pages/admin/LoginPage.ts`,
    `pages/admin/DashboardPage.ts`, `pages/shop/HomePage.ts`) are
    protected for the same reason. Add new specs alongside them, not
    in place of them.

8. **Keep `README.md`, `AGENTS.md`, and `CLAUDE.md` in sync.**
   Whenever the structure, scripts, env vars, or conventions change,
   update all three files in the same change.

## Repository map (`e2e-pw/`)

```
e2e-pw/
├── data/
│   ├── catalog/categories/1/basic.json
│   ├── catalog/products/1/simple.json
│   └── configuration/suggestion/enabled.json
├── fixtures/
│   └── test.ts                                       Custom test fixture; specs always import from here
├── pages/
│   ├── BasePage.ts                                   Abstract POM parent — visit(), dataPath()
│   ├── admin/
│   │   ├── LoginPage.ts                              [seed] Used only by 01-auth.setup.ts
│   │   ├── DashboardPage.ts                          [seed]
│   │   ├── catalog/categories/CategoryCreatePage.ts
│   │   ├── catalog/products/ProductCreatePage.ts
│   │   └── configuration/suggestion/SuggestionConfigPage.ts
│   └── shop/
│       ├── HomePage.ts                               [seed]
│       └── SuggestionSearchbarPage.ts
├── tests/
│   ├── admin/
│   │   ├── 01-authentication/01-auth.setup.ts        [seed] Logs in once, saves .state/admin-auth.json
│   │   ├── 02-dashboard/01-dashboard.spec.ts         [seed] Dashboard smoke test
│   │   ├── 03-configuration/suggestion/01-enable-suggestion.spec.ts
│   │   ├── 04-catalog/categories/01-create-category.spec.ts
│   │   └── 04-catalog/products/01-create-product.spec.ts
│   └── shop/
│       ├── homepage.spec.ts                          [seed] Storefront smoke test
│       └── suggestion/
│           ├── 01-product-visible.spec.ts
│           └── 02-category-shown.spec.ts
├── utils/
│   ├── env.ts                                        Single source of truth for process.env
│   ├── paths.ts                                      E2E_ROOT_PATH, DATA_PATH, ADMIN_AUTH_STATE_PATH
│   ├── bagistoVersion.ts                             isBagistoVersionAtLeast() for branch gating
│   └── runtimeState.ts                               writeRuntimeState / readRuntimeState for cross-spec data
├── .env.example
├── .gitignore
├── .prettierrc.json
├── .prettierignore
├── playwright.config.ts
├── tsconfig.json
├── package.json
├── README.md
├── AGENTS.md
└── CLAUDE.md
```

## Environment variables

Defined in `utils/env.ts`. Every variable must also exist in
`.env.example`.

| Variable                 | Purpose                                               | Default             |
| ------------------------ | ----------------------------------------------------- | ------------------- |
| `BAGISTO_BASE_URL`       | Storefront / admin host (required)                    | —                   |
| `BAGISTO_VERSION`        | Used by `isBagistoVersionAtLeast()` for branch gating | `2.4`               |
| `BAGISTO_ADMIN_EMAIL`    | Admin login email                                     | `admin@example.com` |
| `BAGISTO_ADMIN_PASSWORD` | Admin login password                                  | `admin123`          |
| `ACTION_TIMEOUT`         | Default Playwright action timeout (ms)                | `10000`             |
| `NAVIGATION_TIMEOUT`     | Default Playwright navigation timeout (ms)            | `15000`             |
| `HEADED`                 | Run with a visible browser                            | `false`             |

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

Specs should never call `page.locator(...)` directly — they call
methods on a page object that hides the selectors. This keeps specs
readable and makes selector changes a one-file fix.

### Project pipeline (`playwright.config.ts`)

| Project       | testMatch             | Dependencies  | Storage state            |
| ------------- | --------------------- | ------------- | ------------------------ |
| `admin-setup` | `admin/**/*.setup.ts` | —             | none                     |
| `admin`       | `admin/**/*.spec.ts`  | `admin-setup` | `.state/admin-auth.json` |
| `shop`        | `shop/**/*.spec.ts`   | `admin`       | none                     |

`shop` depends on `admin` so admin specs always run first — the
storefront tests follow the human-like flow (set up the store as an
admin, then browse it as a customer).

The boilerplate ships with all three projects populated:

- `admin-setup` runs `tests/admin/01-authentication/01-auth.setup.ts`,
  which logs in via `AdminLoginPage`, waits for the dashboard, and
  saves storage state to `ADMIN_AUTH_STATE_PATH` from `utils/paths.ts`
  using `ensureStateDir()`.
- `admin` runs every `admin/**/*.spec.ts` with the saved storage
  state. The seed spec is `tests/admin/02-dashboard/01-dashboard.spec.ts`,
  which proves the saved session lands on `/admin/dashboard`.
- `shop` runs `shop/**/*.spec.ts` anonymously. The seed spec is
  `tests/shop/homepage.spec.ts`.

Module-specific admin specs go under `tests/admin/<NN>-<area>/...`
and reuse the same auth setup — they should never log in themselves.

### Test area split (mandatory)

The suite is organised around two top-level areas:

- **`tests/admin/`** — drives `/admin/...`. Page objects live in
  `pages/admin/`.
- **`tests/shop/`** — exercises the storefront. Page objects live in
  `pages/shop/`.

Anything genuinely shared lives directly under `pages/` (e.g.
`BasePage.ts`).

### URL-mirroring sub-folders (mandatory)

Sub-folders mirror Bagisto URL paths:

| Bagisto URL prefix     | Tests path               | Page objects path        |
| ---------------------- | ------------------------ | ------------------------ |
| `/admin/settings/...`  | `tests/admin/settings/`  | `pages/admin/settings/`  |
| `/admin/catalog/...`   | `tests/admin/catalog/`   | `pages/admin/catalog/`   |
| `/admin/customers/...` | `tests/admin/customers/` | `pages/admin/customers/` |
| `/admin/sales/...`     | `tests/admin/sales/`     | `pages/admin/sales/`     |
| `/admin/marketing/...` | `tests/admin/marketing/` | `pages/admin/marketing/` |

### Entity folder structure (mandatory)

Every entity gets its own folder named after the lowercase plural URL
slug — e.g. `tests/admin/settings/channels/`. Spec files use a
two-digit numeric prefix and an action verb:

| Spec file            | URL                                  |
| -------------------- | ------------------------------------ |
| `01-listing.spec.ts` | `GET    /admin/.../<slug>`           |
| `02-create.spec.ts`  | `POST   /admin/.../<slug>/create`    |
| `03-edit.spec.ts`    | `PUT    /admin/.../<slug>/edit/{id}` |
| `04-delete.spec.ts`  | `DELETE /admin/.../<slug>/edit/{id}` |

Page objects are siblings of the tests folder under `pages/`. Naming:

| Resource         | Listing                 | Create                      | Edit / View               |
| ---------------- | ----------------------- | --------------------------- | ------------------------- |
| Channel          | `ChannelsPage`          | `ChannelCreatePage`         | `ChannelEditPage`         |
| Attribute        | `AttributesPage`        | `AttributeCreatePage`       | `AttributeEditPage`       |
| Attribute family | `AttributeFamiliesPage` | `AttributeFamilyCreatePage` | `AttributeFamilyEditPage` |
| Product          | `ProductsPage`          | `ProductCreatePage`         | `ProductEditPage`         |

Plural for listing, singular + action suffix for entity-specific.
Never plain singular like `ChannelPage` (ambiguous).

### Sequential execution (mandatory)

`playwright.config.ts` ships with `fullyParallel: false` and
`workers: 1`. Tests are ordered alphabetically within a project — use
numeric prefixes on file names (`01-listing.spec.ts`,
`02-create.spec.ts`, …) when order matters. **Do not rely on
`testMatch` array order** to control sequence; Playwright sorts
alphabetically.

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

### Page Object Model

- Each screen → one class in `pages/` extending `BasePage`.
- Methods are intent-revealing: `createChannel(data)`, not
  `clickChannelSubmitButton()`.
- Selectors live inside the page object, never in specs.
- Constructor takes a `Page` and passes it up: `super(page)`.
- `this.dataPath(relativePath)` resolves relative paths under `data/`
  to absolute paths — use it in upload methods so tests never see
  filesystem paths.

### Member ordering inside a page object class

| Order | Section                                                           |
| ----- | ----------------------------------------------------------------- |
| 1     | Constructor                                                       |
| 2     | Navigation                                                        |
| 3     | Locators by UI section (top-to-bottom matching the visual layout) |
| 4     | Page-level locators                                               |
| 5     | Public actions                                                    |
| 6     | Private helpers                                                   |

### Fixtures

- Add to `fixtures/test.ts` whenever multiple specs need the same
  setup.
- Each fixture is `async (deps, use) => { ... }` — set up, call
  `await use(value)`, then tear down after `use` returns.
- The boilerplate exports two fixtures: an `env` fixture wrapping the
  validated env, and an overridden `page` fixture that injects a
  global CSS rule hiding Bagisto's `.phpdebugbar` dev overlay (it
  intercepts clicks near the page footer on non-prod installs).

### Test data and images (mandatory)

Test data and fixture images are co-located under `data/` and mirror
the Bagisto URL path:

```
data/<resource-group>/<entity>/<id>/<meaningful-name>.json
```

with images alongside in an `images/` subfolder. JSON references
fixture images by **relative path from `data/`**, e.g.
`"logo": "settings/channels/1/images/logo.png"`. The page object
resolves to absolute paths via `this.dataPath(relativePath)`. Tests
never call `path.join` or know about filesystem layout.

### Naming

| Kind         | Convention                      | Example                            |
| ------------ | ------------------------------- | ---------------------------------- |
| Spec files   | `NN-kebab-case.spec.ts`         | `01-listing.spec.ts`               |
| Page objects | `PascalCase.ts`                 | `LoginPage.ts`, `DashboardPage.ts` |
| Utilities    | `camelCase.ts`                  | `randomSku.ts`                     |
| Test areas   | `tests/admin/` or `tests/shop/` | `tests/admin/channels.spec.ts`     |
| Page areas   | `pages/admin/` or `pages/shop/` | `pages/admin/ChannelsPage.ts`      |

`describe` blocks and `setup`/`test` names mirror the file path as a
breadcrumb using `" — "` (area separator) and `" › "` (depth
separator):

```
"Area — path › segment › ... › action"
```

The numeric file prefix (`01-`, `02-`) is **not** part of the
breadcrumb. Use the bare action verb only (`listing`, not
`01-listing`).

### TypeScript

- `strict`, `noUnusedLocals`, `noUnusedParameters` are on. Don't relax them.
- `npm run typecheck` is the source of truth for type validity — run
  it before claiming work is done.
- Avoid `any`; use `unknown` + narrowing if a type is genuinely
  unknown.

### Formatting (Prettier)

- Run `npm run format` before marking work complete.
- `npm run format:check` is the CI-style check that exits non-zero on
  drift.
- Do not add per-file or per-directory Prettier overrides without
  approval. The single config in `.prettierrc.json` applies to
  everything.

## Validation checklist (before marking work complete)

1. **Zero comments anywhere** in any file you touched. Grep for `//`,
   `/*`, and `#` in `.env*` and remove them all.
2. `npm run format` — Prettier auto-fixes any drift on touched files.
3. `npm run format:check` — succeeds with no diff.
4. `npm run typecheck` — no TS errors.
5. `npm test` — all tests pass against a real `BAGISTO_BASE_URL` (or
   document why skipped).
6. `README.md`, `AGENTS.md`, `CLAUDE.md` updated if behaviour or
   structure changed.
7. `.env.example` updated if env vars changed.
8. No new top-level npm dependencies in `package.json`.
9. No imports reaching outside `e2e-pw/`.
10. No `process.env` reads outside `utils/env.ts`.
11. No hardcoded URLs anywhere except `.env.example`.

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

Rules:

- Only persist values that other specs genuinely need. Don't dump the
  whole entity payload.
- Use kebab-case keys scoped by feature (`suggestion-product`, not
  `product`). The helper rejects keys that aren't `[a-z0-9-]+`.
- The store lives under `.state/runtime/`, which is gitignored and
  wiped between local runs. CI starts clean.

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

### What is intentionally *not* covered

- **Pricing** (catalog rules, customer-group prices, taxes). The
  Suggestion dropdown only renders image + name + (optional) category
  names — it never shows prices. Verifying that catalog-rule discounts
  flow into the PDP is a *catalog/marketing* concern; it lives in
  those modules' own e2e suites, not here.
- A "module disabled" round-trip (admin toggles status off, shop
  verifies dropdown is gone). The skeleton's project pipeline runs
  admin specs to completion before any shop spec, so an "enable →
  verify enabled → disable → verify disabled" sequence isn't a clean
  fit. If you need this, either add a single combined admin spec that
  toggles off and verifies the storefront from the same admin context,
  or open a discussion before doing anything that breaks the
  admin-then-shop split.
- Image upload during product creation (the source `simple.json` has
  no `images` field). The original legacy suite skipped this too. If
  you add product images, follow the skeleton's data-and-images
  co-location rule.

### Bagisto's hidden-input + checkbox toggle pattern

Bagisto renders boolean toggles as **two** form inputs sharing the same
`name`:

```html
<input value="0" type="hidden" name="suggestion[suggestion][general][status]"/>
<input type="checkbox" name="suggestion[suggestion][general][status]"
       id="suggestion[suggestion][general][status]" class="peer sr-only"/>
```

The hidden input ensures unchecked checkboxes still submit a `0` value.
Selecting `input[name="…"]` triggers a Playwright strict-mode
violation. **Always scope toggle locators to the checkbox**:

```ts
this.page.locator(
    'input[type="checkbox"][name="suggestion[suggestion][general][status]"]',
);
```

The same pattern applies to admin form toggles like `name="status"` on
the category and product create pages.

### Bagisto's `<v-field>` (VeeValidate) inputs need keyboard input, not `.fill()`

Required text inputs in admin forms are wrapped in
`<v-field rules="required" v-slot="{ field }"> <input v-bind="field" /> </v-field>`.
The validation tracker only commits values when the input fires real
`input`/`blur` events. Playwright's `.fill()` sets `input.value`
directly and dispatches a synthetic `input` event, but on freshly-mounted
forms (e.g. category create after a navigation), the listener can race
the form's hydration — the value lands in the DOM but VeeValidate's
tracked value stays empty, and the server rejects submit with
"name is required".

The defensive pattern (used by `CategoryCreatePage.typeIntoVField`):

```ts
await input.click();
await input.fill("");
await input.pressSequentially(value, { delay: 30 });
await input.press("Tab");
```

Each character fires a real `keydown`/`input`/`keyup`. The trailing
Tab forces a `blur`, which is when VeeValidate's `lazy` mode commits.
Use this pattern for any required field on a form reached via a fresh
navigation. Existing tests on already-mounted forms (suggestion config,
product *edit* after the create-redirect) work fine with `.fill()`.

### Bagisto's icon-checkbox label pattern

Filterable-attribute checkboxes (and many other boolean fields in the
admin) render with **two labels** pointing at the same `for=`: an icon
label (`label.icon-uncheckbox[for="…"]`) and a visible text label.
Both will trigger the underlying checkbox when clicked. Scope the
locator to the icon variant to avoid strict-mode violations:

```ts
this.page.locator(`label.icon-uncheckbox[for="${name}"]`);
```

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

…you **must** update all three files in the same change. When a
section is genuinely audience-specific (e.g. "Quick start for humans"
in `README.md` vs "Workflow expectations for Claude" in `CLAUDE.md`)
it can live in only one file — but anything factual about the suite
belongs in all three.
