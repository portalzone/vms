# Changelog

All notable changes are documented here. The format loosely follows [Keep a Changelog](https://keepachangelog.com/).

---

## [Unreleased]

Changes that are merged but not yet tagged as a release.

---

## [2.0.0] — 2026-05

### Added — ML Engine

- **`app/Services/MLService.php`** — Pure-PHP ML engine with five algorithms, no external libraries, deployable on Hostinger shared hosting
  - Algorithm 1: Predictive Maintenance (mean-interval regression + coefficient of variation confidence score)
  - Algorithm 2: Fleet Health Score (weighted composite 30/25/25/20 across maintenance, trips, cost efficiency, availability)
  - Algorithm 3: Expense Anomaly Detection (z-score, population std dev, threshold z > 2.0, severity Normal/Medium/High/Critical)
  - Algorithm 4: Driver Performance Score (multi-factor 40/35/25 across trip completion, revenue per trip, safety)
  - Algorithm 5: Cost Forecast (Exponentially Weighted Moving Average over 6 months, weights 0.30/0.20/0.15/0.15/0.10/0.10)
  - Cold-start handling: all algorithms return graceful "Insufficient data" messages instead of errors

- **`app/Http/Controllers/Api/MLController.php`** — 10 REST endpoints delegating to MLService
  - `GET /api/ml/dashboard` — all five model outputs in a single call (admin/manager only)
  - `GET /api/ml/health/fleet` — all vehicles ranked by health score
  - `GET /api/ml/health/{vehicleId}` — single vehicle health
  - `GET /api/ml/anomalies/fleet` — fleet-wide anomaly report
  - `GET /api/ml/anomalies/{vehicleId}` — per-vehicle anomaly detection
  - `GET /api/ml/driver/scores/all` — all driver rankings
  - `GET /api/ml/driver/{driverId}/score` — individual driver score
  - `GET /api/ml/forecast/fleet` — fleet cost forecasts
  - `GET /api/ml/forecast/{vehicleId}` — per-vehicle EWMA forecast
  - `GET /api/ml/maintenance/predict/{vehicleId}` — next maintenance prediction

- **`vms-frontend/vue-project/src/views/MLDashboard.vue`** — ML Insights page (admin/manager only)
  - Fleet Health overview with score bars and A–F grade badges
  - Cost Forecast section with trend indicators and alert levels
  - Expense Anomaly Detection table with severity colours
  - Driver Performance Rankings with breakdown scores

- **`vms-frontend/vue-project/src/router/index.js`** — added `/ml-insights` route restricted to admin and manager roles

- **`vms-frontend/vue-project/src/layouts/AuthenticatedLayout.vue`** — added ML Insights nav link (admin/manager only)

- **`vms-ml-service/`** — Optional Python FastAPI microservice using real ML libraries
  - scikit-learn LinearRegression (R² score) for maintenance prediction
  - scikit-learn IsolationForest for anomaly detection (no normal-distribution assumption)
  - statsmodels Holt-Winters ExponentialSmoothing for forecasting (handles trend + seasonality)
  - scipy.stats.zscore + pandas groupby for statistical analysis
  - SQLAlchemy connection to same MySQL database (read-only)
  - FastAPI auto-docs at `/docs`
  - Run with: `uvicorn main:app --reload --port 8001`

### Fixed — Deployment

- `.htaccess` API routing: changed `RewriteCond %{REQUEST_URI} ^/api` pattern to `RewriteRule ^api(/.*)?$ index.php [L]` — previous pattern was not matching on Hostinger Apache
- Vue SPA routing: added `.htaccess` to `vms-frontend/vue-project/public/` with history-mode fallback to `index.html`
- Vite dotfile exclusion: added `closeBundle` plugin to `vite.config.js` to copy `.htaccess` from `public/` into `dist/` on each build (Vite skips dotfiles by default)

### Fixed — ML

- `MLService::detectExpenseAnomalies()` early return (< 3 records) was missing `'anomaly_count' => 0` key, causing undefined array key error in `MLController::dashboard()`

### Changed

- Upgraded from Laravel 11 to Laravel 12
- Replaced Docker/Render deployment with Hostinger shared hosting (LAMP stack)
- Removed Docker configuration files (no longer needed)

---

## [1.0.0] — 2025-08

Initial release of the Vehicle Management System.

### Backend

- Laravel 11 REST API with Sanctum token authentication
- Role-based access control via Spatie laravel-permission
  - Roles: admin, manager, driver, gate_security, vehicle_owner, staff, visitor
- Full audit trail on all models via Spatie laravel-activitylog
- **Vehicles** — CRUD with ownership type tracking (organization vs individual/staff/visitor/vehicle_owner), plate number search, role-filtered listings
- **Drivers** — Driver profiles linked to user accounts and vehicles, Excel trip export
- **Check-In / Check-Out** — Gate entry/exit logging with duplicate prevention, vehicles-within-premises view
- **Maintenance** — Maintenance records that automatically create a linked expense in a single transaction
- **Expenses** — Manual and maintenance-linked expense tracking
- **Incomes** — Revenue records linked to trips, vehicles, and drivers
- **Trips** — Trip logs with start/end location, times, status, and amount
- **Dashboard** — Aggregate stats (vehicle count, driver count, total income/expense, active trips, check-in counts)
- **Gate Security dashboard** — Specialised stats and alert feed for gate security role
- **Audit trail** — Full change history viewable via API
- **User management** — Admin-only user CRUD with role assignment
- **Profile** — Users can view and update their own profile
- Database seeders for all roles and default test accounts
- Docker + Render deployment configuration (nginx + PHP-FPM + supervisord)

### Frontend

- Vue 3 SPA with Vite and Tailwind CSS
- Pinia for auth state management
- Role-aware routing with navigation guards
- Separate layouts for authenticated and guest views
- Dashboard views per role (admin, manager, driver, gate_security, vehicle_owner)
- Vehicles module — list, create, edit, detail
- Drivers module — list, profile page, create, edit
- Check-ins module — gate check-in/out form and log
- Maintenance module — list and form
- Expenses module — list and form
- Income module — list and form
- Trips module — list and form
- Audit trail viewer
- User management (admin only)
- User profile page
- Pagination, toast notifications, modal confirmations
- Stats charts and monthly trends chart on dashboard
