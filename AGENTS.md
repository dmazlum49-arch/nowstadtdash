# Nowstadt dashboard — sandbox notes

## Current state of this repository: application code is missing

The uploaded repository contains only the Laravel *skeleton* (40 files): `config/`,
`routes/`, `bootstrap/app.php`, `artisan`, `server.php`, `composer.json`,
`package.json`, `vite.config.js`, `tests/`, README. The application layer was never
uploaded:

| Missing | Why it blocks the app |
| --- | --- |
| `app/` | `bootstrap/app.php` binds `App\Http\Kernel`, `App\Console\Kernel`, `App\Exceptions\Handler`; `config/app.php` registers `App\Providers\*`; `routes/*.php` reference `App\Http\Controllers\…`, models and middleware (`is_admin`, `is_businessuser`, `set_locale`). Every one of these classes lives here. |
| `public/` | No `public/index.php`, so there is no web entry point to serve (and no uploaded assets). |
| `resources/` | No Blade views to render and no `resources/css/app.css` / `resources/js/app.js` that `vite.config.js` declares as entry points. |
| `database/` | No migrations or seeders, so no schema can be created. |
| `storage/`, `bootstrap/cache` | Framework runtime directories (the container creates them at start-up). |
| `.env.example` | No record of the environment variables the app expects. |

Observable consequence: `php artisan` cannot even start (`Class "App\Http\Kernel" not
found`), and the same is true for `public/index.php`. **The environment below is ready,
but the preview stays empty until the missing directories are re-uploaded.**

## Running the sandbox environment

```bash
docker compose -f docker-compose.base44.yml up -d --build
docker compose -f docker-compose.base44.yml ps
docker compose -f docker-compose.base44.yml logs -f app
```

- `app` — PHP 8.2 CLI (`Dockerfile.base44`), repo bind-mounted at `/var/www/html`,
  installs Composer dependencies on start-up and serves `php artisan serve` on
  **host port 3000** (the preview entry point).
- `mysql` — MySQL 8, database `nowstadtdash`, user `nowstadt` / `nowstadt`.
- `redis` — Redis 7 (`REDIS_CLIENT` defaults to `phpredis`, which the image provides).

Environment variables: `.env.base44-defaults` (committed dev placeholders) is loaded
first and `/run/base44/app.env` (Base44 dashboard secrets) last, so dashboard values
always win. Local infrastructure credentials are inline in the compose `environment:`
block on purpose — nothing there can shadow a dashboard value.

## State the sandbox left behind

- The `app` service refuses to start while `app/` and `public/index.php` are missing: it
  prints `Application source missing: app/ and public/index.php were never uploaded…` and
  exits. The guard is a guard only — as soon as the directories exist, the next restart
  installs dependencies and starts `php artisan serve` on port 3000 by itself.
- `composer.lock` was generated *by this sandbox* (`composer update`), because the repo
  shipped none and Composer 2.10 refuses to resolve any Laravel 10 release
  ("affected by security advisories"). `COMPOSER_NO_BLOCKING=1` is set on the `app`
  service for the same reason — sandbox-only, never carry it into CI or production.
  If you have the project's own lockfile, delete this one and upload yours; it is the
  authoritative pin.
- `storage/framework/{cache,sessions,views}`, `storage/logs` and `bootstrap/cache` are
  created by the compose command because the repo does not ship them. `.gitignore` keeps
  those runtime files, `vendor/` and `node_modules/` out of git.
- `APP_KEY` in `.env.base44-defaults` is a generated sandbox placeholder. Replace it with
  the project's real key if this workspace must read data encrypted with it.
- The PHP image already installs `pdo_mysql, mbstring, zip, gd, bcmath, intl, exif, pcntl,
  opcache` and the `redis` extension (`php-ffmpeg` additionally needs an `ffmpeg` binary at
  runtime, `simplesoftwareio/simple-qrcode` needs GD — both can be added to
  `Dockerfile.base44` when the code is back).
- No queue worker / scheduler / websocket service is defined yet: `config/queue.php`
  defaults to `sync`, `sockets` (Ratchet) and the scheduled commands
  (`events:send-reminders`, `business:weekly-summary`) can only be wired up once the
  console commands exist.
- The Vite asset pipeline is not wired into compose yet. `resources/` does not exist, and
  in the sandbox the browser reaches the app through the port-3000 origin, so either
  `npm run build` or a Vite dev server exposed through that origin will be needed.
- External services referenced by the remaining code (real credentials belong in your
  Base44 dashboard secrets, never in the repo): AWS S3 (`config/constants.php` hardcodes
  `'upload_type' => 's3'`) plus optionally SQS/SES, AWS MediaConvert (`mediaconvert` webhook),
  OpenAI, Stripe (`subscription/webhook`), Twilio, Google API client, and Firebase Cloud
  Messaging (`config/constants.php` expects the credentials JSON at
  `public/default/fcm_config.json`, which is also missing).
