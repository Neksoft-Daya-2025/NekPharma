# VPS deployment (Hostinger / generic Linux)

This folder is a **deployable copy** of the Laravel app: application source, `composer.lock`, `package-lock.json`, and migrations. It does **not** include `vendor/`, `node_modules/`, or `.env` (regenerate on the server).

## 1. Server requirements

- **PHP 8.1+** with extensions: `openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `bcmath`, `curl`, `zip`, `gd` (or `imagick` if required by your build).
- **Composer** 2.x (install globally).
- **MySQL 8+** or **MariaDB** (database and user for this app).
- **Web server**: Nginx or Apache with the **document root** pointing to this app’s **`public/`** directory (not the project root).
- **Node.js 16+** and **npm** on the build machine (this VPS or your PC) to run `npm run production`.

### Permissions

After upload, ensure the PHP user can write to:

- `storage/`
- `bootstrap/cache/`

Example (adjust `www-data` to your PHP-FPM user):

```bash
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
```

## 2. First-time setup on the VPS

From the directory where you uploaded this project (e.g. `/var/www/pharma-crm`):

```bash
cp .env.example .env
nano .env   # set APP_URL, DB_*, mail, etc.
php artisan key:generate
composer install --no-dev --optimize-autoloader
npm ci
npm run production
php artisan migrate --force
```

Optional production optimization (after `.env` is final):

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set `APP_URL` to your real URL (including `https://` on production).

## 3. Database strategy

| Situation | Steps |
|-----------|--------|
| **New database (no data to import)** | Create an empty MySQL database and user → configure `DB_*` in `.env` → run `php artisan migrate --force`. Add seeders only if your team uses them. |
| **Moving data from another server** | On the source: `mysqldump -u USER -p DBNAME > backup.sql`. On the VPS: create DB/user, `mysql -u USER -p DBNAME < backup.sql`, then deploy this code and run **`php artisan migrate --force`** so the `migrations` table and schema match this codebase. Resolve any conflicts manually if versions differ. |

Avoid relying on SQL alone without migrations: Laravel expects the `migrations` table to reflect the code you deploy.

## 4. Updating an existing installation

```bash
git pull   # or upload changed files
composer install --no-dev --optimize-autoloader
npm ci && npm run production
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

Then re-cache config/routes/views in production if you use `config:cache` etc.

## 5. Syncing from the main project folder

On Windows, from the **repository root** (parent of this folder), run:

```bat
scripts\sync-to-hostingercode.bat
```

Or:

```bash
python scripts/sync_to_hostingercode.py
```

This refreshes `hostingercode/` from the main tree, excluding `vendor`, `node_modules`, `.git`, `.cursor`, `.env`, and skipping `public/user-uploads` (large uploads).

## 6. Post-deploy checks

- Open the site login URL; confirm no mixed-content issues (`APP_URL` vs HTTPS).
- `php artisan migrate:status` — all migrations should be **Ran**.
- Test file uploads if you use `storage/app` or `public/user-uploads`.
