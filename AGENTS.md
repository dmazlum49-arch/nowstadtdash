# Nowstadt dashboard — sandbox notes

Laravel 10 app (PHP 8.2). This file records only what is not obvious from the manifests.

## Application code: imported from `main`, but `resources/` is still missing

The repository originally arrived as a flat, structure-less upload. On 2026-10-21 the
application code was pulled from the GitHub repo `dmazlum49-arch/nowstadtdash` (branch
`main`, while it was public) and re-assembled into the standard Laravel layout:

| Now in the repo | Came from (flat upload) |
| --- | --- |
| `app/{Console,Exceptions,Helpers,Http,Mail,Models,Providers,Services}` | the same names at the repository root |
| `database/migrations/` (114 files) | the `YYYY_MM_DD_NNNNNN_*.php` files at the root |
| `database/{factories,seeders}` | `factories/`, `seeders/` at the root |
| `public/{css,js,fonts,images,default,jquery,jquery-validation,datatables.net*,metismenu,node-waves,simplebar,sweetalert2,toastr,fancybox}` | same names at the root (`default/fcm_config.json` confirms these belong to `public/`) |
| `public/{index.php,favicon.ico,robots.txt}` | same files at the root |
| `public/tinymce/{tinymce.min.js,jquery.tinymce.min.js,skins,themes,icons,plugins/*}` | `tinymce.min.js`, `jquery.tinymce.min.js`, `skins/`, `themes/`, `icons/` and the TinyMCE plugin folders (`plugin.min.js` marker) at the root |

Because the upload was flattened, the `public/` asset paths above are a best-effort
reconstruction (TinyMCE is assumed at `public/tinymce/`). If a view references an asset
path that 404s, compare it with how the file was uploaded and move the folder — the
inventory is complete, only the nesting is a guess.

**Still missing from `main` (verified: 0 `*.blade.php` files in the whole repository):**

| Missing | Effect |
| --- | --- |
| `resources/` — all Blade views (`resources/views/…`, e.g. `user.guest.login`), plus `resources/css/app.css` and `resources/js/app.js` that `vite.config.js` declares as entry points | Every page returns HTTP 500 `View [user.guest.login] not found`. The app boots, routes and hits the database correctly — only rendering fails. |
| `.env.example` | No record of the expected environment variables; the list below was inferred from `env()` calls in `app/` + `config/`. |

The `app` service is therefore reported **unhealthy**: the healthcheck probes `/` for real
(which is a 500 until the views exist) instead of a placeholder. It goes green on its own
once `resources/views` is in place.

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

Useful checks:

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:3000/phpinfo   # 200 = framework serves
curl -s http://localhost:3000/ | head -5                                 # 500 until views exist
docker compose -f docker-compose.base44.yml exec -T app php artisan route:list | head
```

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
- The Vite asset pipeline is not wired into compose yet. In the sandbox the browser reaches
  the app through the port-3000 origin, so once `resources/` exists either `npm run build`
  or a Vite dev server exposed through that origin is needed.
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
