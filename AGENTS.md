# Nowstadt dashboard — sandbox notes

Laravel 10 app (PHP 8.2). This file records only what is not obvious from the manifests.

## The repo arrived as a flat upload — the nesting was reconstructed

The repository was uploaded flat twice: first the application code, then (2026-10-21)
`resources/` and `public/`'s assets, each time with the directory nesting stripped.
Both times the files were re-assembled into the standard Laravel layout:

| Now in the repo | Came from (flat upload) |
| --- | --- |
| `app/{Console,Exceptions,Helpers,Http,Mail,Models,Providers,Services}` | the same names at the repository root |
| `database/migrations/` (114 files) | the `YYYY_MM_DD_NNNNNN_*.php` files at the root |
| `database/{factories,seeders}` | `factories/`, `seeders/` at the root |
| `resources/views/{admin,user,layouts,emails}` + `resources/views/welcome.blade.php` | the same names at the repository root |
| `resources/lang/{de,en}` | `lang/` at the root — but the upload landed them at `resources/lang/lang/{de,en}`, so **every `__()` key rendered as its raw key** ("admin.dashboard" as the page heading). Moved up to `resources/lang/{en,de}` on 2026-10-21; `app()->langPath()` is `/var/www/html/resources/lang`, so that is where the loader looks. |
| `resources/{css,js}` | `css/`, `js/` at the root (Vite entry points) |
| `public/assets/admin/{css,js,images,fonts}` | `public/{css,js,images,fonts}` |
| `public/assets/admin/libs/{jquery,jquery-validation,metismenu,simplebar,node-waves,toastr,sweetalert2,fancybox,tinymce,datatables.net*}` | the same names directly under `public/` |
| `public/{index.php,favicon.ico,robots.txt,default/}` | same files at the root |

The nesting is a reconstruction, not a copy of the original tree: the layouts and CSS
reference everything as `assets/admin/...` (`url('assets/admin/css/app.css')`,
`../fonts/boxicons.woff2`, `../images/...`), which is what drove the mapping. If a view
references a path that 404s, compare it with the upload and move the folder.

**Files the upload never included** (verified absent — nothing else in the app is missing):

| Missing file | Effect |
| --- | --- |
| `public/assets/admin/libs/bootstrap/js/bootstrap.bundle.min.js` | Bootstrap's JS never loads (the CSS does). Dropdowns/collapse/modals in the admin and user panels will not open. |
| `public/default/no_image.png` | `checkFileExist()` falls back to this placeholder, so every missing image (e.g. the login logo) renders as a broken image. |
| `public/assets/admin/images/bg-auth-overlay.png`, `public/assets/admin/images/crypto/bg-ico-hero.jpg` | Referenced by `assets/admin/css/app.css`; cosmetic only. |
| `.env.example` | No record of the expected environment variables; the list below was inferred from `env()` calls in `app/` + `config/`. |

## One sandbox-gated override: URL generation behind the preview proxy

The preview proxy terminates TLS and forwards the request to the container over plain HTTP
with the sandbox-internal host (`<port>-<sandbox id>.e2b.app`), which the browser cannot
reach and which the preview page's own origin does not match. Laravel builds every
`route()` / `asset()` URL — and therefore every redirect, including the one after a login
POST — from that host and scheme, so the browser blocked them (wrong origin / mixed
content) and the target page never opened: the login seemed to do nothing and panel links
appeared dead. `app/Providers/AppServiceProvider.php` therefore calls
`URL::forceRootUrl(config('app.url'))` and `URL::forceScheme('https')` **only** when
`BASE44_PREVIEW_MODE === '1'`; with the flag unset (or any other value) the app generates
URLs exactly as before. `APP_URL` in `.env.base44-defaults` is the public preview origin,
so the generated URLs stay inside the preview's own origin. This is the only backend
override — routes, controllers, middleware, authentication, models and database logic are
untouched.

The preview's entry point `/` is redirected to the admin panel login (`/panel/login`) by
`server-preview.php` — see the last section.

## Running the sandbox environment

```bash
docker compose -f docker-compose.base44.yml up -d --build
docker compose -f docker-compose.base44.yml ps
docker compose -f docker-compose.base44.yml logs -f app
```

