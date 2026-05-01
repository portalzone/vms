# Components

This document describes every Vue component in `vms-frontend/vue-project/src/`. It covers layouts, shared UI components, and page-level views grouped by module.

---

## Layouts

Located in `src/layouts/`.

### `AuthenticatedLayout.vue`

Top-level wrapper for all authenticated pages. Renders the top navbar with:
- App name / brand link
- Navigation links (Dashboard, Vehicles, Drivers, Check-Ins, Maintenance, Expenses, Income, Trips, Audit Trail, Users)
- ML Insights link — visible only to `admin` and `manager` roles
- User profile link and logout button
- Hamburger menu for mobile (collapses nav links)

**Used by:** every view behind the `auth:sanctum` guard.

**Auth logic:** reads the current user from the Pinia `auth` store. `hasRole(roles)` checks `auth.user?.role` (or the `user` prop as a fallback) to conditionally show role-restricted links.

---

### `GuestLayout.vue`

Bare wrapper for unauthenticated pages (Login, Register). No navbar — just a centred content slot.

---

## Shared Components

Located in `src/components/`.

### `Card.vue`

A stat card for dashboard summaries.

| Prop | Type | Description |
|---|---|---|
| `title` | String | Label shown above the value |
| `subtitle` | String | Optional small text below the value |

The card value is passed via the default slot. Used by role dashboards to show counts (vehicles, drivers, total income, etc.).

---

### `Modal.vue`

Confirmation modal for destructive actions (delete). Fixed overlay, centred dialog.

| Prop | Type | Description |
|---|---|---|
| `title` | String | Dialog heading |
| `message` | String | Body text (rendered with `v-html`) |

| Event | When |
|---|---|
| `close` | User clicks Cancel or backdrop |
| `confirm` | User clicks Delete |

---

### `ModalNotification.vue`

A more capable modal that can act as either a notification or a confirm dialog.

| Prop | Type | Default | Description |
|---|---|---|---|
| `show` | Boolean | `false` | Controls visibility (reactive via watcher) |
| `title` | String | `'Notification'` | Dialog heading |
| `message` | String | `''` | Body text (rendered with `v-html`, `whitespace-pre-wrap`) |
| `showConfirm` | Boolean | `false` | When true, shows Cancel and Confirm buttons |

| Event | When |
|---|---|
| `close` | Modal dismissed |
| `confirm` | Confirm button clicked |

---

### `Notification.vue`

Slide-in toast banner anchored to the top-right corner. Supports multi-line messages via `<pre>`.

**Exposed method:** `showNotification(message, type)` — call via template ref.
- `type` = `'success'` → green background
- `type` = `'error'` → red background
- Auto-hides after 4 seconds

---

### `Toast.vue`

Simpler single-line toast banner, also top-right.

**Exposed method:** `showToast(message, duration, color)`
- `color` = `'success'` or `'error'`
- `duration` defaults to 3000 ms

---

### `Pagination.vue`

Page navigation for any paginated list. Only renders when there is more than one page.

| Prop | Type | Description |
|---|---|---|
| `meta` | Object | Laravel paginator `meta` object — must contain `current_page` and `last_page` |

| Event | Payload | When |
|---|---|---|
| `page-changed` | `Number` — new page | A page button or Prev/Next is clicked |

---

### `StatsChart.vue`

Bar chart (Chart.js via vue-chartjs) for the admin/manager dashboard overview.

| Prop | Type | Description |
|---|---|---|
| `stats` | Object | Dashboard stats — `vehicles`, `drivers`, `trips`, `expenses`, `maintenances` |

Displays three bars: Vehicles, Drivers, Trips.

---

### `TrendsChart.vue`

Line chart showing 12-month activity trends. Fetches its own data from `GET /api/dashboard/monthly-trends` on mount. Plots three lines: Maintenance Costs, Expenses, and Trips.

No props required. Self-contained.

---

### `RecentActivity.vue`

Activity feed with filter controls. Shows change-log entries from Spatie Activity Log.

**Filters:** event type (Check-In, Maintenance Created/Updated, Trip, Vehicle, Driver, Expense, Income) and date range.

Supports pagination internally — emits no events, manages its own page state.

---

### `VehicleTable.vue`

Vehicle listing table with ownership-type filter (All, Organization, Staff, Visitor, Vehicle Owner). Shows plate number, model, manufacturer, year, ownership type, and owner/driver name. Includes Edit and Delete action buttons.

---

### Icons (`src/components/icons/`)

Simple SVG icon components used inside the sidebar and nav:

| Component | Icon for |
|---|---|
| `IconDashboard.vue` | Dashboard |
| `IconVehicle.vue` | Vehicles |
| `IconDriver.vue` | Drivers |
| `IconCheckInOut.vue` | Check-Ins |
| `IconMaintenance.vue` | Maintenance |
| `IconExpense.vue` | Expenses |
| `IconAbout.vue` | About |
| `IconCommunity.vue` | Community |
| `IconDocumentation.vue` | Documentation |
| `IconEcosystem.vue` | Ecosystem |
| `IconSupport.vue` | Support |
| `IconTooling.vue` | Tooling |

---

## Views

Located in `src/views/`.

### Auth

| File | Route | Description |
|---|---|---|
| `Login.vue` | `/login` | Email + password form, calls `POST /api/login`, stores token in Pinia |
| `Register.vue` | `/register` | New account form |

---

### Dashboards

