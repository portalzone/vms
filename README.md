# Vehicle Management System — Backend API

A REST API built with Laravel 11 that handles everything a vehicle management operation needs day-to-day: tracking vehicles, assigning drivers, logging gate check-ins and check-outs, recording maintenance work, managing trip logs, and keeping a full audit trail of who did what and when.

The frontend is a separate Vue 3 app living inside `vms-frontend/vue-project/`. This README covers the backend only.

---

## What it does

The system is built around a few core ideas:

- **Vehicles** can belong to the organisation or to individuals (staff, visitors, or registered vehicle owners).
- **Drivers** are user accounts with a driver profile attached, assigned to one vehicle at a time.
- **Gate security** logs when vehicles enter and leave the premises through check-in/check-out records.
- **Maintenance** records are automatically linked to an expense entry so costs stay consistent.
- **Trips** track where a vehicle went, who drove it, and what it earned.
- **Audit trail** — every meaningful change to every record is logged automatically via Spatie Activity Log.
- **Roles and permissions** control what each type of user can see and do (admin, manager, driver, gate_security, vehicle_owner, staff, visitor).

---

## Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel 11 |
| Auth | Laravel Sanctum (token-based) |
| Roles & permissions | Spatie laravel-permission |
| Activity logging | Spatie laravel-activitylog |
| Excel export | Maatwebsite Excel + PhpSpreadsheet |
| PDF generation | barryvdh/laravel-dompdf |
| Database | SQLite (local / testing), MySQL (production) |
| Deployment | Docker + Render (nginx + supervisord) |

---

## Getting started locally

### Requirements

- PHP 8.2+
- Composer
- Node.js 18+ and npm (for the frontend; not needed if you only run the API)

### Installation

```bash
# 1. Clone the repo
git clone <repo-url>
cd vms-main

# 2. Install PHP dependencies
composer install

# 3. Copy and fill in your environment file
cp .env.example .env

# 4. Generate the application key
php artisan key:generate

# 5. Run migrations and seed the database
php artisan migrate --seed

# 6. Start the dev server
php artisan serve
```

The API will be available at `http://localhost:8000/api`.

### Default seeded accounts

These are created by the `UserSeeder` when you run `--seed`. All passwords are `abcd1234`.

| Role | Email |
|---|---|
| admin | muojekevictor@gmail.com |
| manager | faith@gmail.com |
| driver | john@gmail.com |
| gate_security | mary@gmail.com |
| vehicle_owner | samuel@gmail.com |
| staff | Peter@gmail.com |
| visitor | kelvin@gmail.com |

---

## Environment variables

See `.env.example` for the full list with descriptions. The ones you'll almost always need to change:

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite           # change to mysql for production
SANCTUM_STATEFUL_DOMAINS=localhost:5173   # your frontend origin
```

---

## Roles and what they can do

| Role | Can do |
|---|---|
| `admin` | Everything — full access to all resources and destructive actions |
| `manager` | Everything except deleting users and system-level config |
| `driver` | View their own vehicle and check-in/out records |
| `gate_security` | Create and close check-in/out records, view visitor vehicles |
| `vehicle_owner` | View and manage their own registered vehicles and maintenance records |
| `staff` / `visitor` | Limited read access depending on context |

---

## Project structure

```
app/
  Http/
    Controllers/Api/   — one controller per resource
    Middleware/        — RoleMiddleware, Authenticate
  Models/              — Vehicle, Driver, Trip, CheckInOut, Maintenance, Expense, Income, User
  Services/            — MaintenanceService (wraps maintenance + expense creation in a transaction)

database/
  migrations/          — all schema migrations
  seeders/             — RolePermissionSeeder, UserSeeder

routes/
  api.php              — all API routes

tests/
  Feature/             — HTTP-level tests per controller
  Unit/                — model and service-level tests
```

---

## Running tests

```bash
php artisan test
```

Tests use an in-memory SQLite database so nothing in your local `.env` gets touched.

---

## Deployment

The project ships with a `Dockerfile` and Render-specific config under `.render/`. See [DEPLOYMENT.md](./DEPLOYMENT.md) for the full walkthrough.

---

## API reference

See [API_REFERENCE.md](./API_REFERENCE.md) for every endpoint, its required fields, and example responses.
