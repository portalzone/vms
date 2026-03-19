# Contributing

Thanks for taking the time to work on this. This document covers the conventions the project follows so that contributions stay consistent and reviews go smoothly.

---

## Getting set up

Follow the instructions in [README.md](./README.md) to get the backend running locally, and the frontend README under `vms-frontend/vue-project/README.md` for the Vue app.

Run the test suite once before you start to confirm everything is green:

```bash
php artisan test
```

---

## Branching

Work off `main`. Create a branch named after what you're doing:

```
feature/trip-export-pdf
fix/duplicate-checkin-on-refresh
chore/update-sanctum
```

Open a pull request against `main` when you're ready for review. Avoid long-running branches — smaller PRs are easier to review and less likely to have merge conflicts.

---

## Commit messages

Keep them short and in the present tense, describing what the commit does rather than what you did:

```
Add vehicle plate number search endpoint
Fix duplicate check-in when browser refreshes mid-request
Update MaintenanceService to handle zero-cost records
```

If the commit is fixing a specific issue, reference it: `Fix check-out timestamp not saving (#42)`.

---

## Backend conventions

### Controllers

- One controller per resource, in `app/Http/Controllers/Api/`.
- Keep controllers thin — validation and response formatting are fine inside them, but anything involving multiple models or transactions belongs in a Service class.
- Use the existing `authorizeAccess()` pattern for role checks. Define the allowed roles per action at the top of the method, not scattered through the logic.

### Validation

- Validate inside the controller using `$request->validate()`.
- Return early with a `response()->json()` for business rule failures (e.g. "vehicle already checked in") rather than throwing exceptions.

### Models

- Add new relationships to the relevant model rather than doing raw joins in controllers.
- If you add a new model that should be audited, add `use LogsActivity` and implement `getActivitylogOptions()` following the pattern in the existing models.
- Keep `$fillable` up to date — don't use `$guarded = []`.

### Services

- Create a Service class for any operation that touches more than one model or needs a database transaction.
- Service methods should be straightforward to call: accept an array of data, return the created/updated model.

### Tests

Every controller change should come with a test. See the `tests/Feature/` directory for examples. Tests use an in-memory SQLite database and don't touch your local `.env`.

```bash
php artisan test                  # run all tests
php artisan test --filter Vehicle # run a specific test class
```

---

## Frontend conventions

### Components

- Reusable components go in `src/components/`. If a component is only used by one view, keep it in that view's folder under `src/views/`.
- Document props and emits with a comment block at the top of the `<script setup>` section.

### API calls

- All API calls go through the Axios instance in `src/axios.js`. Don't create separate Axios instances in individual components.
- Handle loading states and errors in the component making the call, not buried inside a utility function.

### Routing

- Add new routes to `src/router/index.js`.
- Set `meta.requiresAuth: true` on any route that needs a logged-in user, and `meta.role` if it's restricted to a specific role.

---

## What to avoid

- Don't commit `.env` files. `.env.example` is the only env file that should be in version control.
- Don't put credentials, API keys, or real user data in tests or seeders.
- Don't leave `console.log` calls in frontend code before merging.
- Don't use `$guarded = []` in models — be explicit about what's fillable.
- Don't bypass the role checks in controllers "just for now" — it tends to stay.

---

## Questions

If something in the codebase isn't clear, open an issue or ask in the team channel before spending too long guessing. It's also a good sign that the documentation needs improving.
