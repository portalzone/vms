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

// 🔓 Public Routes (No Auth Required)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// 🔒 Authenticated Routes (Token Required via Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // ✅ Fixes "Loading..." issue by supporting /me fetch
    Route::get('/me', [AuthController::class, 'me']);

    // ✅ User info and logout
    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // ✅ Roles
    Route::get('/roles', [RoleController::class, 'index']);

    // ✅ Users
    Route::get('/users/{id}', [UserController::class, 'show']); // Custom show
    Route::get('/available-users', [UserController::class, 'availableForDrivers']);
    Route::get('/users-with-driver-status', [UserController::class, 'usersWithDriverStatus']);
    Route::get('/users-available-for-drivers', [UserController::class, 'usersAvailableForDriverForm']);
    Route::apiResource('users', UserController::class)->except(['show']);

    // ✅ Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/monthly-trends', [DashboardController::class, 'monthlyTrends']);
    Route::get('/dashboard/activity', [DashboardController::class, 'recentActivity']);

    // ✅ Feature modules
    Route::apiResource('vehicles', VehicleController::class);
    Route::apiResource('drivers', DriverController::class);
    Route::get('/driver/me', [DriverController::class, 'me']);

    Route::apiResource('checkins', CheckInOutController::class);
    Route::apiResource('maintenances', MaintenanceController::class);
    Route::apiResource('expenses', ExpenseController::class);
    Route::apiResource('trips', TripController::class);
});
