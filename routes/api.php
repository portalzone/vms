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

    Route::get('/driver/me', [DriverController::class, 'me'])->middleware('auth:sanctum');
Route::apiResource('trips', TripController::class)->middleware('auth:sanctum');

    // ✅ Auth/User Info
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', fn(Request $request) => $request->user());
    Route::get('/available-users', [UserController::class, 'availableForDrivers'])->middleware('auth:sanctum');
    Route::get('/users-with-driver-status', [UserController::class, 'usersWithDriverStatus'])->middleware('auth:sanctum');
    Route::get('/users-available-for-drivers', [UserController::class, 'usersAvailableForDriverForm']);

    // trip route


    Route::post('/logout', [AuthController::class, 'logout']);

    // ✅ Roles (used for dropdowns etc)
    Route::get('/roles', [RoleController::class, 'index']);

    // ✅ Dashboard Data
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/trends', [DashboardController::class, 'monthlyTrends']);
    Route::get('/dashboard/activity', [DashboardController::class, 'recentActivity']);

    // ✅ Users (override GET /users/{id} to fix 405 error)
    Route::get('/users/{id}', [UserController::class, 'show']); // 🛠️ Custom show route
    Route::apiResource('users', UserController::class)->except(['show']);

    // ✅ Other Resources
    Route::apiResource('vehicles', VehicleController::class);
    Route::apiResource('drivers', DriverController::class);
    Route::apiResource('maintenances', MaintenanceController::class);
    Route::apiResource('expenses', ExpenseController::class);
    Route::apiResource('checkins', CheckInOutController::class);
    Route::apiResource('trips', TripController::class);

    // Route::apiResource('trips', \App\Http\Controllers\Api\TripController::class);

});