- `app` — PHP 8.2 CLI (`Dockerfile.base44`), repo bind-mounted at `/var/www/html`, runs
  `composer install` at container start and serves `server-preview.php` on **host port 3000**
  (the preview entry point; it redirects `/` to `/panel/login` and hands every other path to
  Laravel).
- `mysql` — MySQL 8, database `nowstadtdash`, user `nowstadt` / `nowstadt`.
- `redis` — Redis 7 (`REDIS_CLIENT` defaults to `phpredis`, which the image provides).

Migrations were applied on 2026-10-21 (`php artisan migrate --force`, 114 migrations +
the two permission seeders). The `mysql-data` volume keeps that schema; re-running
`migrate` is a no-op.

Environment variables: `.env.base44-defaults` (committed dev placeholders) is loaded first
and `/run/base44/app.env` (Base44 dashboard secrets) last, so dashboard values always win.
Local infrastructure credentials are inline in the compose `environment:` block on purpose
— nothing there can shadow a dashboard value.

### Verifying the app actually works

The public entry points render without credentials:

```bash
for r in / /login /sign-up /forgot-password /panel/login; do
  printf '%-18s ' "$r"; curl -s -o /dev/null -w '%{http_code}\n' "http://localhost:3000$r"
done
# / -> 302 (to /panel/login, the preview's entry point); the rest -> 200
```

The healthcheck probes `/panel/login` and requires its form (`login-form`) to be present, so
a blank or error page fails. Every public entry point renders without credentials (`/login`,
`/sign-up`, `/forgot-password`, `/panel/login` all answer 200; a missing Blade view would be
a 500). Anything a panel page pulls from `assets/admin/...` should answer 200 as well.

The whole panel is reachable end to end with the seeded admin — sign in at `/panel/login`
(username `admin` or `admin@gmail.com`, password `123456`) and `/panel` renders the
dashboard. A `curl` login is enough to check it: fetch `/panel/login`, POST it with the
`_token` cookie and the `username`/`password` fields, then GET `/panel` with the cookie jar
(the login form posts `username`, not `email`).

## State the sandbox left behind

