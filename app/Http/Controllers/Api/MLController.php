<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MLService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * MLController — exposes machine learning insights from the VMS dataset.
 *
 * All routes require Sanctum authentication.
 * Admin / Manager roles can access fleet-wide endpoints.
 * Vehicle-owner / driver roles are scoped to their own assets.
 */
class MLController extends Controller
{
    public function __construct(private readonly MLService $ml) {}

    // ─── PREDICTIVE MAINTENANCE ───────────────────────────────────────────────

    /**
     * GET /api/ml/maintenance/predict/{vehicleId}
     * Predicts the next maintenance date for one vehicle.
     */
    public function predictMaintenance(int $vehicleId): JsonResponse
    {
        $this->authorizeVehicleAccess($vehicleId);
        return response()->json($this->ml->predictiveMaintenance($vehicleId));
    }

    // ─── FLEET HEALTH SCORE ───────────────────────────────────────────────────

    /**
     * GET /api/ml/health/{vehicleId}
     * Returns the composite health score (0-100) for one vehicle.
     */
    public function vehicleHealth(int $vehicleId): JsonResponse
    {
        $this->authorizeVehicleAccess($vehicleId);
        return response()->json($this->ml->fleetHealthScore($vehicleId));
    }

    /**
     * GET /api/ml/health/fleet
     * Returns ranked health scores for all vehicles (admin / manager only).
     */
    public function fleetHealth(): JsonResponse
    {
        $this->authorizeAdminOrManager();
        return response()->json($this->ml->fleetSummary());
    }

    // ─── EXPENSE ANOMALY DETECTION ────────────────────────────────────────────

    /**
     * GET /api/ml/anomalies/{vehicleId}?threshold=2.0
     * Detects anomalous expenses for a single vehicle using z-score analysis.
     */
    public function vehicleAnomalies(Request $request, int $vehicleId): JsonResponse
    {
        $this->authorizeVehicleAccess($vehicleId);
        $threshold = (float) $request->query('threshold', 2.0);
        return response()->json($this->ml->detectExpenseAnomalies($vehicleId, $threshold));
    }

    /**
     * GET /api/ml/anomalies/fleet
     * Fleet-wide anomaly report (admin / manager only).
     */
    public function fleetAnomalies(): JsonResponse
    {
        $this->authorizeAdminOrManager();
        return response()->json($this->ml->fleetAnomalyReport());
    }

    // ─── DRIVER PERFORMANCE SCORE ─────────────────────────────────────────────

    /**
     * GET /api/ml/driver/{driverId}/score
     * Returns performance score for one driver.
     */
    public function driverScore(int $driverId): JsonResponse
    {
        return response()->json($this->ml->driverPerformanceScore($driverId));
    }

    /**
     * GET /api/ml/driver/scores/all
     * Ranked driver performance scores (admin / manager only).
     */
    public function allDriverScores(): JsonResponse
    {
        $this->authorizeAdminOrManager();
        return response()->json($this->ml->allDriverScores());
    }

    // ─── COST FORECAST ────────────────────────────────────────────────────────

    /**
     * GET /api/ml/forecast/{vehicleId}
     * Forecasts next month's costs for one vehicle using EWMA.
     */
    public function vehicleForecast(int $vehicleId): JsonResponse
    {
        $this->authorizeVehicleAccess($vehicleId);
        return response()->json($this->ml->costForecast($vehicleId));
    }

    /**
     * GET /api/ml/forecast/fleet
     * Fleet-wide cost forecast (admin / manager only).
     */
    public function fleetForecast(): JsonResponse
    {
        $this->authorizeAdminOrManager();
        return response()->json($this->ml->fleetCostForecast());
    }

    // ─── DASHBOARD SUMMARY ────────────────────────────────────────────────────

    /**
     * GET /api/ml/dashboard
     * Single endpoint returning all ML insights for the admin dashboard.
     */
    public function dashboard(): JsonResponse
    {
        $this->authorizeAdminOrManager();

        return response()->json([
            'fleet_health'   => $this->ml->fleetSummary(),
            'fleet_forecast' => $this->ml->fleetCostForecast(),
            'fleet_anomalies'=> $this->ml->fleetAnomalyReport(),
            'driver_scores'  => $this->ml->allDriverScores(),
            'generated_at'   => now()->toIso8601String(),
        ]);
    }

    // ─── AUTHORIZATION HELPERS ────────────────────────────────────────────────

    private function authorizeAdminOrManager(): void
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['admin', 'manager'])) {
            abort(403, 'Only admins and managers can access fleet-wide ML insights.');
        }
    }

    private function authorizeVehicleAccess(int $vehicleId): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthenticated.');
        }

        if ($user->hasAnyRole(['admin', 'manager'])) {
            return;
        }

        if ($user->hasRole('vehicle_owner')) {
            $owns = \App\Models\Vehicle::where('id', $vehicleId)
                ->where('owner_id', $user->id)
                ->exists();
            if (!$owns) {
                abort(403, 'You can only view ML insights for your own vehicles.');
            }
            return;
        }

        if ($user->hasRole('driver')) {
            $assigned = \App\Models\Driver::where('user_id', $user->id)
                ->where('vehicle_id', $vehicleId)
                ->exists();
            if (!$assigned) {
                abort(403, 'You can only view ML insights for your assigned vehicle.');
            }
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
