# Changelog

All notable changes are documented here. The format loosely follows [Keep a Changelog](https://keepachangelog.com/).

---

## [Unreleased]

Changes that are merged but not yet tagged as a release.

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
