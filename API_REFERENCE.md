# API Reference

**Production Base URL:** `https://vms.basepan.com/api`  
**Local Base URL:** `http://localhost:8000/api`

All protected endpoints require a bearer token in the `Authorization` header:

```
Authorization: Bearer <token>
```

Tokens are obtained via the login endpoint and remain valid until the user logs out.

---

## Authentication

### POST /login

Authenticates a user and returns an access token.

**Request body**
```json
{
  "email": "admin@example.com",
  "password": "yourpassword"
}
```

**Response `200`**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

**Response `401`** — wrong credentials
```json
{ "message": "Invalid credentials" }
```

---

### POST /register

Creates a new user account. Newly registered users have no role assigned by default.

**Request body**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

**Response `201`**
```json
{
  "token": "2|xyz789...",
  "user": {
    "id": 5,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "role": null
  }
}
```

---

### GET /me

Returns the currently authenticated user with their role.

**Response `200`**
```json
{
  "id": 1,
  "name": "Admin User",
  "email": "admin@example.com",
  "role": "admin"
}
```

---

### POST /logout

Revokes the current access token.

**Response `200`**
```json
{ "message": "Logged out successfully." }
```

---

## Vehicles

### GET /vehicles

Returns a paginated list of vehicles. Results are filtered based on the authenticated user's role:

- `admin` / `manager` — all vehicles
- `vehicle_owner` — only vehicles they own
- `driver` — only the vehicle assigned to them
- `gate_security` — only individual/visitor vehicles

**Query parameters**

| Parameter | Type | Description |
|---|---|---|
| `search` | string | Searches by ID, manufacturer, model, or plate number |
| `ownership_type` | string | `organization` or `individual` |
| `individual_type` | string | `staff`, `visitor`, or `vehicle_owner` (only when `ownership_type=individual`) |
| `driver_id` | integer | Filter by assigned driver ID |
| `sort_by` | string | Column to sort by (default: `id`) |
| `order` | string | `asc` or `desc` (default: `asc`) |
| `per_page` | integer | Results per page (default: `15`) |

**Response `200`** — paginated
```json
{
  "data": [
    {
      "id": 1,
      "manufacturer": "Toyota",
      "model": "Hiace",
      "year": 2021,
      "plate_number": "ABC-123",
      "ownership_type": "organization",
      "individual_type": null,
      "owner_id": null,
      "driver": { "id": 2, "user": { "id": 3, "name": "John Driver" } }
    }
  ],
  "current_page": 1,
  "last_page": 3,
  "total": 42
}
```

---

### POST /vehicles

Creates a new vehicle.

**Roles:** admin, manager, vehicle_owner, gate_security

**Request body**
```json
{
  "manufacturer": "Toyota",
  "model": "Hiace",
  "year": 2021,
  "plate_number": "ABC-123",
  "ownership_type": "individual",
  "individual_type": "vehicle_owner",
  "owner_id": 4
}
```

- `individual_type` is required when `ownership_type` is `individual`
- `owner_id` is required when `individual_type` is `vehicle_owner`

**Response `200`**
```json
{
  "message": "Vehicle created successfully",
  "vehicle": { ... }
}
```

---

### GET /vehicles/{id}

Returns a single vehicle with its driver, owner, and editor details.

**Roles:** admin, manager, vehicle_owner, gate_security, driver

---

### PUT /vehicles/{id}

Updates a vehicle. Same validation rules as create.

**Roles:** admin, manager, vehicle_owner

---

### DELETE /vehicles/{id}

Deletes a vehicle.

**Roles:** admin only

---

### GET /vehicles/with-drivers

Returns all vehicles that have a driver currently assigned.

---

### GET /vehicles/within-premises

Returns vehicles currently inside the premises — check-in records with no check-out time.

---

### GET /vehicles/mine

Returns vehicles owned by the authenticated vehicle_owner.

**Roles:** vehicle_owner only

---

### GET /vehicles/search-by-plate?q={query}

Searches vehicles by plate number. Only returns vehicles that have an assigned driver.

---

### GET /vehicles-available-for-drivers

Returns vehicles not yet assigned to any driver. Pass `?driver_id={id}` to exclude the current driver's vehicle from the "assigned" check (useful when editing a driver record).

---

### GET /vehicles/{vehicle}/driver-user-id

Returns the user ID of the driver assigned to a specific vehicle.

---

## Drivers

### GET /drivers

Returns a paginated list of all driver records with their user and vehicle details.

