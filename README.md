# Vehicle Management System (VMS)

A full-stack fleet management platform with role-based access control, real-time gate
security logging, and an embedded Machine Learning engine for predictive analytics.

**Live URL:** https://vms.basepan.com  
**Stack:** Laravel 12 (PHP 8.2) API · Vue 3 + Tailwind frontend · Hostinger hosting

---

## What it does

- **Vehicles** — CRUD with ownership type tracking (organisation vs individual: staff / visitor / vehicle_owner)
- **Drivers** — Driver profiles linked to user accounts and vehicles
- **Gate security** — Check-in / check-out logging with duplicate prevention
- **Maintenance** — Records that auto-create a linked expense in a single transaction
- **Expenses & Income** — Full revenue and cost ledger linked to trips and vehicles
- **Trips** — Start/end location, times, status, and earnings per trip
- **Audit trail** — Every change to every record logged automatically via Spatie Activity Log
- **Roles and permissions** — admin, manager, driver, gate_security, vehicle_owner
- **ML Insights** — Five live machine learning algorithms (see below)

---

## Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12 (PHP 8.2) |
| Auth | Laravel Sanctum (token-based) |
| Roles & permissions | spatie/laravel-permission |
| Activity logging | spatie/laravel-activitylog |
| Excel export | Maatwebsite Excel + PhpSpreadsheet |
| PDF generation | barryvdh/laravel-dompdf |
| Frontend | Vue 3 + Vite + Tailwind CSS |
| Database | SQLite (local / testing), MySQL (production) |
| Hosting | Hostinger shared hosting (LAMP stack) |
| Python ML service | FastAPI + scikit-learn + statsmodels (optional, separate service) |

---

## Machine Learning Engine

The ML engine lives in `app/Services/MLService.php` — pure PHP, no external libraries,
deployable on standard Hostinger shared hosting.

| # | Algorithm | Endpoint |
|---|---|---|
| 1 | Predictive Maintenance (mean-interval regression) | `GET /api/ml/maintenance/predict/{vehicleId}` |
| 2 | Fleet Health Score (weighted composite 0–100) | `GET /api/ml/health/{vehicleId}` |
| 3 | Expense Anomaly Detection (z-score) | `GET /api/ml/anomalies/{vehicleId}` |
| 4 | Driver Performance Score (multi-factor) | `GET /api/ml/driver/{driverId}/score` |
| 5 | Cost Forecast (EWMA over 6 months) | `GET /api/ml/forecast/{vehicleId}` |

Fleet-wide dashboard: `GET /api/ml/dashboard` (admin / manager only)

A Python alternative using scikit-learn, statsmodels, and FastAPI lives in `vms-ml-service/`.

---

## Getting started locally

### Requirements

- PHP 8.2+, Composer
- Node.js 18+, npm
- Laravel Herd (recommended) or `php artisan serve`

```bash
# Clone
git clone https://github.com/portalzone/vms.git
cd vms

# Backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve          # → http://localhost:8000/api

# Frontend
cd vms-frontend/vue-project
npm install
npm run dev                # → http://localhost:5173
```

### Python ML service (optional)

```bash
cd vms-ml-service
python3 -m venv venv && source venv/bin/activate
pip install -r requirements.txt
cp .env.example .env       # fill in DB credentials
uvicorn main:app --reload --port 8001
# Docs → http://localhost:8001/docs
```

---

## Default seeded accounts (password: `abcd1234`)

| Role | Email |
|---|---|
| admin | muojekevictor@gmail.com |
| manager | faith@gmail.com |
| driver | john@gmail.com |
| gate_security | mary@gmail.com |
| vehicle_owner | samuel@gmail.com |

---

## Roles

| Role | Access |
|---|---|
| `admin` | Full access including ML fleet-wide endpoints |
| `manager` | Same as admin except user deletion |
| `driver` | Own vehicle, trips, maintenance |
| `gate_security` | Check-ins, visitor vehicles |
| `vehicle_owner` | Own vehicles and maintenance |

---

## Project structure

```
app/
  Http/Controllers/Api/   ← one controller per resource + MLController
  Models/                 ← Vehicle, Driver, Trip, Maintenance, Expense, Income, User
  Services/
    MaintenanceService.php
    MLService.php         ← 5 ML algorithms

routes/api.php            ← all routes including /api/ml/*

vms-frontend/vue-project/ ← Vue 3 SPA
  src/views/MLDashboard.vue

vms-ml-service/           ← Python FastAPI ML microservice
  main.py
  services/ml_service.py
```

---

## Running tests

```bash
php artisan test
```

---

## Documentation

| File | Contents |
|---|---|
| [API_REFERENCE.md](./API_REFERENCE.md) | Every endpoint with request/response examples |
| [ARCHITECTURE.md](./ARCHITECTURE.md) | System design and decisions |
| [DEPLOYMENT.md](./DEPLOYMENT.md) | Hostinger deployment walkthrough |
| [CHANGELOG.md](./CHANGELOG.md) | Release history |
| [ML_INTERVIEW_PREP.md](./ML_INTERVIEW_PREP.md) | ML algorithms explained in depth |
| [CLAUDE.md](./CLAUDE.md) | AI assistant project context |
