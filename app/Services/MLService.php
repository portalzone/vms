<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Maintenance;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * MLService — Statistical machine learning algorithms for the VMS.
 *
 * Five core models:
 *  1. Predictive Maintenance  – linear-regression-style interval averaging
 *  2. Fleet Health Score      – weighted composite scoring (0-100)
 *  3. Expense Anomaly Detection – z-score outlier flagging
 *  4. Driver Performance Score – multi-factor scoring (0-100)
 *  5. Cost Forecast           – exponentially-weighted moving average
 */
class MLService
{
    // ─── 1. PREDICTIVE MAINTENANCE ────────────────────────────────────────────

    /**
     * Predict the next maintenance date for a vehicle using the mean interval
     * between historical maintenance events (simple linear regression surrogate).
     *
     * Returns:
     *   predicted_date       – Carbon date string
     *   days_until           – integer days from today
     *   confidence           – 0.0–1.0 (1 = perfectly regular intervals)
     *   avg_interval_days    – mean days between past maintenances
     *   last_maintenance     – date of most recent maintenance
     *   history_count        – number of maintenance events used
     *   risk_level           – "Low" | "Medium" | "High" | "Critical"
     */
    public function predictiveMaintenance(int $vehicleId): array
    {
        $maintenances = Maintenance::where('vehicle_id', $vehicleId)
            ->orderBy('date')
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d));

        if ($maintenances->count() < 2) {
            return [
                'predicted_date'    => null,
                'days_until'        => null,
                'confidence'        => 0,
                'avg_interval_days' => null,
                'last_maintenance'  => $maintenances->last()?->toDateString(),
                'history_count'     => $maintenances->count(),
                'risk_level'        => 'Unknown',
                'message'           => 'Insufficient maintenance history (need at least 2 records).',
            ];
        }

        // Compute intervals between consecutive maintenance dates
        $intervals = [];
        for ($i = 1; $i < $maintenances->count(); $i++) {
            $intervals[] = $maintenances[$i]->diffInDays($maintenances[$i - 1]);
        }

        $mean   = array_sum($intervals) / count($intervals);
        $stdDev = $this->standardDeviation($intervals);

        // Coefficient of variation → confidence (lower CV = higher confidence)
        $cv         = $mean > 0 ? ($stdDev / $mean) : 1;
        $confidence = round(max(0, min(1, 1 - $cv)), 4);

        $lastDate      = $maintenances->last();
        $predictedDate = $lastDate->copy()->addDays((int) round($mean));
        $daysUntil     = (int) now()->diffInDays($predictedDate, false);

        $riskLevel = match (true) {
            $daysUntil < 0    => 'Critical',
            $daysUntil <= 7   => 'High',
            $daysUntil <= 30  => 'Medium',
            default           => 'Low',
        };

        return [
            'predicted_date'    => $predictedDate->toDateString(),
            'days_until'        => $daysUntil,
            'confidence'        => $confidence,
            'avg_interval_days' => round($mean, 1),
            'std_dev_days'      => round($stdDev, 1),
            'last_maintenance'  => $lastDate->toDateString(),
            'history_count'     => $maintenances->count(),
            'risk_level'        => $riskLevel,
            'message'           => "Next maintenance predicted in {$daysUntil} day(s) with " . round($confidence * 100) . "% confidence.",
        ];
    }

    // ─── 2. FLEET HEALTH SCORE ────────────────────────────────────────────────

    /**
     * Composite health score for a single vehicle (0–100).
     *
     * Weights:
     *   Maintenance completion rate  30 %
     *   Trip completion rate         25 %
     *   Cost efficiency ratio        25 %
     *   Vehicle availability         20 %
     */
    public function fleetHealthScore(int $vehicleId): array
    {
        // Maintenance Health
        $totalMaint     = Maintenance::where('vehicle_id', $vehicleId)->count();
        $completedMaint = Maintenance::where('vehicle_id', $vehicleId)
            ->whereIn('status', ['Completed', 'completed'])
            ->count();
        $maintScore = $totalMaint > 0 ? ($completedMaint / $totalMaint) * 100 : 100;

        // Trip Completion
        $totalTrips     = Trip::where('vehicle_id', $vehicleId)->count();
        $completedTrips = Trip::where('vehicle_id', $vehicleId)
            ->where('status', 'completed')
            ->count();
        $tripScore = $totalTrips > 0 ? ($completedTrips / $totalTrips) * 100 : 100;

        // Cost Efficiency  income / (income + expenses)
        $totalIncome  = Income::where('vehicle_id', $vehicleId)->sum('amount');
        $totalExpense = Expense::where('vehicle_id', $vehicleId)->sum('amount');
        $totalFlow    = $totalIncome + $totalExpense;
        $costScore    = $totalFlow > 0 ? ($totalIncome / $totalFlow) * 100 : 50;

        // Availability — penalise vehicles currently under maintenance
        $pendingMaint    = Maintenance::where('vehicle_id', $vehicleId)
            ->whereIn('status', ['Pending', 'pending', 'in_progress'])
            ->count();
        $availScore = $pendingMaint === 0 ? 100 : max(0, 100 - ($pendingMaint * 20));

        $composite = round(
            ($maintScore * 0.30) +
            ($tripScore  * 0.25) +
            ($costScore  * 0.25) +
            ($availScore * 0.20),
            1
        );

        $grade = match (true) {
            $composite >= 85 => 'A – Excellent',
            $composite >= 70 => 'B – Good',
            $composite >= 55 => 'C – Fair',
            $composite >= 40 => 'D – Poor',
            default          => 'F – Critical',
        };

        return [
            'health_score'       => $composite,
            'grade'              => $grade,
            'breakdown'          => [
                'maintenance_health' => round($maintScore, 1),
                'trip_completion'    => round($tripScore, 1),
                'cost_efficiency'    => round($costScore, 1),
                'availability'       => round($availScore, 1),
            ],
            'stats'              => [
                'total_maintenance'   => $totalMaint,
                'completed_maintenance' => $completedMaint,
                'total_trips'         => $totalTrips,
                'completed_trips'     => $completedTrips,
                'total_income'        => $totalIncome,
                'total_expense'       => $totalExpense,
                'pending_maintenance' => $pendingMaint,
            ],
        ];
    }

    /**
     * Run fleetHealthScore across every vehicle and return a ranked list.
     */
    public function fleetSummary(): array
    {
        $vehicles = Vehicle::with(['driver.user'])->get();

        $scores = $vehicles->map(function ($v) {
            $health    = $this->fleetHealthScore($v->id);
            $predict   = $this->predictiveMaintenance($v->id);
            return [
                'vehicle_id'       => $v->id,
                'plate_number'     => $v->plate_number,
                'vehicle'          => "{$v->manufacturer} {$v->model} ({$v->year})",
                'driver'           => $v->driver?->user?->name ?? 'Unassigned',
                'health_score'     => $health['health_score'],
                'grade'            => $health['grade'],
                'next_maintenance' => $predict['predicted_date'],
                'days_until_maintenance' => $predict['days_until'],
                'risk_level'       => $predict['risk_level'],
            ];
        })->sortByDesc('health_score')->values();

        return [
            'fleet_count'     => $vehicles->count(),
            'average_health'  => round($scores->avg('health_score'), 1),
            'critical_count'  => $scores->where('risk_level', 'Critical')->count(),
            'high_risk_count' => $scores->where('risk_level', 'High')->count(),
            'vehicles'        => $scores,
        ];
    }

    // ─── 3. EXPENSE ANOMALY DETECTION ─────────────────────────────────────────

    /**
     * Flag unusual expense records for a vehicle using z-score analysis.
     * An expense is anomalous when |z| > threshold (default 2.0 = ~95 % CI).
     */
    public function detectExpenseAnomalies(int $vehicleId, float $threshold = 2.0): array
    {
        $expenses = Expense::where('vehicle_id', $vehicleId)
            ->with(['maintenance', 'creator'])
            ->orderBy('date')
            ->get();

        if ($expenses->count() < 3) {
            return [
                'anomalies'    => [],
                'normal'       => $expenses->toArray(),
                'mean'         => null,
                'std_dev'      => null,
                'total_count'  => $expenses->count(),
                'anomaly_count'=> 0,
                'threshold'    => $threshold,
                'message'      => 'Not enough data for anomaly detection (minimum 3 records required).',
            ];
        }

        $amounts = $expenses->pluck('amount')->map(fn($a) => (float) $a)->toArray();
        $mean    = array_sum($amounts) / count($amounts);
        $stdDev  = $this->standardDeviation($amounts);

        $anomalies = [];
        $normal    = [];

        foreach ($expenses as $expense) {
            $amount  = (float) $expense->amount;
            $zScore  = $stdDev > 0 ? ($amount - $mean) / $stdDev : 0;
            $isAnomaly = abs($zScore) > $threshold;

            $record = [
                'id'          => $expense->id,
                'date'        => $expense->date,
                'amount'      => $amount,
                'description' => $expense->description,
                'z_score'     => round($zScore, 3),
                'is_anomaly'  => $isAnomaly,
                'severity'    => $this->anomalySeverity($zScore),
                'created_by'  => $expense->creator?->name,
            ];

            if ($isAnomaly) {
                $anomalies[] = $record;
            } else {
                $normal[] = $record;
            }
        }

        return [
            'anomalies'      => $anomalies,
            'normal'         => $normal,
            'mean'           => round($mean, 2),
            'std_dev'        => round($stdDev, 2),
            'threshold'      => $threshold,
            'total_count'    => $expenses->count(),
            'anomaly_count'  => count($anomalies),
            'message'        => count($anomalies) > 0
                ? count($anomalies) . ' anomalous expense(s) detected.'
                : 'No expense anomalies detected.',
        ];
    }

    /**
     * Fleet-wide anomaly report across all vehicles.
     */
    public function fleetAnomalyReport(): array
    {
        $vehicles = Vehicle::all();
        $report   = [];
        $totalAnomalies = 0;

        foreach ($vehicles as $vehicle) {
            $result = $this->detectExpenseAnomalies($vehicle->id);
            $totalAnomalies += $result['anomaly_count'];
            $report[] = [
                'vehicle_id'     => $vehicle->id,
                'vehicle'        => "{$vehicle->manufacturer} {$vehicle->model} ({$vehicle->plate_number})",
                'anomaly_count'  => $result['anomaly_count'],
                'total_expenses' => $result['total_count'],
                'mean_expense'   => $result['mean'],
                'anomalies'      => $result['anomalies'],
            ];
        }

        return [
            'total_anomalies' => $totalAnomalies,
            'vehicles'        => collect($report)->sortByDesc('anomaly_count')->values(),
        ];
    }

    // ─── 4. DRIVER PERFORMANCE SCORE ─────────────────────────────────────────

    /**
     * Multi-factor driver performance score (0–100).
     *
     * Weights:
     *   Trip completion rate   40 %
     *   Revenue per trip       35 %
     *   Incident-free trips    25 %
     */
    public function driverPerformanceScore(int $driverId): array
    {
        $driver = Driver::with(['user', 'vehicle', 'trips'])->find($driverId);

        if (!$driver) {
            return ['error' => 'Driver not found.'];
        }

        $trips = Trip::where('driver_id', $driverId)->with(['income'])->get();

        // Trip Completion
        $totalTrips     = $trips->count();
        $completedTrips = $trips->where('status', 'completed')->count();
        $completionRate = $totalTrips > 0 ? ($completedTrips / $totalTrips) : 0;

        // Revenue Generation — average income per completed trip, normalized vs fleet average
        $incomePerTrip = $completedTrips > 0
            ? Income::where('driver_id', $driverId)->sum('amount') / $completedTrips
            : 0;

        $fleetAvgIncomePerTrip = $this->fleetAvgIncomePerTrip();
        $revenueScore = $fleetAvgIncomePerTrip > 0
            ? min(1, $incomePerTrip / $fleetAvgIncomePerTrip)
            : ($incomePerTrip > 0 ? 1 : 0);

        // Incident-free — completed trips that did NOT coincide with a pending maintenance event
        // Proxy: trips where the vehicle had no maintenance logged on the same day
        $incidentFree  = 0;
        foreach ($trips->where('status', 'completed') as $trip) {
            $maintenanceOnTripDay = Maintenance::where('vehicle_id', $trip->vehicle_id)
                ->whereDate('date', Carbon::parse($trip->start_time)->toDateString())
                ->whereIn('status', ['Pending', 'pending'])
                ->count();
            if ($maintenanceOnTripDay === 0) {
                $incidentFree++;
            }
        }
        $safetyScore = $completedTrips > 0 ? ($incidentFree / $completedTrips) : 1;

        $composite = round(
            ($completionRate * 0.40 +
             $revenueScore  * 0.35 +
             $safetyScore   * 0.25) * 100,
            1
        );

        $grade = match (true) {
            $composite >= 85 => 'A – Outstanding',
            $composite >= 70 => 'B – Good',
            $composite >= 55 => 'C – Average',
            $composite >= 40 => 'D – Needs Improvement',
            default          => 'F – Underperforming',
        };

        return [
            'driver_id'       => $driverId,
            'driver_name'     => $driver->user?->name,
            'performance_score' => $composite,
            'grade'           => $grade,
            'breakdown'       => [
                'trip_completion_rate' => round($completionRate * 100, 1),
                'revenue_score'        => round($revenueScore * 100, 1),
                'safety_score'         => round($safetyScore * 100, 1),
            ],
            'stats'           => [
                'total_trips'          => $totalTrips,
                'completed_trips'      => $completedTrips,
                'incident_free_trips'  => $incidentFree,
                'income_per_trip'      => round($incomePerTrip, 2),
                'fleet_avg_income'     => round($fleetAvgIncomePerTrip, 2),
            ],
        ];
    }

    /**
     * Rank all drivers by performance score.
     */
    public function allDriverScores(): array
    {
        $drivers = Driver::with('user')->get();

        $scores = $drivers->map(function ($d) {
            return $this->driverPerformanceScore($d->id);
        })->filter(fn($s) => !isset($s['error']))
          ->sortByDesc('performance_score')
          ->values();

        return [
            'total_drivers'    => $drivers->count(),
            'average_score'    => round($scores->avg('performance_score'), 1),
            'top_performer'    => $scores->first(),
            'drivers'          => $scores,
        ];
    }

    // ─── 5. COST FORECAST ─────────────────────────────────────────────────────

    /**
     * Forecast next month's expenses for a vehicle using an exponentially
     * weighted moving average (EWMA) over the past 6 months.
     *
     * Weights (newest to oldest): 0.30, 0.20, 0.15, 0.15, 0.10, 0.10
     */
    public function costForecast(int $vehicleId, int $months = 6): array
    {
        $weights = [0.30, 0.20, 0.15, 0.15, 0.10, 0.10];
        $history = [];

        for ($i = 0; $i < $months; $i++) {
            $date  = now()->subMonths($i);
            $total = Expense::where('vehicle_id', $vehicleId)
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('amount');

            $history[] = [
                'month'  => $date->format('M Y'),
                'amount' => (float) $total,
            ];
        }

        $amounts = array_column($history, 'amount'); // newest first
        $weightedSum  = 0;
        $weightTotal  = 0;

        for ($i = 0; $i < min(count($amounts), count($weights)); $i++) {
            $weightedSum += $amounts[$i] * $weights[$i];
            $weightTotal += $weights[$i];
        }

        $forecast = $weightTotal > 0 ? round($weightedSum / $weightTotal, 2) : 0;
        $trend    = $this->detectTrend($amounts);

        // Simple budget alert
        $lastMonth  = $amounts[0] ?? 0;
        $alertLevel = match (true) {
            $forecast > ($lastMonth * 1.5) => 'High — forecast is 50%+ above last month',
            $forecast > ($lastMonth * 1.2) => 'Medium — forecast is 20%+ above last month',
            default                         => 'Normal',
        };

        return [
            'vehicle_id'      => $vehicleId,
            'forecast_month'  => now()->addMonth()->format('M Y'),
            'forecasted_cost' => $forecast,
            'trend'           => $trend,
            'alert_level'     => $alertLevel,
            'history'         => array_reverse($history), // oldest first for display
            'method'          => 'Exponentially Weighted Moving Average (EWMA)',
        ];
    }

    /**
     * Fleet-wide cost forecast summary.
     */
    public function fleetCostForecast(): array
    {
        $vehicles = Vehicle::all();
        $forecasts = $vehicles->map(fn($v) => $this->costForecast($v->id));

        return [
            'forecast_month'        => now()->addMonth()->format('M Y'),
            'total_fleet_forecast'  => round($forecasts->sum('forecasted_cost'), 2),
            'high_alert_vehicles'   => $forecasts->filter(fn($f) => str_starts_with($f['alert_level'], 'High'))->count(),
            'vehicles'              => $forecasts->sortByDesc('forecasted_cost')->values(),
        ];
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    private function standardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $values)) / $count;
        return sqrt($variance);
    }

    private function anomalySeverity(float $zScore): string
    {
        $abs = abs($zScore);
        return match (true) {
            $abs >= 4  => 'Critical',
            $abs >= 3  => 'High',
            $abs >= 2  => 'Medium',
            default    => 'Normal',
        };
    }

    private function detectTrend(array $amounts): string
    {
        if (count($amounts) < 3) {
            return 'Insufficient data';
        }
        $recent   = array_slice($amounts, 0, 3);
        $older    = array_slice($amounts, 3);
        $recentAvg = array_sum($recent) / count($recent);
        $olderAvg  = count($older) > 0 ? array_sum($older) / count($older) : $recentAvg;

        if ($olderAvg == 0) {
            return 'Stable';
        }
        $change = (($recentAvg - $olderAvg) / $olderAvg) * 100;

        return match (true) {
            $change >  15 => 'Increasing',
            $change < -15 => 'Decreasing',
            default       => 'Stable',
        };
    }

    private function fleetAvgIncomePerTrip(): float
    {
        $completedTrips = Trip::where('status', 'completed')->count();
        if ($completedTrips === 0) {
            return 0;
        }
        $totalIncome = Income::sum('amount');
        return $totalIncome / $completedTrips;
    }
}