---

### POST /drivers

Creates a driver profile and links it to a user account and vehicle.

**Roles:** admin, manager

**Request body**
```json
{
  "user_id": 5,
  "vehicle_id": 3,
  "license_number": "LIC-20230101",
  "phone_number": "+234 800 000 0000",
  "home_address": "12 Example Street, City",
  "sex": "male",
  "driver_type": "full_time"
}
```

---

### GET /drivers/{id}

Returns a single driver with their user, vehicle, and trip history.

---

### PUT /drivers/{id}

Updates a driver record.

**Roles:** admin, manager

---

### DELETE /drivers/{id}

Deletes a driver record.

**Roles:** admin

---

### GET /driver/me

Returns the driver profile for the currently authenticated user (must have the driver role).

---

### GET /drivers/{id}/export-trips-excel

Downloads an Excel file of all trips for a specific driver.

---

## Check-In / Check-Out

### GET /checkins

Returns a paginated log of all check-in/out records.

**Roles:** admin, manager, driver, gate_security

**Query parameters**

| Parameter | Type | Description |
|---|---|---|
| `search` | string | Searches by plate number or driver name |
| `per_page` | integer | Results per page (default: `10`) |

---

### POST /checkins

Checks a vehicle in. Automatically resolves the assigned driver from the vehicle ID.

**Roles:** admin, manager, gate_security

**Request body**
```json
{ "vehicle_id": 3 }
```

Returns `422` if the vehicle is already checked in (no open check-out on record).

---

### POST /checkins/{id}/checkout

Closes an open check-in record by recording the check-out time and the user who closed it.

**Roles:** admin, manager, gate_security

---

### GET /checkins/{id}

Returns a single check-in/out record with vehicle and driver details.

---

### PUT /checkins/{id}

Updates a check-in/out record. Typically used to manually set a `checked_out_at` timestamp.

**Roles:** admin, manager, gate_security

---

### DELETE /checkins/{id}

Deletes a record.

**Roles:** admin

---

### GET /checkins/latest?vehicle_id={id}

Returns the most recent check-in record for a given vehicle.

---

## Maintenance

### GET /maintenances

Returns all maintenance records.

---

### POST /maintenances

Creates a maintenance record. A corresponding expense entry is automatically created in the same database transaction.

**Request body**
```json
{
  "vehicle_id": 1,
  "description": "Oil change and filter replacement",
  "status": "pending",
  "cost": 15000.00,
  "date": "2025-08-01"
}
```

Valid statuses: `pending`, `in_progress`, `completed`

---

### GET /maintenances/{id}

Returns a single maintenance record.

---

### PUT /maintenances/{id}

Updates a maintenance record.

---

### DELETE /maintenances/{id}

Deletes a maintenance record.

---

### GET /vehicles/{id}/maintenances

Returns all maintenance records for a specific vehicle.

---

## Expenses

### GET /expenses

Returns all expense records.

---

### POST /expenses

Creates a manual expense (not linked to maintenance).

**Request body**
```json
{
  "vehicle_id": 1,
  "amount": 5000.00,
  "description": "Fuel",
  "date": "2025-08-05"
}
```

---

### GET /expenses/{id}

Returns a single expense record.

---

### PUT /expenses/{id}

Updates an expense record.

---

### DELETE /expenses/{id}

Deletes an expense record.

---

## Incomes

### GET /incomes

Returns all income records.

---

### POST /incomes

Creates an income record linked to a trip, vehicle, and driver.

**Request body**
```json
{
  "vehicle_id": 1,
  "driver_id": 2,
  "trip_id": 10,
  "source": "passenger_fare",
  "amount": 8500.00,
  "description": "Lagos to Abuja",
  "date": "2025-08-06"
}
```

---

### GET /incomes/{id}

Returns a single income record.

---

### PUT /incomes/{id}

Updates an income record.

---

### DELETE /incomes/{id}

Deletes an income record.

---

## Trips

### GET /trips

Returns a paginated list of trips for the authenticated user (filtered by role).

---

### GET /trips/all

Returns all trips without pagination (for exports or full-list views).

---

### POST /trips

Creates a trip log entry.

**Request body**
```json
{
  "driver_id": 2,
  "vehicle_id": 1,
  "start_location": "Lagos",
  "end_location": "Abuja",
  "start_time": "2025-08-06T08:00:00",
  "end_time": "2025-08-06T16:00:00",
  "status": "completed",
  "amount": 8500.00
}
```

