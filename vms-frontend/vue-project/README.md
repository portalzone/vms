# Vehicle Management System — Frontend

The Vue 3 single-page application that talks to the VMS backend API. Handles everything from the login screen through to dashboards, vehicle management, driver profiles, trip logs, gate check-ins, expenses, maintenance tracking, and the audit trail.

---

## Stack

| | |
|---|---|
| Framework | Vue 3 (Composition API) |
| Build tool | Vite |
| Routing | Vue Router 4 |
| State management | Pinia |
| HTTP client | Axios |
| Styling | Tailwind CSS |
| Linting | ESLint + Prettier |

---

## Getting started

### Requirements

- Node.js 18 or newer
- npm

### Installation

```bash
cd vms-frontend/vue-project

npm install
```

### Configure the API base URL

Open `src/axios.js` (or `src/plugins/axios.js`) and point it at your backend:

```js
// src/axios.js
import axios from 'axios'

const instance = axios.create({
  baseURL: 'http://localhost:8000/api',  // change this for staging / production
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})
```

If you're running the backend with `php artisan serve`, the default `http://localhost:8000/api` should work out of the box.

### Run the dev server

```bash
npm run dev
```

The app will be available at `http://localhost:5173`.

---

## Building for production

```bash
npm run build
```

Output goes to `dist/`. Point your web server at that folder or let the Dockerfile handle it.

---

## Project structure

```
src/
  axios.js              — Axios instance with base URL and auth token interceptor
  plugins/axios.js      — Axios plugin registered in main.js
  router/index.js       — All routes, with role-based navigation guards
  stores/
    auth.js             — Pinia store: user, token, login/logout actions
    counter.js          — Example Pinia store (unused in production)
  layouts/
    AuthenticatedLayout.vue   — Shell with sidebar for logged-in users
    GuestLayout.vue           — Bare layout for login/register pages
  components/
    Card.vue            — Reusable stat/info card
    Modal.vue           — Generic confirmation/form modal
    ModalNotification.vue
    Notification.vue
    Pagination.vue      — Page controls for paginated API responses
    RecentActivity.vue  — Activity feed component
    SideBar.vue         — Main navigation sidebar
    StatsChart.vue      — Dashboard stats chart
    Toast.vue           — Transient success/error notifications
    TrendsChart.vue     — Monthly trends chart
    VehicleTable.vue    — Reusable vehicle listing table
    icons/              — SVG icon components (per module)
  views/
    Login.vue / Register.vue
    Dashboard.vue       — Role-aware dashboard landing page
    AdminDashboard.vue
    DriverDashboard.vue
    GateSecurityDashboard.vue
    VehicleOwnerDashboard.vue
    UserDashboard.vue
    Vehicles/           — List, form, detail views for vehicles
    Drivers/            — Driver list, profile, and form pages
    CheckIns/           — Gate check-in/out pages
    Trips/              — Trip log views
    Maintenances/       — Maintenance record pages
    Expenses/           — Expense tracking pages
    Income/             — Income record pages
    Audit/              — Audit trail viewer
    Users/              — User management (admin only)
    Profile/            — User profile page
```

---

## Authentication

Login returns a Sanctum token that gets stored in `localStorage` and attached to every subsequent request via an Axios request interceptor. The Pinia auth store (`stores/auth.js`) manages the current user object and exposes a `logout()` action that clears both the token and the store.

Route guards in `router/index.js` check the user's role before allowing navigation to protected views.

---

## Linting and formatting

```bash
# Check for lint errors
npm run lint

# Format with Prettier
npx prettier --write src/
```

---

## Notes

- The app uses `localStorage` to persist the auth token across page refreshes. Clearing site data or calling logout removes it.
- Role-based routing is handled purely on the frontend. The backend independently enforces the same role rules on every API request, so a user can't get data they aren't allowed to see even if they manually navigate to a URL.
- The `HelloWorld.vue` and `counter.js` files are leftover Vue CLI scaffolding and aren't used in the actual app.
