# VMS Tech Stack — Explained From Scratch

This document teaches every technology used in the VMS project using your **actual code** as the examples. Read it top to bottom the first time. Use it as a reference after that.

---

## Table of Contents

1. [How the whole system works together](#1-how-the-whole-system-works-together)
2. [Laravel — the framework](#2-laravel--the-framework)
3. [Models & Eloquent ORM](#3-models--eloquent-orm)
4. [Migrations — building the database](#4-migrations--building-the-database)
5. [Controllers — handling requests](#5-controllers--handling-requests)
6. [Routes — the URL map](#6-routes--the-url-map)
7. [Services — shared business logic](#7-services--shared-business-logic)
8. [Laravel Sanctum — authentication tokens](#8-laravel-sanctum--authentication-tokens)
9. [Spatie Permission — roles and access control](#9-spatie-permission--roles-and-access-control)
10. [Spatie Activity Log — audit trail](#10-spatie-activity-log--audit-trail)
11. [Vue.js — the frontend framework](#11-vuejs--the-frontend-framework)
12. [Vue Router — page navigation](#12-vue-router--page-navigation)
13. [Pinia — state management](#13-pinia--state-management)
14. [Axios — API communication](#14-axios--api-communication)
15. [How a full request flows end-to-end](#15-how-a-full-request-flows-end-to-end)

---

## 1. How the whole system works together

Before diving into individual pieces, understand the big picture:

```
User opens browser
     ↓
Vue.js SPA loads (HTML + JS from public/)
     ↓
User logs in → Vue calls POST /api/login
     ↓
Laravel validates credentials → returns a TOKEN
     ↓
Vue stores that token in localStorage
     ↓
Every future API call includes: Authorization: Bearer <token>
     ↓
Laravel checks the token (Sanctum) → identifies the user
     ↓
Controller checks the user's role (Spatie) → allows or blocks
     ↓
Controller reads/writes to the MySQL database (Eloquent)
     ↓
Controller returns JSON → Vue updates the screen
```

**There is no page reload.** The browser loads Vue once. After that, everything is JSON going back and forth. This is called a **Single Page Application (SPA)**.

---

## 2. Laravel — the framework

Laravel is a PHP framework. It gives you a structured way to build a web application by organising your code into predictable folders.

### What PHP does here
PHP runs on the server. It never runs in the browser. When the Vue SPA sends a request to `/api/vehicles`, PHP wakes up, runs your code, queries the database, and returns JSON.

### Laravel's folder structure (what matters)
```
app/
  Http/
    Controllers/Api/    ← your controllers live here
  Models/               ← your database table classes live here
  Services/             ← shared business logic lives here
database/
  migrations/           ← instructions for building the database
routes/
  api.php               ← all the URL → controller mappings
```

### The request lifecycle
Every API call goes through this pipeline:
```
HTTP Request
  → public/index.php       (Laravel boots here)
  → Kernel.php             (registers middleware)
  → auth:sanctum           (validates the token)
  → Your Controller        (your logic runs)
  → JSON Response          (sent back to Vue)
```

---

## 3. Models & Eloquent ORM

### What is a Model?
A **Model** is a PHP class that represents a database table. Each row in the table becomes an object you can work with in code.

| Database concept | Laravel concept |
|---|---|
| Table `vehicles` | Model `Vehicle` |
| A row in `vehicles` | An instance of `Vehicle` |
| A column like `plate_number` | A property `$vehicle->plate_number` |

### Your Vehicle model explained line by line

```php
// app/Models/Vehicle.php

namespace App\Models;                      // tells PHP where this class lives

use Illuminate\Database\Eloquent\Model;   // gives us all Eloquent powers
use Spatie\Activitylog\Traits\LogsActivity; // adds automatic logging
use Spatie\Activitylog\LogOptions;

class Vehicle extends Model
{
    use HasFactory, LogsActivity;          // "use" adds extra features to this class

    // $fillable lists columns that are safe to mass-assign
    // Without this, Vehicle::create($data) would fail as a security measure
    protected $fillable = [
        'manufacturer',
        'model',
        'year',
        'plate_number',
        'ownership_type',
        'individual_type',
        'owner_id',
        'created_by',
        'updated_by',
    ];
```

**What is mass assignment?** When you receive form data from a user and want to create a record directly:
```php
Vehicle::create($request->all());   // dangerous without $fillable
Vehicle::create($validated);        // safe — only fillable fields get through
```
`$fillable` is a whitelist. Any column not listed here cannot be set via `create()` or `update()`.

### Relationships

Relationships describe how tables connect. Laravel calls them **Eloquent relationships**.

```php
// In Vehicle model:

public function owner()
{
    return $this->belongsTo(User::class, 'owner_id');
    // "I have an owner_id column. Go find the User with that id."
}

public function driver()
{
    return $this->hasOne(Driver::class);
    // "The drivers table has a vehicle_id column pointing back to me."
}

public function maintenances()
{
    return $this->hasMany(Maintenance::class);
    // "Many maintenance records can belong to this vehicle."
}
```

**The four relationship types:**

| Method | Meaning | Real example in VMS |
|---|---|---|
| `belongsTo` | This table has a foreign key | Vehicle `owner_id` → User |
| `hasOne` | Other table has a FK pointing here, one row | Vehicle → Driver (one driver) |
| `hasMany` | Other table has a FK pointing here, many rows | Vehicle → Maintenances |
| `belongsToMany` | Join table exists between two models | Roles ↔ Users (Spatie handles this) |

### Using relationships in code

```php
// In a controller:

// Load vehicle AND its owner AND its driver in ONE query
$vehicle = Vehicle::with(['owner', 'driver.user'])->find($id);

// Now you can access:
$vehicle->plate_number;            // direct column
$vehicle->owner->name;             // through relationship
$vehicle->driver->user->email;     // nested relationship (driver → user)
```

`with()` is called **eager loading** — it prevents the N+1 problem (loading 10 vehicles and then making 10 separate queries for each owner).

### Your User model explained

```php
// app/Models/User.php

class User extends Authenticatable
{
    // Authenticatable gives Laravel permission to use this model for login
    // HasApiTokens      → Sanctum — allows token creation
    // HasRoles          → Spatie — adds assignRole(), hasRole()
    // LogsActivity      → Spatie Activity Log — auto-logs changes
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $guard_name = 'api';
    // Tells Spatie to check tokens via the 'api' guard (Sanctum), not sessions

    protected $hidden = ['password', 'remember_token'];
    // These columns are NEVER included in JSON responses — security

    protected $casts = [
        'password' => 'hashed',
        // When you set $user->password = 'abc', Laravel auto-hashes it
    ];
}
```

---

## 4. Migrations — building the database

A **migration** is a PHP file that describes how to create or modify a database table. Think of it as version control for your database schema.

### Your vehicles migration explained

```php
// database/migrations/2025_06_17_080444_create_vehicles_table.php

return new class extends Migration
{
    public function up(): void   // runs when you do: php artisan migrate
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();                          // auto-increment primary key: id

            $table->unsignedBigInteger('created_by')->nullable();
            // unsignedBigInteger = a number that's never negative (used for foreign keys)
            // nullable() = this column can be empty (NULL)

            $table->string('plate_number')->unique();
            // unique() = no two rows can have the same plate_number

            $table->integer('year');
            $table->enum('ownership_type', ['organization', 'individual'])->default('organization');
            // enum = only allows specific values
            // default() = value used when nothing is provided

            $table->enum('individual_type', ['staff', 'visitor', 'vehicle_owner'])->nullable();

            $table->timestamps();
            // Adds two columns automatically: created_at and updated_at
            // Laravel updates these for you on every create/update

            // Foreign key: owner_id must exist in the users table
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            // nullOnDelete() = if the user is deleted, set owner_id to NULL (don't crash)
        });
    }

    public function down(): void  // runs when you do: php artisan migrate:rollback
    {
        Schema::dropIfExists('vehicles');
    }
};
```

### Drivers migration — foreign key shortcuts

```php
Schema::create('drivers', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    // foreignId() = shortcut for unsignedBigInteger
    // constrained() = automatically links to the users table id
    // onDelete('cascade') = if the user is deleted, delete their driver record too

    $table->foreignId('vehicle_id')->nullable()->constrained()->onDelete('set null')->unique();
    // unique() = one vehicle can only have ONE driver at a time

    $table->string('license_number')->unique();
    $table->enum('sex', ['male', 'female', 'other']);
    $table->timestamps();
});
```

### Running migrations
```bash
php artisan migrate           # run all pending migrations
php artisan migrate --seed    # run migrations AND seed default data
php artisan migrate:rollback  # undo the last batch
php artisan migrate:fresh     # DROP all tables and re-run from scratch
```

---

## 5. Controllers — handling requests

A **Controller** is a PHP class that receives an HTTP request, runs logic, and returns a response. One method = one endpoint.

### How a controller method works

```
HTTP Request → Route → Controller Method → Response
```

### Your AuthController — login method explained

```php
// app/Http/Controllers/Api/AuthController.php

public function login(Request $request)
{
    // Step 1: Validate the incoming data
    // If validation fails, Laravel automatically returns a 422 JSON error
    $request->validate([
        'email'    => 'required|email',         // must exist, must be an email format
        'password' => 'required|string',        // must exist, must be a string
    ]);

    // Step 2: Find the user by email
    $user = User::where('email', $request->email)->first();
    // where() = SQL WHERE clause
    // first() = get the first matching row, or null if not found

    // Step 3: Check password
    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
        // 401 = Unauthorized HTTP status code
    }

    // Step 4: Create a Sanctum token
    $token = $user->createToken('api-token')->plainTextToken;
    // This creates a row in the personal_access_tokens table
    // plainTextToken is the actual token string Vue will store

    // Step 5: Get the user's role name
    $role = $user->getRoleNames()->first();
    // getRoleNames() comes from Spatie — returns a Collection of role names

    // Step 6: Return JSON response
    return response()->json([
        'token' => $token,
        'user'  => $user->only(['id', 'name', 'email']) + ['role' => $role],
        // only() = return just these columns (not password, remember_token, etc.)
    ]);
    // 200 is the default HTTP status code (OK)
}
```

### Your VehicleController — index method explained

```php
public function index(Request $request)
{
    $this->authorizeAccess('view');
    // Calls the private method below — checks the user's role

    // Start building a query (doesn't hit the database yet)
    $query = Vehicle::with(['owner', 'creator', 'driver.user']);
    // with() = eager load these relationships when we run the query

    // Search filter
    if ($search = $request->input('search')) {
        $query->where(function ($q) use ($search) {
            // where() with a closure groups conditions with parentheses:
            // WHERE (id = ? OR manufacturer LIKE ? OR model LIKE ? OR plate_number LIKE ?)
            $q->where('id', $search)
              ->orWhere('manufacturer', 'like', "%{$search}%");
              // LIKE with % = wildcard search (contains)
        });
    }

    // Role-based filtering — each role sees different vehicles
    $user = auth()->user();  // get the currently logged-in user
    if ($user->hasRole('driver')) {
        $query->whereHas('driver', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
        // whereHas() = only return vehicles WHERE a related driver record exists
        // that matches the condition inside the closure
    } elseif ($user->hasRole('vehicle_owner')) {
        $query->where('owner_id', $user->id);
        // vehicle_owner only sees their own vehicles
    }

    // Sorting
    $sortBy = $request->input('sort_by', 'id');  // default: sort by id
    $sortOrder = $request->input('order', 'asc');
    $query->orderBy($sortBy, $sortOrder);

    // Execute the query with pagination
    return response()->json(
        $query->paginate($request->input('per_page', 15))
        // paginate(15) = return 15 results per page with metadata
        // The response includes: data[], current_page, last_page, total, etc.
    );
}
```

### The `authorizeAccess()` private method

```php
private function authorizeAccess(string $action): void
{
    $user = auth()->user();

    // Map: which roles can perform which actions
    $rolePermissions = [
        'view'   => ['admin', 'manager', 'vehicle_owner', 'gate_security', 'driver'],
        'create' => ['admin', 'manager', 'vehicle_owner', 'gate_security'],
        'update' => ['admin', 'manager', 'vehicle_owner'],
        'delete' => ['admin'],  // only admin can delete
    ];

    $allowedRoles = $rolePermissions[$action] ?? [];

    if (!$user || !$user->hasAnyRole($allowedRoles)) {
        // Log a warning for security monitoring
        \Log::warning("Unauthorized {$action} attempt by user ID {$user?->id}");
        abort(403, 'Unauthorized for this action.');
        // abort() stops execution and returns HTTP 403 Forbidden
    }
}
```

### HTTP status codes you need to know

| Code | Meaning | When VMS uses it |
|---|---|---|
| 200 | OK | Successful GET |
| 201 | Created | Successful POST (new record) |
| 401 | Unauthorized | Wrong password, no token |
| 403 | Forbidden | Token is valid but role is wrong |
| 404 | Not Found | Record doesn't exist |
| 422 | Unprocessable | Validation failed |
| 500 | Server Error | PHP exception / crash |

---

## 6. Routes — the URL map

`routes/api.php` maps URLs to controller methods.

```php
// Public — no token needed
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected — token required (auth:sanctum middleware)
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    // GET /api/me → AuthController::me()

    Route::apiResource('vehicles', VehicleController::class);
    // This one line creates FIVE routes automatically:
    // GET    /api/vehicles          → VehicleController::index()
    // POST   /api/vehicles          → VehicleController::store()
    // GET    /api/vehicles/{id}     → VehicleController::show()
    // PUT    /api/vehicles/{id}     → VehicleController::update()
    // DELETE /api/vehicles/{id}     → VehicleController::destroy()

    // Custom route BEFORE apiResource (order matters!)
    Route::get('/vehicles/mine', [VehicleController::class, 'myVehicles']);
    // GET /api/vehicles/mine → myVehicles()
    // Must be declared before apiResource or Laravel treats 'mine' as an {id}
});
```

### Why `auth:sanctum` middleware?
Middleware runs before your controller method. `auth:sanctum` reads the `Authorization: Bearer <token>` header, looks up the token in `personal_access_tokens`, finds the user, and makes them available via `auth()->user()`. If the token is missing or expired, it returns 401 automatically — your controller never runs.

---

## 7. Services — shared business logic

A **Service** is a plain PHP class that holds logic you want to reuse across multiple controllers, or logic that's too complex to put in a controller.

### Your MaintenanceService

```php
// app/Services/MaintenanceService.php

class MaintenanceService
{
    public function create(array $data): Maintenance
    {
        return DB::transaction(function () use ($data) {
            // DB::transaction() wraps both operations in a single database transaction
            // If EITHER operation fails, BOTH are rolled back
            // This guarantees the database is never in a broken half-state

            // Step 1: Create the maintenance record
            $maintenance = Maintenance::create($data);

            // Step 2: Auto-create the linked expense
            Expense::create([
                'vehicle_id'     => $maintenance->vehicle_id,
                'maintenance_id' => $maintenance->id,
                'description'    => 'Maintenance: ' . $maintenance->description,
                'amount'         => $maintenance->cost,
                'date'           => $maintenance->date,
            ]);

            return $maintenance;
        });
    }
}
```

**Why use a Service instead of putting this in the controller?**
Because `MaintenanceController::store()` just calls `$this->maintenanceService->create($validated)`. The controller stays thin. If you need this logic elsewhere (a future import command, an artisan command), you call the same service — no code duplication.

---

## 8. Laravel Sanctum — authentication tokens

### What Sanctum does
Sanctum issues **API tokens** — random strings that prove who you are. When you log in, Sanctum creates a token, stores a hashed copy in the `personal_access_tokens` table, and gives you the plain text version.

```
personal_access_tokens table:
| id | tokenable_type | tokenable_id | name      | token (hashed) | created_at |
|----|----------------|--------------|-----------|----------------|------------|
| 1  | App\Models\User| 3            | api-token | abc123hash...  | 2026-05-01 |
```

### How login works step by step

```
1. Vue sends: POST /api/login { email, password }
2. AuthController finds the user, checks the password
3. $user->createToken('api-token') → inserts a row in personal_access_tokens
4. Returns the plain text token to Vue
5. Vue stores it: localStorage.setItem('token', token)
6. Every future request: Authorization: Bearer <plain_text_token>
7. Sanctum hashes the incoming token and compares to the stored hash
8. If match → sets auth()->user() → your controller runs
9. If no match → 401 Unauthorized
```

### How logout works

```php
public function logout(Request $request)
{
    $request->user()->tokens()->delete();
    // Deletes ALL tokens for this user from personal_access_tokens
    // The token they send with this request becomes immediately invalid
    return response()->json(['message' => 'Logged out']);
}
```

### The `HasApiTokens` trait
In `User.php`:
```php
use HasApiTokens;
```
This one line adds `createToken()`, `tokens()`, and `currentAccessToken()` to your User model. Without it, Sanctum doesn't know which model to use for tokens.

---

## 9. Spatie Permission — roles and access control

**Spatie laravel-permission** gives you a full role and permission system.

### What it creates in the database
When you run `php artisan migrate`, Spatie creates these tables:
- `roles` — list of role names (admin, manager, driver, etc.)
- `permissions` — list of permission names (if you use them)
- `model_has_roles` — links a user to their role(s)
- `role_has_permissions` — links roles to permissions

### The `HasRoles` trait
In `User.php`:
```php
use HasRoles;
```
This adds:
- `$user->assignRole('admin')` — give a role to a user
- `$user->hasRole('admin')` — returns true/false
- `$user->hasAnyRole(['admin', 'manager'])` — true if user has any of these
- `$user->getRoleNames()` — returns a Collection of role name strings

### How VMS uses roles

```php
// In AuthController::login()
$role = $user->getRoleNames()->first();
// Returns: 'admin', 'manager', 'driver', etc.

// In VehicleController::authorizeAccess()
if (!$user->hasAnyRole(['admin', 'manager'])) {
    abort(403);
}

// In the database seeder (how roles get created):
// Role::create(['name' => 'admin', 'guard_name' => 'api']);
// $user->assignRole('admin');
```

### `guard_name = 'api'`
In `User.php`:
```php
protected $guard_name = 'api';
```
A "guard" is Laravel's term for how authentication is performed. `api` = token-based (Sanctum). `web` = cookie/session-based. Because VMS uses tokens (not cookies), you must tell Spatie to use the `api` guard — otherwise it looks in the wrong place and `hasRole()` always returns false.

---

## 10. Spatie Activity Log — audit trail

**Spatie laravel-activitylog** automatically records every create, update, and delete action on your models.

### Adding it to a model

```php
// In Vehicle.php (and every other model in VMS)

use Spatie\Activitylog\Traits\LogsActivity;

class Vehicle extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()           // log every fillable column
            ->useLogName('vehicle')  // label this log type as 'vehicle'
            ->logOnlyDirty();    // only log fields that actually changed
                                 // (skips unchanged fields on update)
    }
}
```

### What gets stored
Every time a Vehicle is created, updated, or deleted, Spatie inserts a row in `activity_log`:

```
activity_log table row (example — vehicle update):
{
  log_name: 'vehicle',
  description: 'updated',
  subject_type: 'App\Models\Vehicle',
  subject_id: 5,
  causer_type: 'App\Models\User',
  causer_id: 1,             ← who made the change
  properties: {
    attributes: { plate_number: 'NEW-123', model: 'Camry' },
    old:        { plate_number: 'OLD-999', model: 'Camry' }
    ← only the changed fields (logOnlyDirty)
  }
}
```

### Reading the audit trail

```php
// AuditTrailController reads from this table:
$logs = Activity::latest()->paginate(20);
// Activity is the Spatie model for the activity_log table
```

---

## 11. Vue.js — the frontend framework

Vue.js is a JavaScript framework for building user interfaces in the browser. In VMS it's the entire frontend — every screen the user sees.

### The core idea: reactive data

```vue
<template>
  <!-- The HTML template. Uses {{ }} to display data. -->
  <div>
    <h1>{{ title }}</h1>
    <button @click="increment">Clicked {{ count }} times</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

// ref() creates a reactive variable
// When count changes, the HTML updates automatically — no manual DOM manipulation
const count = ref(0)
const title = ref('Hello VMS')

function increment() {
  count.value++   // .value is required when reading/writing ref() variables in JS
                  // In the template, Vue unwraps it automatically (no .value needed)
}
</script>
```

### Props — passing data into a component

```vue
<!-- Parent component -->
<Card title="Total Vehicles" subtitle="Fleet summary">
  {{ vehicleCount }}
</Card>

<!-- Card.vue — receives the data -->
<template>
  <div class="bg-white shadow rounded-2xl p-4">
    <h3>{{ title }}</h3>
    <div class="text-2xl font-bold">
      <slot />    <!-- slot = the content between the tags (vehicleCount here) -->
    </div>
    <div v-if="subtitle" class="text-sm text-gray-500">{{ subtitle }}</div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  subtitle: String
})
</script>
```

**Rule:** Props flow **down** (parent → child). Events flow **up** (child → parent via `$emit`).

### Events — child talking to parent

```vue
<!-- Modal.vue (child) -->
<button @click="$emit('confirm')">Delete</button>
<button @click="$emit('close')">Cancel</button>

<!-- Parent using Modal -->
<Modal
  title="Delete Vehicle?"
  message="This cannot be undone."
  @confirm="deleteVehicle"
  @close="showModal = false"
/>
```

### `v-` directives — the template commands

| Directive | What it does | Example |
|---|---|---|
| `v-if` | Show element only if true | `v-if="isAdmin"` |
| `v-else` | Alternative to v-if | `v-else` |
| `v-for` | Loop and render a list | `v-for="car in vehicles"` |
| `v-model` | Two-way binding (form inputs) | `v-model="email"` |
| `v-bind` / `:` | Bind a JS value to an attribute | `:href="url"` |
| `v-on` / `@` | Listen to an event | `@click="save"` |
| `v-html` | Render raw HTML | `v-html="message"` |
| `v-show` | Toggle visibility (keeps in DOM) | `v-show="loading"` |

### Lifecycle hooks — code that runs at specific moments

```js
import { onMounted, onUnmounted } from 'vue'

onMounted(() => {
  // Runs once after the component appears on screen
  // This is where you fetch data from the API
  loadVehicles()
})

onUnmounted(() => {
  // Runs when the component is removed from the screen
  // Clean up timers, event listeners here
})
```

### `computed` — values derived from other data

```js
import { ref, computed } from 'vue'

const vehicles = ref([])
const search = ref('')

const filteredVehicles = computed(() => {
  // Recalculates automatically whenever vehicles or search changes
  return vehicles.value.filter(v =>
    v.plate_number.includes(search.value)
  )
})
```

---

## 12. Vue Router — page navigation

Vue Router lets a single-page app pretend to have multiple pages by swapping which component is displayed when the URL changes — **without reloading the browser**.

### How routes are defined in VMS

```js
// src/router/index.js

const routes = [
  {
    path: '/vehicles',
    name: 'Vehicles',
    component: VehiclesPage,         // what to show at this URL
    meta: {
      requiresAuth: true,            // user must be logged in
      roles: ['admin', 'manager', 'vehicle_owner', 'gate_security'],
      // only these roles can visit /vehicles
    },
  },
  {
    path: '/vehicles/:id/edit',      // :id is a dynamic parameter
    name: 'VehicleEdit',
    component: VehicleFormPage,
    props: true,                     // passes :id as a prop to the component
  },
  {
    path: '/:pathMatch(.*)*',        // catches everything else
    component: NotFound,             // 404 page
  },
]
```

### The navigation guard — the security checkpoint

This function runs before EVERY route change:

```js
router.beforeEach(async (to, from, next) => {
  // 'to' = the route the user is trying to visit
  // 'from' = the route they're currently on
  // 'next' = call this to allow or redirect

  // 1. If we have a token but no user yet, fetch the user
  if (auth.token && !auth.user) {
    await auth.fetchUser()
  }

  // 2. Route requires auth but user is not logged in → send to login
  if (to.meta.requiresAuth && !auth.user) {
    return next('/login')
  }

  // 3. Guest-only route (login page) but user IS logged in → send to dashboard
  if (to.meta.guestOnly && auth.user) {
    return next('/dashboard')
  }

  // 4. Route has role restriction → check the user's role
  if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
    return next({ name: 'NotAuthorized' })
    // Sends the user to the "You don't have permission" page
  }

  return next()  // allow navigation
})
```

### Lazy loading
```js
const VehiclesPage = () => import('../views/Vehicles/VehiclesPage.vue')
```
This means the JavaScript for `VehiclesPage` is only downloaded when the user actually visits `/vehicles`. This makes the initial page load faster.

### Navigating in code
```js
import { useRouter } from 'vue-router'
const router = useRouter()

// Go to a route by name
router.push({ name: 'Vehicles' })

// Go to a route with a parameter
router.push({ name: 'VehicleEdit', params: { id: 5 } })

// Go back
router.back()
```

---

## 13. Pinia — state management

**Pinia** is the Vue 3 state management library. It solves a specific problem: data that multiple components need to share.

**The problem without Pinia:** the logged-in user's info would need to be passed as props from every parent to every child component — extremely messy.

**With Pinia:** any component can access the auth store directly.

### Your auth store explained

```js
// src/stores/auth.js

export const useAuthStore = defineStore('auth', {
  // STATE — the data stored in the store (like a component's ref() variables)
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    // On page load, restore the token from localStorage if it exists
  }),

  // ACTIONS — functions that change the state
  actions: {
    async login(credentials) {
      const res = await axios.post('/login', credentials)
      const { token, user } = res.data

      this.token = token      // update state
      this.user = user

      localStorage.setItem('token', token)   // persist so refresh doesn't log out
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      // Set the token on the Axios instance so every future request includes it
    },

    async logout(router) {
      await axios.post('/logout')            // tell the server to delete the token
      localStorage.removeItem('token')       // clear from browser storage
      delete axios.defaults.headers.common['Authorization']
      this.token = null
      this.user = null
      if (router) router.push('/')
    },

    async fetchUser() {
      // Called on page refresh — we have a token but no user object yet
      const response = await axios.get('/me')
      this.user = response.data.user
    },

    hasRole(role) {
      return this.user?.role === role
      // ?. = optional chaining: if user is null, returns undefined instead of crashing
    },
  }
})
```

### Using the store in a component

```js
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

// Read state
console.log(auth.user?.name)    // 'Victor'
console.log(auth.token)         // 'abc123...'

// Call an action
await auth.login({ email, password })
auth.logout(router)

// Check role
if (auth.hasRole('admin')) {
  // show admin features
}
```

---

## 14. Axios — API communication

**Axios** is a JavaScript library for making HTTP requests. VMS configures it once in `src/axios.js` and imports it everywhere.

### Your axios configuration explained

```js
// src/axios.js

import axios from 'axios'

const instance = axios.create({
  baseURL: 'https://vms.basepan.com/api',
  // Every request is automatically prefixed with this
  // axios.get('/vehicles') → GET https://vms.basepan.com/api/vehicles
})

// REQUEST INTERCEPTOR — runs before every request is sent
instance.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
    // Automatically attaches the token to EVERY request
    // You never have to add this header manually
  }
  return config   // must return config to continue
})

// RESPONSE INTERCEPTOR — runs when a response comes back
instance.interceptors.response.use(
  (res) => res,   // success: just pass the response through

  (err) => {
    const isLoginAttempt = err.config?.url?.includes('/login')

    if (err.response?.status === 401 && !isLoginAttempt) {
      // 401 on any route except /login means the token expired
      localStorage.removeItem('token')
      window.location.href = '/'   // force redirect to login
    }

    return Promise.reject(err)   // pass the error along so the calling code can handle it
  }
)

export default instance
```

### Making API calls in a component

```js
import axios from '@/axios'   // always import from @/axios, not the raw 'axios' package

// GET request
const res = await axios.get('/vehicles')
const vehicles = res.data.data    // Laravel pagination wraps data in a 'data' key

// GET with parameters
const res = await axios.get('/vehicles', {
  params: { search: 'Toyota', page: 2 }
})
// URL becomes: /api/vehicles?search=Toyota&page=2

// POST request (create)
const res = await axios.post('/vehicles', {
  manufacturer: 'Toyota',
  model: 'Camry',
  year: 2022,
  plate_number: 'ABC-123',
  ownership_type: 'organization',
})

// PUT request (update)
const res = await axios.put(`/vehicles/${id}`, formData)

// DELETE request
await axios.delete(`/vehicles/${id}`)

// Error handling
try {
  const res = await axios.post('/login', credentials)
} catch (err) {
  if (err.response?.status === 401) {
    errorMessage.value = 'Invalid email or password'
  } else if (err.response?.status === 422) {
    // Validation errors
    const errors = err.response.data.errors
    // errors = { email: ['The email field is required.'], ... }
  }
}
```

---

## 15. How a full request flows end-to-end

Let's trace what happens when the admin loads the Vehicles page.

### Step 1 — Vue component mounts

```js
// VehiclesPage.vue
onMounted(() => {
  loadVehicles()
})

async function loadVehicles() {
  const res = await axios.get('/vehicles')
  vehicles.value = res.data.data    // the array of vehicle objects
  pagination.value = res.data.meta  // { current_page, last_page, total }
}
```

### Step 2 — Axios sends the request

```
GET https://vms.basepan.com/api/vehicles
Headers: Authorization: Bearer abc123token...
```

### Step 3 — Apache / .htaccess receives it

```apache
RewriteRule ^api(/.*)?$ index.php [L]
# URL starts with 'api' → send to Laravel's index.php
```

### Step 4 — Laravel routes it

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('vehicles', VehicleController::class);
});
// GET /api/vehicles → VehicleController::index()
```

### Step 5 — Sanctum middleware validates the token

1. Reads `Authorization: Bearer abc123token`
2. Hashes `abc123token`
3. Finds matching row in `personal_access_tokens`
4. Sets `auth()->user()` to the Vehicle owner
5. Passes request to the controller

### Step 6 — VehicleController::index() runs

```php
public function index(Request $request)
{
    $this->authorizeAccess('view');
    // User is vehicle_owner → passes (vehicle_owner is in the 'view' list)

    $query = Vehicle::with(['owner', 'creator', 'driver.user']);

    // Role check: vehicle_owner → filter to only their vehicles
    $user = auth()->user();
    if ($user->hasRole('vehicle_owner')) {
        $query->where('owner_id', $user->id);
    }

    return response()->json($query->paginate(15));
}
```

### Step 7 — Eloquent queries the database

```sql
SELECT vehicles.*, users.name AS owner_name, ...
FROM vehicles
LEFT JOIN users ON vehicles.owner_id = users.id
LEFT JOIN drivers ON drivers.vehicle_id = vehicles.id
WHERE vehicles.owner_id = 3
LIMIT 15 OFFSET 0
```

### Step 8 — Laravel returns JSON

```json
{
  "data": [
    {
      "id": 5,
      "plate_number": "ABC-123",
      "manufacturer": "Toyota",
      "model": "Camry",
      "year": 2022,
      "owner": { "id": 3, "name": "Samuel" },
      "driver": { "id": 2, "user": { "name": "John" } }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "total": 42
  }
}
```

### Step 9 — Vue updates the screen

```js
vehicles.value = res.data.data   // array of vehicles
pagination.value = res.data.meta
// Vue's reactivity system detects the change and re-renders the table
// No page reload. The user just sees the table populate.
```

---

## Quick Reference

### Laravel Eloquent cheat sheet

```php
// Find
Vehicle::find(5)                     // find by primary key
Vehicle::findOrFail(5)               // find or throw 404
Vehicle::where('year', 2022)->get()  // all matching
Vehicle::where('year', 2022)->first() // first matching

// Create / Update / Delete
Vehicle::create($data)               // insert new row
$vehicle->update($data)              // update this row
$vehicle->delete()                   // delete this row

// Relationships
Vehicle::with('owner')->get()                    // eager load
Vehicle::whereHas('driver')->get()               // only vehicles WITH a driver
Vehicle::with(['owner', 'driver.user'])->get()   // nested eager load

// Aggregates
Vehicle::count()
Vehicle::where('year', '>', 2020)->count()
Vehicle::sum('cost')

// Pagination
Vehicle::paginate(15)           // 15 per page
Vehicle::simplePaginate(15)     // faster, no total count
```

### Vue 3 cheat sheet

```js
import { ref, computed, watch, onMounted } from 'vue'

const name = ref('')              // reactive string
const count = ref(0)              // reactive number
const items = ref([])             // reactive array
const user = ref(null)            // reactive object or null

const fullName = computed(() => `${firstName.value} ${lastName.value}`)

watch(search, (newVal, oldVal) => {
  // runs every time search changes
  fetchResults(newVal)
})

onMounted(() => {
  // runs after component appears on screen
})
```

### HTTP verbs and what they mean

| HTTP Verb | Purpose | Example in VMS |
|---|---|---|
| GET | Read data | GET /api/vehicles |
| POST | Create new data | POST /api/vehicles |
| PUT | Replace existing data | PUT /api/vehicles/5 |
| PATCH | Partially update | (VMS uses PUT for everything) |
| DELETE | Remove data | DELETE /api/vehicles/5 |

### Role permissions in VMS

| Action | admin | manager | vehicle_owner | driver | gate_security |
|---|---|---|---|---|---|
| View vehicles | ✅ | ✅ | own only | own only | visitor only |
| Create vehicle | ✅ | ✅ | ✅ | ✗ | ✅ |
| Update vehicle | ✅ | ✅ | ✅ | ✗ | ✗ |
| Delete vehicle | ✅ | ✗ | ✗ | ✗ | ✗ |
| ML Insights | ✅ | ✅ | ✗ | ✗ | ✗ |
| Audit Trail | ✅ | ✅ | ✗ | ✗ | ✗ |
| Delete users | ✅ | ✗ | ✗ | ✗ | ✗ |
