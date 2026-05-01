# Architecture

This document describes how the VMS is structured, how the pieces connect, and the decisions behind the design.

---

## High-level overview

```
Browser (Vue 3 SPA — vms.basepan.com)
        |
        |  HTTP/JSON  (Authorization: Bearer <token>)
        ↓
Laravel 12 REST API  (vms.basepan.com/api/*)
        |
        |── auth:sanctum          (token validation)
        |── RoleMiddleware        (role checks per route)
        |── Spatie Permission     (hasRole / hasAnyRole)
        |── Spatie Activity Log   (automatic change history)
        |── MLService             (5 ML algorithms, pure PHP)
        |
        ↓
MySQL Database (Hostinger)

        ┄┄ optional ┄┄
Python FastAPI ML Service  (vms-ml-service/ — runs separately)
        |
        |── scikit-learn  (LinearRegression, IsolationForest)
        |── statsmodels   (Holt-Winters ExponentialSmoothing)
        |── scipy / pandas / numpy
        |
        ↓
Same MySQL Database (read-only)
```

The frontend and backend are completely separate.
The Vue SPA talks to the API exclusively over HTTP.
The Python ML service is an optional upgrade that reads the same database using SQLAlchemy.

---

## Backend (Laravel 12 API)

### Request lifecycle

1. Request hits `public/index.php`.
2. Laravel's kernel boots and runs the middleware stack.
3. `auth:sanctum` validates the Bearer token on protected routes.
4. `RoleMiddleware` checks the required role for that route.
5. The controller validates input, runs business logic, returns JSON.
6. Spatie Activity Log writes change records automatically on model events.

### Controllers

All controllers live in `app/Http/Controllers/Api/`:

| Controller | Handles |
|---|---|
| `AuthController` | Register, login, logout, current user |
| `UserController` | User CRUD, profile, vehicle-owner queries |
| `VehicleController` | Vehicle CRUD, role-filtered listings, plate search |
| `DriverController` | Driver CRUD, profile, Excel export |
| `CheckInOutController` | Gate check-in/out with duplicate prevention |
| `MaintenanceController` | Maintenance records (uses `MaintenanceService`) |
| `ExpenseController` | Expense records |
| `IncomeController` | Income/revenue records |
| `TripController` | Trip logs |
| `DashboardController` | Aggregated stats and recent activity feed |
| `GateSecurityController` | Gate-security-specific stats and alerts |
| `RoleController` | Lists available roles |
| `AuditTrailController` | Reads the Spatie activity log |
| `MLController` | All 10 ML API endpoints — delegates to `MLService` |

### Services

| Service | Purpose |
|---|---|
| `MaintenanceService.php` | Wraps maintenance + expense creation in one DB transaction |
| `MLService.php` | Five ML algorithms in pure PHP — no external libraries required |

### ML Engine (`app/Services/MLService.php`)

Five algorithms, all executable on Hostinger shared hosting (no Python or ML libraries needed):

| Method | Algorithm | Output |
|---|---|---|
| `predictiveMaintenance()` | Mean-interval regression + CV confidence | predicted_date, days_until, risk_level, confidence |
| `fleetHealthScore()` | Weighted composite (30/25/25/20) | score 0–100, grade A–F, breakdown |
| `detectExpenseAnomalies()` | Z-score statistical outlier detection | anomalies list, z_score, severity |
| `driverPerformanceScore()` | Multi-factor scoring (40/35/25) | score 0–100, grade, breakdown |
| `costForecast()` | Exponentially Weighted Moving Average (6 months) | forecasted_cost, trend, alert_level |

### Authorization

Two-layer approach:
1. **Spatie roles** — assigned at seeding, checked via `hasRole()` / `hasAnyRole()`
2. **Inline `authorizeAccess()` helpers** inside each controller

ML fleet-wide endpoints (`/api/ml/dashboard`, `/api/ml/health/fleet`, etc.) are restricted to `admin` and `manager` roles. Per-vehicle endpoints are scoped to the vehicle's owner or assigned driver.

### Activity logging

Every model uses `LogsActivity` trait (all of them). Config per model via `getActivitylogOptions()`:
- `logAll()` — captures every fillable field
- `logOnlyDirty()` — skips unchanged writes
- `useLogName('model_name')` — tags entries by resource type

---

## Database schema

### Entity relationships

