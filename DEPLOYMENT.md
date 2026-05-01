# Deployment

This document covers how to deploy VMS to Hostinger shared hosting (the live setup at `vms.basepan.com`) and how to run it locally for development.

---

## Local development

Follow the setup steps in the main [README](./README.md). The short version:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve      # → http://localhost:8000/api
```

For the frontend:

```bash
cd vms-frontend/vue-project
npm install
npm run dev            # → http://localhost:5173
```

---

## Environment configuration

### Backend `.env` (production values)

```dotenv
APP_NAME="Vehicle Management System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vms.basepan.com

DB_CONNECTION=mysql
DB_HOST=<hostinger_db_host>
DB_PORT=3306
DB_DATABASE=vms
DB_USERNAME=<db_user>
DB_PASSWORD=<db_password>

SANCTUM_STATEFUL_DOMAINS=vms.basepan.com
```

> `SANCTUM_STATEFUL_DOMAINS` must match the domain the Vue SPA is served from so that Sanctum accepts the Bearer token on cross-origin requests.

---

## Deploying to Hostinger (shared hosting)

VMS runs on a Hostinger shared LAMP stack (Apache + PHP 8.2 + MySQL). There is no Docker or Node runtime on the server — the Laravel API and the Vue SPA static files live in the same `public/` directory.

### Directory layout on the server

```
public_html/
└── vms/                        ← Laravel project root
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env                    ← production env (not in git)
    └── public/                 ← web root for vms.basepan.com
        ├── index.php           ← Laravel entry point
        ├── index.html          ← Vue SPA entry point
        ├── assets/             ← Vite-built JS/CSS bundles
        └── .htaccess           ← routes /api/* to Laravel, rest to Vue
```

The Hostinger control panel must have the domain's document root pointing to `public_html/vms/public/`.

### Step 1 — Upload the Laravel backend

1. On your local machine, run `composer install --no-dev --optimize-autoloader` to generate the `vendor/` directory.
2. In Hostinger File Manager (or via FTP/SFTP), upload the entire project to `public_html/vms/` — **excluding** `vms-frontend/`, `vms-ml-service/`, `.git/`, and `node_modules/`.
3. Upload your `.env` file with the production values shown above.
4. In Hostinger's **MySQL Databases** panel, create the database and user, then run the migrations:
   ```bash
   # via Hostinger SSH terminal
   cd public_html/vms
   php artisan migrate --force
   php artisan db:seed --force   # only on first deploy; seeds default accounts
   php artisan config:cache
   php artisan route:cache
   php artisan storage:link
   ```

### Step 2 — Build and upload the Vue frontend

1. On your local machine, build the frontend:
   ```bash
   cd vms-frontend/vue-project
   npm install
   npm run build
   ```
   This produces a `dist/` folder. The `vite.config.js` `closeBundle` plugin automatically copies `.htaccess` into `dist/` so it is included in the build output.

2. Upload the **contents** of `dist/` into `public_html/vms/public/` — that means `index.html`, `assets/`, and `.htaccess` go directly inside `public/`.

### Step 3 — Verify `.htaccess` routing

The `public/.htaccess` file handles two concerns:

```apache
Options -MultiViews
RewriteEngine On

# Route all /api/* requests to Laravel's index.php
RewriteRule ^api(/.*)?$ index.php [L]

# Serve real files (JS, CSS, images) directly
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ - [L]

# All other requests fall through to Vue SPA
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [L]
```

**Why `RewriteRule ^api` instead of `RewriteCond %{REQUEST_URI}`:** Hostinger's Apache config resolves `REQUEST_URI` differently; using the rule pattern directly is more reliable across shared-hosting setups.

If you see a 500 on API routes, confirm:
- `mod_rewrite` is enabled (it is on Hostinger by default).
- `AllowOverride All` is set for your document root (Hostinger sets this for `public_html`).
- The `.htaccess` file was actually uploaded (Vite skips dotfiles without the copy plugin).

---

## Updating a deployed version

### Backend changes

1. Upload the changed PHP files via File Manager or SFTP.
2. If there are new migrations:
   ```bash
   php artisan migrate --force
   ```
3. Clear caches after any config/route change:
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

### Frontend changes

1. Rebuild locally: `npm run build`
2. Upload the updated contents of `dist/` to `public_html/vms/public/`, overwriting old files.

---

## Database

### Local (development)

The default `.env.example` uses SQLite:

```bash
touch database/database.sqlite
php artisan migrate --seed
```

### Production (Hostinger MySQL)

Create the database in Hostinger's **MySQL Databases** panel, then point `.env` at it. Run `php artisan migrate --force` via SSH after each deployment that includes migrations.

> The `UserSeeder` uses `firstOrCreate` so re-running seeds won't overwrite existing accounts, but avoid seeding on a live database with real user data unless you've reviewed the seeder first.

---

## Python ML microservice (optional)

The Python FastAPI service in `vms-ml-service/` is **not deployed to Hostinger** — shared hosting has no persistent Python runtime. It is intended for local development or a separate VPS/container.

```bash
cd vms-ml-service
python3 -m venv venv && source venv/bin/activate
pip install -r requirements.txt
cp .env.example .env   # fill in DB credentials
uvicorn main:app --reload --port 8001
# Auto-docs → http://localhost:8001/docs
```

The PHP `MLService.php` handles all production ML workloads on Hostinger — the Python service is an optional upgrade that uses real ML libraries (scikit-learn, statsmodels) and connects to the same MySQL database via SQLAlchemy.

---

## Troubleshooting

**500 on any API route**
- Confirm `APP_KEY` is set in `.env`.
- Check `storage/logs/laravel.log` for the real error.
- Make sure `storage/` and `bootstrap/cache/` are writable (`chmod -R 775 storage bootstrap/cache`).

**Login returns 401 / CORS error**
- Confirm `SANCTUM_STATEFUL_DOMAINS=vms.basepan.com` in `.env` (no `https://` prefix).
- Check `config/cors.php` — `allowed_origins` should include your frontend domain or `['*']` for open access.

**Vue routes return 404 on refresh**
- The `.htaccess` fallback to `index.html` is missing or not being read.
- Confirm `.htaccess` is present in `public/` on the server (upload it manually if the Vite build didn't include it).

**ML Insights page not visible in nav**
- The user's role must be `admin` or `manager` — the link is hidden for other roles.
- Check the Pinia auth store: open browser devtools → Application → Local Storage → confirm `role` is set correctly.

**Blank page after Vue build upload**
- Check the browser console for a 404 on a JS/CSS asset.
- Confirm `assets/` was uploaded alongside `index.html`.
- Confirm the Vite `base` in `vite.config.js` is `/` (default) so asset paths are absolute.
