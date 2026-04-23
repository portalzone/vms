<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\CheckInOutController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\AuditTrailController;
use App\Http\Controllers\Api\GateSecurityController;
use App\Http\Controllers\Api\IncomeController;
use App\Http\Controllers\Api\MLController;

// 🔓 Public Routes (No Auth Required)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// 🔒 Authenticated Routes (Token Required via Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // ✅ Auth & Profile
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // ✅ Roles
    Route::get('/roles', [RoleController::class, 'index']);

    // ✅ Users
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::get('/available-users', [UserController::class, 'availableForDrivers']);
    Route::get('/users-with-driver-status', [UserController::class, 'usersWithDriverStatus']);
    Route::get('/users-available-for-drivers', [UserController::class, 'availableForDrivers']);
    Route::get('/vehicle-owners', [UserController::class, 'vehicleOwners']);
    Route::apiResource('users', UserController::class)->except(['show']);

    // ✅ Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/monthly-trends', [DashboardController::class, 'monthlyTrends']);
    Route::get('/dashboard/recent', [DashboardController::class, 'recentActivity']);

    //Gate security
    Route::get('/gate-security/stats', [GateSecurityController::class, 'stats']);
    Route::get('/gate-security/recent-logs', [GateSecurityController::class, 'recentLogs']);
    Route::get('/gate-security/alerts', [GateSecurityController::class, 'alerts']);
    // Route::get('/vehicles/within-premises', [GateSecurityController::class, 'vehiclesWithinPremises']);

    // ✅ Vehicles
    Route::get('/vehicles/with-drivers', [VehicleController::class, 'withDrivers']);
    Route::get('/vehicles-available-for-drivers', [VehicleController::class, 'availableForDrivers']);
    Route::get('/assigned-vehicles', [VehicleController::class, 'assignedVehicles']);
    Route::get('/vehicles/mine', [VehicleController::class, 'myVehicles']);
    Route::get('/vehicles/within-premises', [VehicleController::class, 'vehiclesWithinPremises']);
    Route::get('/vehicles/search-by-plate', [VehicleController::class, 'searchByPlate']);

    Route::apiResource('vehicles', VehicleController::class);

    // ✅ Drivers
    Route::get('/drivers/{id}', [DriverController::class, 'show']);
    Route::get('/drivers/{id}/export-trips-excel', [DriverController::class, 'exportDriverTripsExcel']);
    Route::get('/vehicles/{vehicle}/driver-user-id', [DriverController::class, 'getDriverUserIdByVehicle']);
    Route::get('/driver/me', [DriverController::class, 'me']);
    Route::apiResource('drivers', DriverController::class);

    // ✅ Check-In/Out
    Route::post('/checkins/{id}/checkout', [CheckInOutController::class, 'checkout']);
    Route::get('/checkins/latest', [CheckInOutController::class, 'latest']);
    Route::apiResource('checkins', CheckInOutController::class);

    // ✅ Maintenance & Expenses
    Route::get('/vehicles/{id}/maintenances', [MaintenanceController::class, 'byVehicle']);
    Route::apiResource('maintenances', MaintenanceController::class);
    Route::apiResource('expenses', ExpenseController::class);


    // income
    Route::apiResource('incomes', IncomeController::class);

    // ✅ Trip Logs
    Route::get('/trips/all', [TripController::class, 'all']);
    Route::apiResource('trips', TripController::class);

    // ✅ Audit Trail Logs
    Route::get('/audit-trail', [AuditTrailController::class, 'index']);
    Route::get('/audit-trail/{id}', [AuditTrailController::class, 'show']);

    // ✅ User Profile
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::get('/profile/history', [UserController::class, 'profileHistory']);

    // ✅ ML / AI Insights
    Route::prefix('ml')->group(function () {
        // Fleet-wide (admin / manager)
        Route::get('/dashboard',           [MLController::class, 'dashboard']);
        Route::get('/health/fleet',        [MLController::class, 'fleetHealth']);
        Route::get('/anomalies/fleet',     [MLController::class, 'fleetAnomalies']);
        Route::get('/driver/scores/all',   [MLController::class, 'allDriverScores']);
        Route::get('/forecast/fleet',      [MLController::class, 'fleetForecast']);

        // Per-vehicle
        Route::get('/maintenance/predict/{vehicleId}', [MLController::class, 'predictMaintenance']);
        Route::get('/health/{vehicleId}',              [MLController::class, 'vehicleHealth']);
        Route::get('/anomalies/{vehicleId}',           [MLController::class, 'vehicleAnomalies']);
        Route::get('/forecast/{vehicleId}',            [MLController::class, 'vehicleForecast']);

        // Per-driver
        Route::get('/driver/{driverId}/score',         [MLController::class, 'driverScore']);
    });
});