---

### GET /trips/{id}

Returns a single trip with driver and vehicle details.

---

### PUT /trips/{id}

Updates a trip.

---

### DELETE /trips/{id}

Deletes a trip.

---

## Users

### GET /users

Returns a paginated list of users.

**Roles:** admin, manager

---

### POST /users

Creates a new user account and assigns a role.

**Roles:** admin

---

### GET /users/{id}

Returns a single user record.

---

### PUT /users/{id}

Updates a user.

**Roles:** admin

---

### DELETE /users/{id}

Deletes a user.

**Roles:** admin

---

### GET /available-users

Returns users who do not yet have a driver profile (usable as a driver candidate).

---

### GET /users-with-driver-status

Returns all users with a flag indicating whether they have a driver profile.

---

### GET /vehicle-owners

Returns all users with the `vehicle_owner` role.

---

### GET /profile

Returns the profile of the currently authenticated user.

---

### PUT /profile

Updates the authenticated user's own profile (name, phone, avatar).

---

### GET /profile/history

Returns the activity log entries for the currently authenticated user.

---

## Dashboard

### GET /dashboard/stats

Returns aggregate counts and sums used to populate the admin/manager dashboard.

**Response `200`**
```json
{
  "vehicles": 24,
  "drivers": 18,
  "expenses": 450000.00,
  "incomes": 920000.00,
  "trips": 310,
  "maintenances": {
    "pending": 3,
    "in_progress": 1,
    "completed": 28
  },
  "vehicles_inside": 7,
  "check_ins_today": 12,
  "check_outs_today": 9,
  "active_trips": 4,
  "total_check_ins": 890
}
```

---

### GET /dashboard/monthly-trends

Returns month-by-month income and expense totals for the current year, used to render the trends chart.

---

### GET /dashboard/recent

Returns the most recent activity across vehicles, drivers, check-ins, trips, and maintenance — used for the activity feed.

---

## Gate Security

### GET /gate-security/stats

Returns stats relevant to gate security: current vehicles inside, today's count, and any open alerts.

---

### GET /gate-security/recent-logs

Returns recent check-in/out records for the gate security dashboard feed.

---

### GET /gate-security/alerts

Returns flagged situations — vehicles that have been on premises unusually long, etc.

---

## Roles

### GET /roles

Returns a list of all available roles.

**Response `200`**
```json
[
  { "id": 1, "name": "admin" },
  { "id": 2, "name": "manager" },
  { "id": 3, "name": "driver" },
  { "id": 4, "name": "gate_security" },
  { "id": 5, "name": "vehicle_owner" },
  { "id": 6, "name": "visitor" },
  { "id": 7, "name": "staff" }
]
```

---

## Audit Trail

### GET /audit-trail

Returns a paginated log of all recorded changes across every model in the system.

**Query parameters**

| Parameter | Type | Description |
|---|---|---|
| `per_page` | integer | Results per page (default: `15`) |
| `search` | string | Search by subject type, causer name, or description |
| `type` | string | Filter by log name (e.g. `vehicle`, `driver`, `trip`) |

---

### GET /audit-trail/{id}

Returns the full detail of a single audit trail entry, including the old and new values for every changed field.

---

## Error responses

All error responses follow this structure:

```json
{
  "message": "A human-readable description of what went wrong."
}
```

