# VMS — Vehicle Management System
**Live URL:** https://vms.basepan.com  
**Stack:** Laravel 12 (PHP 8.2) API · Vue 3 + Tailwind frontend · Hostinger hosting  
**Author:** Victor Muojeke  

---

## Project Overview

A full-stack fleet management platform with role-based access control, real-time gate security logging, and an embedded **Machine Learning engine** for predictive analytics.

---

## Directory Layout

```
vms/
├── app/
│   ├── Http/Controllers/Api/   # All REST controllers (including MLController)
│   ├── Models/                 # Eloquent models
│   ├── Services/
│   │   ├── MaintenanceService.php
│   │   └── MLService.php       ← ML engine (5 algorithms)
│   └── Providers/
├── database/migrations/        # Schema history
├── routes/api.php              # All API routes (including /api/ml/*)
├── vms-frontend/vue-project/   # Vue 3 SPA
└── CLAUDE.md                   # This file
```

---

## Authentication

Laravel Sanctum token-based auth.  
- `POST /api/login` → returns Bearer token  
- All other routes require `Authorization: Bearer <token>` header  

Roles managed via **spatie/laravel-permission**: `admin`, `manager`, `vehicle_owner`, `driver`

---

## Core Modules

| Module | Controller | Key Endpoints |
|---|---|---|
| Auth | AuthController | `/login`, `/logout`, `/me` |
| Vehicles | VehicleController | CRUD `/vehicles` |
| Drivers | DriverController | CRUD `/drivers` |
| Trips | TripController | CRUD `/trips` |
| Maintenance | MaintenanceController | CRUD `/maintenances` |
| Expenses | ExpenseController | CRUD `/expenses` |
| Income | IncomeController | CRUD `/incomes` |
| Check-In/Out | CheckInOutController | `/checkins`, `/checkins/{id}/checkout` |
| Gate Security | GateSecurityController | `/gate-security/stats`, `/alerts` |
| Dashboard | DashboardController | `/dashboard/stats`, `/monthly-trends` |
| **ML Engine** | **MLController** | `/api/ml/*` (see below) |

---

## ML Engine — `app/Services/MLService.php`

The ML engine is a pure-PHP statistical module (no external Python service required), making it deployable on standard Hostinger shared hosting.

### Algorithm 1 — Predictive Maintenance
**Endpoint:** `GET /api/ml/maintenance/predict/{vehicleId}`  
**Method:** Mean-interval regression  
- Collects all maintenance dates for a vehicle sorted chronologically  
- Computes intervals (days) between consecutive events  
- Mean interval = next predicted service window  
- Confidence = 1 − (σ / μ) where σ is the standard deviation of intervals  
- Output: `predicted_date`, `days_until`, `risk_level` (Low/Medium/High/Critical), `confidence`

### Algorithm 2 — Fleet Health Score
**Endpoints:**  
- `GET /api/ml/health/{vehicleId}` — single vehicle  
- `GET /api/ml/health/fleet` — all vehicles ranked  
**Method:** Weighted composite scoring  

| Component | Weight | Formula |
|---|---|---|
| Maintenance completion | 30% | completed / total maintenances × 100 |
| Trip completion | 25% | completed / total trips × 100 |
| Cost efficiency | 25% | income / (income + expenses) × 100 |
| Availability | 20% | 100 − (pending_maint × 20) |

Output: score 0–100, grade A–F

### Algorithm 3 — Expense Anomaly Detection
**Endpoints:**  
- `GET /api/ml/anomalies/{vehicleId}?threshold=2.0`  
- `GET /api/ml/anomalies/fleet`  
**Method:** Z-Score statistical outlier detection  
- μ = mean of all expense amounts for a vehicle  
- σ = population standard deviation  
- z = (x − μ) / σ — flag when |z| > 2.0 (~95% confidence interval)  
- Severity: Normal < 2, Medium 2–3, High 3–4, Critical 4+

### Algorithm 4 — Driver Performance Score
**Endpoints:**  
- `GET /api/ml/driver/{driverId}/score`  
- `GET /api/ml/driver/scores/all`  
**Method:** Multi-factor scoring  

| Component | Weight | Formula |
|---|---|---|
| Trip completion rate | 40% | completed_trips / total_trips |
| Revenue per trip | 35% | driver_avg_income / fleet_avg_income (capped at 1.0) |
| Safety (incident-free) | 25% | trips without same-day pending maintenance |

### Algorithm 5 — Cost Forecast (EWMA)
**Endpoints:**  
- `GET /api/ml/forecast/{vehicleId}`  
- `GET /api/ml/forecast/fleet`  
**Method:** Exponentially Weighted Moving Average over 6 months  
- Weights: newest 0.30, 0.20, 0.15, 0.15, 0.10, 0.10 (sum = 1.0)  
- Forecast = Σ(amount × weight) / Σ(weights)  
- Alert levels: Normal / Medium (>20% above last month) / High (>50%)

### ML Dashboard (All-in-One)
**Endpoint:** `GET /api/ml/dashboard` (admin/manager only)  
Returns all five model outputs in a single API call.

---

## Key Commands

```bash
# Install dependencies
composer install

# Run migrations
php artisan migrate

# Serve locally (Herd)
php artisan serve

# Run tests
php artisan test
```

---

## Hosting Notes (Hostinger)

- Hostinger uses a standard LAMP stack (Apache + PHP)
- The ML engine runs entirely in PHP — no Python or ML libraries needed on the server
- Set `APP_ENV=production` and `APP_DEBUG=false` in the Hostinger `.env`
- Point document root to `public/`
- The Vue 3 frontend is built (`npm run build`) and served separately or as a static site

---

## Environment Variables (`.env`)

```
APP_NAME="Vehicle Management System"
APP_ENV=production
APP_URL=https://vms.basepan.com
DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=vms
SANCTUM_STATEFUL_DOMAINS=vms.basepan.com
```

---

## Testing

```bash
php artisan test
# or specific feature
php artisan test --filter=MaintenanceTest
```

Tests live in `tests/Feature/` and `tests/Unit/`.

---

## Security Practices

- All routes protected by Sanctum (`auth:sanctum` middleware)
- Role-based authorization via Spatie Permission
- ML fleet-wide endpoints restricted to `admin` and `manager` roles
- Driver/owner endpoints scoped to their own vehicle/trips
- Activity logging via `spatie/laravel-activitylog` on all models
