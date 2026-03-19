# Architecture

This document describes how the VMS is structured, how the pieces connect, and the decisions behind the design.

---

## High-level overview

```
Browser (Vue 3 SPA)
        |
        |  HTTP/JSON  (Laravel Sanctum token in Authorization header)
        ↓
Laravel 11 REST API
        |
        |-- Spatie Permission  (role/permission checks per request)
        |-- Spatie Activity Log (automatic change history on all models)
        |
        ↓
Database (SQLite locally, MySQL in production)
```

The frontend and backend are completely separate. The Vue app talks to the API exclusively over HTTP — there is no server-side rendering and no Blade views serving the frontend. The only Blade view (`resources/views/welcome.blade.php`) is the default Laravel placeholder and is not used in production.

---

## Backend (Laravel API)

### Request lifecycle

1. A request hits `public/index.php`.
2. Laravel's kernel boots and runs the middleware stack.
3. `auth:sanctum` validates the bearer token on protected routes.
4. `RoleMiddleware` checks whether the authenticated user holds the required role for that route (where applicable — many controllers do their own role check inline).
5. The request reaches the controller method.
6. The controller validates input, runs business logic (directly or via a Service class), and returns a JSON response.
7. Spatie Activity Log fires model event listeners and writes change records automatically.

### Controllers

All controllers live in `app/Http/Controllers/Api/` and map 1:1 to a resource or concern:

| Controller | Handles |
|---|---|
| `AuthController` | Register, login, logout, current user |
| `UserController` | User CRUD, profile, vehicle-owner queries |
| `VehicleController` | Vehicle CRUD, role-filtered listings, plate search |
| `DriverController` | Driver CRUD, driver profile for current user, Excel export |
| `CheckInOutController` | Gate check-in/out with duplicate prevention |
| `MaintenanceController` | Maintenance records (uses `MaintenanceService`) |
| `ExpenseController` | Expense records |
| `IncomeController` | Income/revenue records |
| `TripController` | Trip logs |
| `DashboardController` | Aggregated stats and recent activity feed |
| `GateSecurityController` | Gate-security-specific stats and alert views |
| `RoleController` | Lists available roles |
| `AuditTrailController` | Reads the Spatie activity log |

### Services

`app/Services/MaintenanceService.php` wraps maintenance creation in a database transaction that also creates a corresponding `Expense` record. This keeps the two tables in sync and means controllers don't need to know about that relationship.

If more cross-model business logic grows, add it as a Service class here rather than embedding it in controllers.

### Authorization

The app uses a two-layer approach:

1. **Spatie roles and permissions** — assigned during seeding, checked via `hasRole()` / `hasAnyRole()` calls inside controllers and a `RoleMiddleware`.
2. **Inline `authorizeAccess()` helpers** inside controllers — each controller defines which roles can perform which action (view, create, update, delete) and aborts with a 403 if the check fails.

The available roles and their permissions are defined in `database/seeders/RolePermissionSeeder.php`.

### Activity logging

Every model that extends `LogsActivity` (all of them) automatically records create, update, and delete events to the `activity_log` table. The `AuditTrailController` exposes these records through the API so the frontend can show a history of changes.

Logging is configured per model via `getActivitylogOptions()`:
- `logAll()` — captures every fillable field
- `logOnlyDirty()` — skips writes where nothing actually changed
- `useLogName('model_name')` — tags each entry with the resource type

---

## Database schema

### Entity relationships

```
users
  ├── has one  → drivers (via drivers.user_id)
  └── has many → vehicles (as owner, via vehicles.owner_id)

vehicles
  ├── belongs to → users (owner)
  ├── has one    → drivers (current assigned driver)
  ├── has many   → check_in_outs
  ├── has many   → trips
  ├── has many   → maintenances
  └── has many   → expenses

drivers
  ├── belongs to → users (the person)
  ├── belongs to → vehicles (assigned vehicle)
  └── has many   → trips

check_in_outs
  ├── belongs to → vehicles
  ├── belongs to → drivers
  ├── belongs to → users (checked_in_by)
  └── belongs to → users (checked_out_by)

trips
  ├── belongs to → drivers
  ├── belongs to → vehicles
  └── has one    → incomes

maintenances
  ├── belongs to → vehicles
  └── has many   → expenses (one created automatically on maintenance insert)

expenses
  ├── belongs to → vehicles
  └── belongs to → maintenances (nullable — manual expenses have no maintenance)

incomes
  ├── belongs to → vehicles
  ├── belongs to → drivers
  └── belongs to → trips
```

### Key design decisions

**Vehicles have an `ownership_type` field** (`organization` or `individual`). Individual vehicles further specify an `individual_type` (`staff`, `visitor`, or `vehicle_owner`). This lets gate security filter to visitor vehicles only, and lets vehicle owners see only their own registered vehicles.

**Drivers are a separate profile table**, not a role flag on `users`. A user can be assigned the `driver` role without having a `Driver` record — they only get a driver profile when they're formally enrolled in the system with a license number, phone, and assigned vehicle.

**Maintenance automatically creates an Expense**. This is handled inside `MaintenanceService::create()` in a single transaction. It keeps the expense ledger complete without requiring the UI to do two separate API calls.

**Check-in/out prevents duplicates**. The `CheckInOutController::store()` method checks for an existing open check-in (one where `checked_out_at IS NULL`) before creating a new one, and returns a 422 if it finds one.

---

## Frontend (Vue 3 SPA)

### Routing and auth

`router/index.js` defines all routes with `meta.requiresAuth` and `meta.role` properties. A navigation guard runs before each route change, checks the Pinia auth store, and redirects to `/login` or `/not-authorized` as needed.

### State

Pinia manages two stores:
- `auth.js` — the current user object and token, persisted to `localStorage`
- `counter.js` — default Vue CLI scaffolding, unused

### Layouts

There are two layout wrappers:
- `AuthenticatedLayout.vue` — includes the sidebar and is used by every protected view
- `GuestLayout.vue` — bare wrapper used by Login and Register

### API communication

All API calls go through the Axios instance in `src/axios.js`. A request interceptor attaches the stored token as a `Bearer` header. A response interceptor handles 401 responses by clearing the auth store and redirecting to `/login`.

---

## Deployment

The project ships with a `Dockerfile` that builds a single container running both PHP-FPM and nginx via supervisord. Render picks this up automatically. See [DEPLOYMENT.md](./DEPLOYMENT.md) for the full process.
