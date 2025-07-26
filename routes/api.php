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
use App\Http\Controllers\Api\AuditTrailController; // ✅ NEW Import

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
    Route::apiResource('users', UserController::class)->except(['show']);

    // ✅ Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/monthly-trends', [DashboardController::class, 'monthlyTrends']);
    Route::get('/dashboard/recent', [DashboardController::class, 'recentActivity']);

    // ✅ Vehicles
    Route::get('/vehicles/with-drivers', [VehicleController::class, 'withDrivers']);
    Route::get('/vehicles-available-for-drivers', [VehicleController::class, 'availableForDrivers']);
    Route::apiResource('vehicles', VehicleController::class);

    Route::post('/checkins/{id}/checkout', [CheckInOutController::class, 'checkout']);

    // ✅ Drivers
    Route::get('/assigned-vehicles', [VehicleController::class, 'assignedVehicles']);
    Route::get('/vehicles/{vehicle}/driver-user-id', [DriverController::class, 'getDriverUserIdByVehicle']);
    Route::get('/drivers/{id}', [DriverController::class, 'show']);
    Route::get('/drivers/{id}/export-trips-excel', [DriverController::class, 'exportDriverTripsExcel']);
    Route::apiResource('drivers', DriverController::class);

    // ✅ Check-In/Out
    Route::apiResource('checkins', CheckInOutController::class);

    // ✅ Maintenance & Expenses
    Route::get('/vehicles/{id}/maintenances', [MaintenanceController::class, 'byVehicle']);
    Route::apiResource('maintenances', MaintenanceController::class);
    Route::apiResource('expenses', ExpenseController::class);

    // ✅ Trip Logs
    Route::apiResource('trips', TripController::class);

    // ✅ Audit Trail Logs
     Route::get('/audit-trail', [AuditTrailController::class, 'index']);
    Route::get('/audit-trail/{id}', [AuditTrailController::class, 'show']);

    // user profile
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::get('/profile/history', [UserController::class, 'profileHistory']);
});
