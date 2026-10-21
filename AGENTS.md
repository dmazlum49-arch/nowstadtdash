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
| `resources/lang/{de,en}` | `lang/` at the root |
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

## Sandbox-only override: HTTPS behind the preview proxy

The preview proxy terminates TLS and forwards plain HTTP, and it sends neither `HTTPS`
nor `X-Forwarded-Proto` — so `TrustProxies` cannot help and Laravel emitted `http://`
asset and redirect URLs that the browser blocked as mixed content. `AppServiceProvider::boot()`
therefore calls `URL::forceScheme('https')` **only** when `BASE44_PREVIEW_MODE === '1'`
(the platform passes the flag into the container; it is also listed bare under the `app`
service's `environment:`). With the flag unset or any other value the app keeps its
original URL behaviour, so nothing changes in production.

## Running the sandbox environment

```bash
docker compose -f docker-compose.base44.yml up -d --build
docker compose -f docker-compose.base44.yml ps
docker compose -f docker-compose.base44.yml logs -f app
```

- `app` — PHP 8.2 CLI (`Dockerfile.base44`), repo bind-mounted at `/var/www/html`, runs
  `composer install` at container start and serves `php artisan serve` on **host port 3000**
  (the preview entry point).
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
# / -> 302 (redirects to /login); the rest -> 200
```

`/` is `Route::redirect('/', 'login')`. The healthcheck probes `/login` directly rather than
following that redirect with `-L`: with `URL::forceScheme('https')` active the redirect's
`Location` is `https://…`, which curl cannot speak to the plain-HTTP dev server. Probing
`/login` still only goes green when the Blade view really renders (a missing view returns
500). Anything the login page pulls from `assets/admin/...` should answer 200 as well.

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
