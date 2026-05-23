# Operations Runbook (Setup, Run, Deploy)

## 1) Project Layout Reality

- Canonical source: `Pharma Crm RYVA/`
- Deployment mirror: `Pharma Crm RYVA/hostingercode/`
- Separate nested app: `Pharma Crm RYVA/smhr/`

For normal development, use the root app unless your process explicitly targets `hostingercode/` or `smhr/`.

## 2) Prerequisites (Main App)

- PHP 8.1+
- Composer 2+
- Node + npm (Laravel Mix/Webpack build)
- MySQL/MariaDB

## 3) Local Setup (Main App)

From `Pharma Crm RYVA/`:

1. Create env:
   - `copy .env.example .env` (or use `.env.dev` as template)
2. Configure DB/mail/cache/session values in `.env`
3. Generate key:
   - `php artisan key:generate`
4. Install PHP dependencies:
   - `composer install`
5. Install JS dependencies:
   - `npm install` (or `npm ci`)
6. Build assets:
   - Dev: `npm run dev`
   - Prod bundle: `npm run production`
7. Database:
   - `php artisan migrate`
   - optional seed: `php artisan db:seed`
8. Run app:
   - `php artisan serve --host=127.0.0.1 --port=8000`

## 4) Runtime Dependencies Often Missed

- Scheduler must run every minute (`php artisan schedule:run`)
- Queue workers must run if async queue is configured
- Storage + cache directories need write permissions on server:
  - `storage/`
  - `bootstrap/cache/`

## 5) Important Environment Caveats

- `.env` comments indicate `APP_ENV=codecanyon` is expected by parts of behavior.
- Seeder behavior is environment-dependent in `database/seeders/DatabaseSeeder.php`; seed outcomes differ by env.
- Keep secrets out of git (`.env`, keys, tokens).

## 6) Build and Asset Notes

- Asset pipeline uses Laravel Mix + Webpack (`webpack.mix.js`)
- If UI appears broken after pull/deploy:
  - run `npm install`
  - run `npm run production`
  - clear caches (`php artisan optimize:clear`)

## 7) Deployment Notes

Operational deployment guidance exists in:

- `hostingercode/HOSTINGER_DEPLOYMENT.md`
- `DEPLOYMENT_INSTRUCTIONS.md`

General production sequence:

1. Upload/sync code
2. `composer install --no-dev --optimize-autoloader`
3. configure `.env`
4. `php artisan migrate --force`
5. build production assets
6. cache config/routes/views (optional but common)
7. ensure scheduler + queue are supervised

## 8) Sync Workflow to `hostingercode/`

Provided scripts:

- `scripts/sync-to-hostingercode.bat`
- `scripts/sync_to_hostingercode.py`

These skip heavy/non-deploy folders and env secrets. Use this to keep deployment mirror consistent.

## 9) Testing / Quality State

- PHPUnit config exists, but test coverage appears minimal by default.
- Treat manual smoke tests as required after major changes in:
  - invoicing/payments,
  - payroll generation,
  - pharma reports/imports,
  - cron-triggered features.

## 10) Quick Troubleshooting Checklist

- App not loading -> confirm `.env`, `APP_KEY`, DB connectivity
- 500 errors -> check `storage/logs/laravel.log`
- UI missing styles/scripts -> rebuild assets (`npm run production`)
- Scheduled actions not running -> verify cron for scheduler
- Background jobs stuck -> verify queue driver and worker process
- Module screens missing -> verify module enablement/config and migrations