| File | Route | Role(s) | Description |
|---|---|---|---|
| `AdminDashboard.vue` | `/dashboard` | admin, manager | Stats cards, StatsChart, TrendsChart, recent check-ins |
| `DriverDashboard.vue` | `/dashboard` | driver | Own vehicle, trips, maintenance summary |
| `VehicleOwnerDashboard.vue` | `/dashboard` | vehicle_owner | Owned vehicles and maintenance records |
| `GateSecurityDashboard.vue` | `/dashboard` | gate_security | Vehicles currently on premises, active alerts |
| `UserDashboard.vue` | `/dashboard` | fallback | Generic dashboard for other roles |
| `Dashboard.vue` | — | — | Route dispatcher — checks role and renders the correct dashboard component |
| `MLDashboard.vue` | `/ml-insights` | admin, manager | ML insights page (see below) |

#### `MLDashboard.vue`

Calls `GET /api/ml/dashboard` on mount and renders four sections:

- **Fleet Health** — scored bar per vehicle, A–F grade badge, colour-coded by score range
- **Cost Forecast** — EWMA forecast per vehicle with trend arrow and alert badge (Normal / Medium / High)
- **Anomaly Detection** — table of expense anomalies with severity colours (Normal / Medium / High / Critical)
- **Driver Rankings** — sorted list of driver performance scores with breakdown bars

Helper functions: `scoreColor()`, `gradeBadge()`, `riskClass()`, `alertClass()`, `barHeight()`.

---

### Vehicles (`src/views/Vehicles/`)

| File | Description |
|---|---|
| `VehiclesPage.vue` | Container page — holds list and handles navigation to forms |
| `VehicleList.vue` / `VehicleList2.vue` / `VehicleList3.vue` | Iterative versions of the vehicle listing table |
| `VehicleForm.vue` | Create / edit form fields |
| `VehicleFormPage.vue` | Wraps VehicleForm, handles API call and redirect |
| `VehicleWithin.vue` | Shows vehicles currently checked in (on premises) |

---

### Drivers (`src/views/Drivers/`)

| File | Description |
|---|---|
| `DriversPage.vue` | Container page |
| `DriverList.vue` | Tabular listing with Excel export button |
| `DriverForm.vue` | Create / edit form fields |
| `DriverFormPage.vue` | Wraps DriverForm, handles API call and redirect |
| `DriverProfilePage.vue` | Full driver profile — linked user, vehicle, trip history |

---

### Check-Ins (`src/views/CheckIns/`)

| File | Description |
|---|---|
| `CheckInsPage.vue` | Container page |
| `CheckInForm.vue` / `CheckInForm1.vue` | Gate check-in form fields |
| `CheckInFormPage.vue` | Handles check-in and check-out API calls, duplicate prevention feedback |

---

### Maintenance (`src/views/Maintenances/`)

| File | Description |
|---|---|
| `MaintenancePage.vue` | Listing with status filter |
| `MaintenanceFormPage.vue` | Create / edit form — auto-creates a linked expense via `MaintenanceService` on the backend |

---

### Expenses (`src/views/Expenses/`)

| File | Description |
|---|---|
| `ExpensesPage.vue` | Container page |
| `ExpenseList.vue` | Tabular listing |
| `ExpenseForm.vue` | Form fields |
| `ExpenseFormPage.vue` | Handles API call and redirect |

---

### Income (`src/views/Income/`)

| File | Description |
|---|---|
| `IncomeList.vue` | Tabular listing |
| `IncomeFormPage.vue` | Create / edit form, handles API call |

---

### Trips (`src/views/Trips/`)

| File | Description |
|---|---|
| `TripsPage.vue` | Container page |
| `TripList.vue` | Listing with status and date filters |
| `TripForm.vue` | Form fields |
| `TripFormPage.vue` | Handles API call and redirect |

---

### Users (`src/views/Users/`)

Visible to `admin` only.

| File | Description |
|---|---|
| `UsersPage.vue` | User listing with role filter |
| `UserForm.vue` | Create / edit form |
| `UserFormPage.vue` | Handles API call and redirect |

---

### Audit Trail (`src/views/Audit/`)

| File | Description |
|---|---|
| `AuditTrailList.vue` | Full activity log viewer — wraps RecentActivity component with full-page layout |

---

### Profile (`src/views/Profile/`)

| File | Description |
|---|---|
| `UserProfile.vue` | Current user's profile — view and update name, email, password |

---

### Utility Views

| File | Route | Description |
|---|---|---|
| `NotAuthorized.vue` | `/not-authorized` | Shown when a user navigates to a route their role cannot access |
| `NotFound.vue` | `/:pathMatch(.*)` | 404 fallback |
| `RecentActivityPage.vue` | `/activity` | Standalone activity feed page |
| `Home.vue` | `/` | Landing / redirect page |

---

## Routing

`src/router/index.js` uses Vue Router in **history mode** (requires the `.htaccess` server-side fallback).

Each route has a `meta` object:

```js
meta: {
  requiresAuth: true,   // redirects to /login if no token
  roles: ['admin']      // redirects to /not-authorized if role doesn't match
}
```

The navigation guard (`router.beforeEach`) reads the Pinia `auth` store to enforce these rules before every route change.

---

## State management

`src/stores/auth.js` (Pinia):

| Property | Description |
|---|---|
| `user` | Current user object (id, name, email, role) |
| `token` | Sanctum Bearer token |

Both are persisted to `localStorage` so the session survives a page refresh.

`src/axios.js` — a pre-configured Axios instance. A request interceptor automatically attaches `Authorization: Bearer <token>` from the auth store. A 401 response interceptor clears the store and redirects to `/login`.