Validation errors return `422` with a `errors` object:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "plate_number": ["The plate number field is required."]
  }
}
```

Common status codes used throughout the API:

| Code | Meaning |
|---|---|
| `200` | Success |
| `201` | Resource created |
| `400` | Bad request (e.g. missing required query param) |
| `401` | Not authenticated |
| `403` | Authenticated but not authorised for this action |
| `404` | Resource not found |
| `422` | Validation failed or business rule violated |
| `500` | Server error |

---

## ML Insights

All ML endpoints sit under `/api/ml/` and require `auth:sanctum`.  
Fleet-wide endpoints (`/fleet`, `/dashboard`, `/scores/all`) require the `admin` or `manager` role.  
Per-vehicle and per-driver endpoints are scoped — owners and drivers can only access their own assets.

---

### GET /ml/dashboard

Returns all five ML model outputs in a single call.  
**Role:** admin, manager

**Response `200`**
```json
{
  "fleet_health":    { "fleet_count": 2, "average_health": 93.8, "vehicles": [...] },
  "fleet_forecast":  { "forecast_month": "May 2026", "total_fleet_forecast": 0.00, "vehicles": [...] },
  "fleet_anomalies": { "total_anomalies": 0, "vehicles": [...] },
  "driver_scores":   { "total_drivers": 1, "average_score": 100, "drivers": [...] },
  "generated_at":    "2026-04-23T23:00:24Z"
}
```

---

### GET /ml/maintenance/predict/{vehicleId}

Predicts the next maintenance date for a vehicle using mean-interval regression.

**Response `200`**
```json
{
  "predicted_date":    "2026-06-15",
  "days_until":        52,
  "confidence":        0.87,
  "avg_interval_days": 44.0,
  "std_dev_days":      6.2,
  "last_maintenance":  "2026-04-24",
  "history_count":     5,
  "risk_level":        "Low",
  "message":           "Next maintenance predicted in 52 day(s) with 87% confidence."
}
```

`risk_level` values: `Low` (>30 days) · `Medium` (≤30) · `High` (≤7) · `Critical` (overdue) · `Unknown` (insufficient data)

---

### GET /ml/health/{vehicleId}

Returns the composite health score for one vehicle.

**Response `200`**
```json
{
  "health_score": 100.0,
  "grade": "A – Excellent",
  "breakdown": {
    "maintenance_health": 100.0,
    "trip_completion":    100.0,
    "cost_efficiency":    100.0,
    "availability":       100.0
  },
  "stats": {
    "total_maintenance":    3,
    "completed_maintenance":3,
    "total_trips":          5,
    "completed_trips":      5,
    "total_income":         75000.00,
    "total_expense":        0.00,
    "pending_maintenance":  0
  }
}
```

### GET /ml/health/fleet

Returns health scores for all vehicles, ranked best to worst. **Role:** admin, manager

---

### GET /ml/anomalies/{vehicleId}?threshold=2.0

Detects anomalous expenses using z-score analysis.  
`threshold` — z-score cutoff (default 2.0 = 95th percentile).

**Response `200`**
```json
{
  "anomalies": [
    {
      "id": 12,
      "date": "2026-03-10",
      "amount": 85000.00,
      "description": "Engine overhaul",
      "z_score": 3.41,
      "severity": "High",
      "is_anomaly": true
    }
  ],
  "normal":        [...],
  "mean":          9200.00,
  "std_dev":       4100.00,
  "anomaly_count": 1,
  "total_count":   14,
  "threshold":     2.0,
  "message":       "1 anomalous expense(s) detected."
}
```

`severity` values: `Normal` · `Medium` (z 2–3) · `High` (z 3–4) · `Critical` (z ≥ 4)

### GET /ml/anomalies/fleet

Fleet-wide anomaly report. **Role:** admin, manager

---

### GET /ml/driver/{driverId}/score

Returns the performance score for one driver.

**Response `200`**
```json
{
  "driver_id":         1,
  "driver_name":       "Driver User",
  "performance_score": 100.0,
  "grade":             "A – Outstanding",
  "breakdown": {
    "trip_completion_rate": 100.0,
    "revenue_score":        100.0,
    "safety_score":         100.0
  },
  "stats": {
    "total_trips":         1,
    "completed_trips":     1,
    "incident_free_trips": 1,
    "income_per_trip":     75000.00,
    "fleet_avg_income":    75000.00
  }
}
```

### GET /ml/driver/scores/all

Ranked performance scores for all drivers. **Role:** admin, manager

---

### GET /ml/forecast/{vehicleId}

Forecasts next month's expenses using Exponentially Weighted Moving Average (EWMA).

**Response `200`**
```json
{
  "vehicle_id":      1,
  "forecast_month":  "May 2026",
  "forecasted_cost": 12400.00,
  "trend":           "Increasing",
  "alert_level":     "Normal",
  "history": [
    { "month": "Nov 2025", "amount": 0.00 },
    { "month": "Dec 2025", "amount": 8000.00 },
    { "month": "Jan 2026", "amount": 9500.00 },
    { "month": "Feb 2026", "amount": 11000.00 },
    { "month": "Mar 2026", "amount": 13000.00 },
    { "month": "Apr 2026", "amount": 15000.00 }
  ],
  "method": "Exponentially Weighted Moving Average (EWMA)"
}
```

`alert_level` values: `Normal` · `Medium — forecast is 20%+ above last month` · `High — forecast is 50%+ above last month`

### GET /ml/forecast/fleet

Fleet-wide cost forecast. **Role:** admin, manager