```
users
  ├── has one  → drivers         (via drivers.user_id)
  └── has many → vehicles        (as owner, via vehicles.owner_id)

vehicles
  ├── belongs to → users         (owner)
  ├── has one    → drivers       (current assigned driver)
  ├── has many   → check_in_outs
  ├── has many   → trips
  ├── has many   → maintenances
  ├── has many   → expenses
  └── has many   → incomes

drivers
  ├── belongs to → users         (the person)
  ├── belongs to → vehicles      (assigned vehicle)
  ├── has many   → trips
  └── has many   → incomes

trips
  ├── belongs to → drivers
  ├── belongs to → vehicles
  └── has one    → incomes

maintenances
  ├── belongs to → vehicles
  └── has one    → expenses      (auto-created on maintenance insert)

expenses
  ├── belongs to → vehicles
  └── belongs to → maintenances  (nullable — manual expenses have no maintenance)

incomes
  ├── belongs to → vehicles
  ├── belongs to → drivers
  └── belongs to → trips
```

### Key design decisions

**Maintenance auto-creates an Expense.** `MaintenanceService::create()` wraps both inserts in a single DB transaction. This keeps the expense ledger consistent without requiring the UI to make two API calls.

**Drivers are a separate profile table.** A user can have the `driver` role without a `Driver` record — they only get one when formally enrolled with a license number and assigned vehicle.

**Check-in/out prevents duplicates.** `CheckInOutController::store()` checks for an open check-in (`checked_out_at IS NULL`) before creating a new one, returning 422 if found.

**ML cold-start is handled gracefully.** All five ML algorithms return clear "Insufficient data" messages rather than errors when a vehicle or driver doesn't have enough history yet.

---

## Frontend (Vue 3 SPA)

### Routing and auth

`router/index.js` defines all routes with `meta.requiresAuth` and `meta.roles`. A navigation guard runs before each route change, checks the Pinia auth store, and redirects to `/login` or `/not-authorized` as needed.

ML Insights route (`/ml-insights`) is restricted to `admin` and `manager` roles.

### State

Pinia manages:
- `auth.js` — current user object and token, persisted to `localStorage`

### Layouts

- `AuthenticatedLayout.vue` — top navbar (includes 🤖 ML Insights link for admin/manager)
- `GuestLayout.vue` — bare wrapper for Login and Register

### Key views

| View | Route | Role |
|---|---|---|
| `AdminDashboard.vue` | `/dashboard` | admin, manager |
| `MLDashboard.vue` | `/ml-insights` | admin, manager |
| `DriverDashboard.vue` | `/dashboard` | driver |
| `VehicleOwnerDashboard.vue` | `/dashboard` | vehicle_owner |
| `GateSecurityDashboard.vue` | `/dashboard` | gate_security |

### API communication

All API calls go through `src/axios.js`. A request interceptor attaches the stored token as a `Bearer` header. A 401 response clears the auth store and redirects to `/login`.

---

## Python ML Microservice (`vms-ml-service/`)

An optional FastAPI service that uses real ML libraries on the same MySQL database.

| PHP MLService | Python MLService | Upgrade |
|---|---|---|
| Manual mean/std | numpy | Same maths, cleaner code |
| Manual EWMA weights | statsmodels Holt-Winters | Handles trend + seasonality |
| Z-score (manual) | scipy.stats.zscore + IsolationForest | No normal distribution assumption |
| Manual averages | pandas groupby | Faster, more readable |
| LinearRegression (manual) | scikit-learn LinearRegression | R² score, extensible |

Run with: `uvicorn main:app --reload --port 8001`  
Auto-docs: `http://localhost:8001/docs`

---

## Hosting (Hostinger)

```
public_html/
└── vms/                        ← Laravel project root
    ├── app/
    ├── public/                 ← web root for vms.basepan.com
    │   ├── index.php           ← Laravel entry point
    │   ├── index.html          ← Vue SPA entry point
    │   ├── assets/             ← Vite-built JS/CSS
    │   └── .htaccess           ← routes /api/* to Laravel, rest to Vue
    └── ...
```

The `.htaccess` in `public/` handles both Laravel API routing and Vue SPA routing:
- `RewriteRule ^api(/.*)?$ index.php [L]` — API calls go to Laravel
- Real files (JS/CSS) served directly
- Everything else falls through to `index.html` (Vue Router handles it)