- `composer.lock` was generated *by this sandbox* (`composer update`), because the repo
  ships none and Composer 2.10 refuses to resolve any Laravel 10 release ("affected by
  security advisories"). `COMPOSER_NO_BLOCKING=1` is set on the `app` service for the same
  reason — sandbox-only, never carry it into CI or production. If you have the project's own
  lockfile, delete this one and upload yours; it is the authoritative pin.
- `storage/framework/{cache,sessions,views}`, `storage/logs` and `bootstrap/cache` are
  created by the compose command because the repo does not ship them. `.gitignore` keeps
  those runtime files, `vendor/` and `node_modules/` out of git.
- `APP_KEY` in `.env.base44-defaults` is a generated sandbox placeholder. Replace it with
  the project's real key if this workspace must read data encrypted with it.
- The PHP image installs `pdo_mysql, mbstring, zip, gd, bcmath, intl, exif, pcntl, opcache`
  and the `redis` extension. `php-ffmpeg` additionally needs an `ffmpeg` binary at runtime
  (HLS columns exist in the schema, so media conversion is part of the product) — add it to
  `Dockerfile.base44` when that flow is exercised.
- No queue worker / scheduler / websocket service is defined yet: `config/queue.php`
  defaults to `sync`; the Ratchet socket server (`Console/Commands/RunSocket.php`) and the
  scheduled commands (`events:send-reminders`, `business:weekly-summary`) need their own
  compose services to actually run.
- The Vite pipeline is not wired into compose yet. The panel layouts load their CSS/JS from
  `public/assets/admin/...` directly, so the pages render without it; only the `resources/css`
  and `resources/js` entry points declared in `vite.config.js` would need a Vite dev server
  or `npm run build`.
- External services the code expects (real credentials belong in the Base44 dashboard
  secrets, never in the repo — the names were inferred from `env()` usage):
  AWS S3 (`config/constants.php` hardcodes `'upload_type' => 's3'`, so uploads need
  `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_BUCKET` / `AWS_DEFAULT_REGION`) and
  optionally SQS/SES; AWS MediaConvert (the `mediaconvert/callback` webhook); OpenAI
  (`OPENAI_API_KEY`); Stripe (`STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`); Twilio
  (`TWILIO_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_PHONE_NUMBER`, `TWILIO_VERIFY_SERVICE_SID`);
  Firebase Cloud Messaging (`config/constants.php` expects the credentials JSON at
  `public/default/fcm_config.json` — that file *is* present, but it holds no real project
  credentials). `ABLY_KEY` / Pusher keys are only needed if broadcasting is enabled.

## Admin UI redesign + the preview entry point

The admin panel's look is restyled by **`public/assets/admin/css/nowstadt-theme.css`**, a
presentation-only design system (tokens, then shell/sidebar/topbar/cards/tables/forms/
buttons/badges/responsive) loaded after `app.css` / `app-change.css` from
`resources/views/layouts/admin/app.blade.php`. It fixes two defects the old overrides
introduced — the `.mm-active` menu item's `font-size: 200px` and the neon green
`.text-primary` (`#00ff40`), unreadable on white — and replaces the loud gradient stat
icons with soft tinted chips.

Two traps when editing it:

- The stat cards carry Bootstrap's `bg-primary` **utility** on the icon chip, and that
  rule is `!important`. Tint overrides for `.mini-stat-icon` need `!important` *and*
  the `.card.mini-stats-wid` ancestor, or the chips stay solid blue and the coloured
  glyphs on top become unreadable.
- `app-change.css` also uses `!important` on `.mm-active > a` and `.navbar-brand-box`,
  so sidebar overrides have to out-rank it.

### The component layer (sections 16–24 of the same file)

Sections 1–15 are the shell and the everyday surfaces; 16–24 finish the system so every
control the panel renders is on it:

- **16 Bootstrap bridge** — the panel runs **Bootstrap 5.0.1 with Skote's compiled
  palette** (primary `#556ee6`, success `#34c38f`, Poppins). Section 16 re-points the
  `--bs-*` custom properties at the Nowstadt tokens, which catches the few components
  the rest of the file does not restyle individually. Skote hardcodes most colours in
  its compiled CSS, so the bridge is a safety net, not the main mechanism.
- **17 Status labels** — `get_badge_html()` and the DataTables controllers render
  `badge bg-success|bg-danger|bg-primary`, i.e. Bootstrap's **solid utilities**, which
  are `!important`. The soft tints therefore need `!important` too, and they must stay
  scoped to `.badge.bg-*` (a bare `bg-success` override would hit anything else using
  that utility). Green carries every positive state: success, rewards, location.
- **18 Inputs** — `.form-select`, native date/time fields, file uploads
  (`::file-selector-button`, plus the `-webkit-` variant), input groups, validation,
  switches and select2. Two of the create/edit views style the cropper preview box
  inline (`.preview { border: 1px solid red }`); `body .preview` out-ranks that
  inline `<style>` rule.
- **19 Tabs & pills**, **20 modals + SweetAlert2**, **21 toastr notifications**,
  **22 loading & empty states**, **23 DataTables responsive chrome**, **24 mobile**.
- Traps found while building these:
  - The create/edit views ship `.modal-lg { max-width: 1000px !important }` in their
    own `<style>` block, which overflows a phone. Only a *more specific* selector wins
    against a later same-specificity `!important`, hence `.modal-dialog.modal-lg`.
  - toastr keeps its per-type icon in `background-image`; setting that to `none`
    silently removes every notification icon.
  - The base DataTables stylesheet is **not** linked (only the `-bs4` integrations), so
    `div.dataTables_processing` is a plain class rule and can be restyled; the
    responsive plugin's expand control is `td.dtr-control:before`.

Section 15 of the file ("Inner pages") carries the list / create / edit treatment that reaches
every non-dashboard page **without touching their markup**. Admin list pages render a leading
`.text-end` action block before their `.card-title` (verified in all 16 list views), so that
block is lifted to the card's top-right (absolute, with a padding reserve on the title) and
falls back to a stacked layout under 576px. Create/edit `.col-form-label` rows and in-card
`.nav-tabs` are matched to the system as well. The theme reaches the real Blade views, so
signing in and browsing the panel is how you see a CSS edit land.

`resources/views/admin/general/dashboard.blade.php` carries the redesigned overview
(page header + "Platform overview" / "Approvals" / "Catalog" groups); every route and
count variable is unchanged. No markup semantics, routes, controllers, providers,
middleware, models or database code were touched.

The preview serves the **real application**. An earlier sandbox session put an isolated
static mock of the admin shell at `/` (`public/preview/index.html`, all links `href="#"`);
it was removed on 2026-10-21 because it hid the real panel — the preview showed a shell whose
every link was dead, so no page behind it could be opened. The redesign lives in
`nowstadt-theme.css`, which the real views load, so nothing was lost with the mock.

What is left is one preview-only router:

- `server-preview.php` — redirects `/` to `/panel/login` and passes every other path to
  Laravel unchanged. `docker-compose.base44.yml` starts the dev server with it
  (`php -S 0.0.0.0:3000 -t public server-preview.php`). The redirect exists because the
  app's own `/` sends visitors to the **business-user** login (`/login`), where an admin
  account is rejected (`User\GuestController@postLogin` allows only `businessuser` /
  `sub-businessuser`), so the panel would be unreachable from the preview's entry point.
  Laravel never loads this file; delete it and start with `server.php` to drop the redirect
  (the app's own `/` behaviour then applies unchanged).

No route, controller, provider, middleware, model or database code was changed for this.
The sandbox database was seeded on 2026-10-21 with `UserSeeder` (two admins, both
`type=admin`, `status=active`) and `RolePermissionSeeder` (which assigns `Super Admin` to
the first admin; without it the permission-gated pages answer 403). Both seeders are
idempotent enough for a fresh `mysql-data` volume; re-seed only if the accounts are gone.
`db:seed --class=UserSeeder` inserts duplicates if run twice.

## Form controls & micro-interactions layer

The small controls the create/edit forms are built from are styled by
**`public/assets/admin/css/nowstadt-forms.css`**, loaded right after `nowstadt-theme.css` in
`resources/views/layouts/admin/app.blade.php`. It is the last stylesheet the layout links, so
only the views' own `@section('head_css')` `<style>` blocks come later; where those collide
(the post create view re-styles `.form-group label` and `.text-danger`), the selectors here
carry a `body` prefix so the design system still wins.

It covers: field shells (44px control height, softer border, hover + focus ring), labels,
required markers, helper text, validation states, field rhythm and card padding, the language
segmented control, breadcrumbs, primary actions with icon support, and the mobile form layout.
It adds no markup and changes no field name, validation rule, route or submit behaviour.

The only scripted part is **`public/assets/admin/js/nowstadt-forms.js`**, loaded after
`app.js`. It is a progressive enhancement: each `input[type="file"]` keeps its place in the
form (visually hidden but focusable and still submitted) and gets a drag-and-drop card beside
it — dropzone, preview thumbnail, file name and size, replace and remove. Dropping or picking
writes the files back onto that same input and fires the native `change` event, so the page's
own handlers keep working; `Remove` clears `input.value` the same way.

Two traps:

- Inputs the views drive themselves — `.profile_image`, whose `change` event opens the
  cropper modal in every create/edit view — are **skipped** by the script on purpose. Only
  their box is restyled (dashed dropzone in CSS). Enhancing them would leave the cropper's
  `image_base64` / `.show-image` thumbnail stale after a card-side remove.
- The post form builds extra upload rows at runtime (`#add-input`). A `MutationObserver`
  enhances those too, and the script marks each row `.nw-upload-row` so the stylesheet can
  lay the card out next to the page's own "X" remove-row button (otherwise the button
  stretches to the card's full height).

## Language fields are optional (2026-10-21)

Every create/edit view with language tabs (home banner, business/event category, city,
contents) no longer marks its per-language fields as required: the
`<span class="text-danger">*</span>` is gone from those labels, and neither the jQuery
`validate()` rules nor the server rules require them (`.*` => `nullable`). Only the image
field keeps its marker.

One trap this exposed: Laravel's `ConvertEmptyStringsToNull` middleware turns a blank
language input into `null`, while `business_categories.business_category`,
`event_categories.event_category`, `cities.city_name` and `contents.title/content` are
**NOT NULL** columns — the inserts answered 500. Every language insert/update now coerces
with `?? ''`, so a record always keeps one row per language. Skipping the empty row instead
would hide the record: the admin lists filter on `language = 'en'`.
