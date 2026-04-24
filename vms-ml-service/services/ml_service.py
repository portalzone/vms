"""
VMS Python ML Service
=====================
Five production-grade ML algorithms using real Python libraries.

PHP version used manual statistics.
This version uses:
  - numpy          : array maths (mean, std, z-score)
  - pandas         : data wrangling and time-series grouping
  - scipy.stats    : z-score, normal distribution
  - scikit-learn   : IsolationForest (anomaly), LinearRegression (maintenance)
  - statsmodels    : EWMA via Holt-Winters ExponentialSmoothing
"""

import numpy as np
import pandas as pd
from datetime import date, datetime, timedelta
from typing import Optional

from scipy import stats as scipy_stats
from sklearn.ensemble import IsolationForest
from sklearn.linear_model import LinearRegression
from sklearn.preprocessing import MinMaxScaler
from statsmodels.tsa.holtwinters import ExponentialSmoothing

from sqlalchemy.orm import Session
from sqlalchemy import func

from models import Vehicle, Driver, Maintenance, Expense, Trip, Income


# ─── 1. PREDICTIVE MAINTENANCE ────────────────────────────────────────────────

def predictive_maintenance(vehicle_id: int, db: Session) -> dict:
    """
    Predict next maintenance date using scikit-learn LinearRegression on
    cumulative days — more accurate than simple mean-interval averaging.

    Steps:
      1. Load all maintenance dates for the vehicle
      2. Convert to cumulative days from first service
      3. Fit LinearRegression: X = record index, y = cumulative days
      4. Extrapolate to the next record to get predicted cumulative days
      5. Convert back to a calendar date
      6. Compute confidence from R² score (1.0 = perfect linear trend)
    """
    records = (
        db.query(Maintenance.date)
        .filter(Maintenance.vehicle_id == vehicle_id)
        .order_by(Maintenance.date)
        .all()
    )

    dates = [r.date for r in records]

    if len(dates) < 2:
        return {
            "predicted_date": None,
            "days_until": None,
            "confidence": 0.0,
            "avg_interval_days": None,
            "std_dev_days": None,
            "last_maintenance": dates[-1].isoformat() if dates else None,
            "history_count": len(dates),
            "risk_level": "Unknown",
            "model_used": "LinearRegression (sklearn)",
            "message": "Insufficient data — need at least 2 maintenance records.",
        }

    # Convert dates to cumulative day numbers from the first service
    origin = dates[0]
    cumulative_days = np.array([(d - origin).days for d in dates], dtype=float)
    indices         = np.arange(len(cumulative_days)).reshape(-1, 1)

    # Fit linear regression: index → cumulative days
    model = LinearRegression()
    model.fit(indices, cumulative_days)
    r_squared = model.score(indices, cumulative_days)

    # Predict the NEXT index
    next_index      = np.array([[len(cumulative_days)]])
    next_cumulative = model.predict(next_index)[0]
    predicted_date  = origin + timedelta(days=int(round(next_cumulative)))

    # Average and std dev of gaps (for display)
    gaps    = np.diff(cumulative_days)
    avg_gap = float(np.mean(gaps))
    std_gap = float(np.std(gaps))

    days_until = (predicted_date - date.today()).days

    risk_level = (
        "Critical" if days_until < 0  else
        "High"     if days_until <= 7  else
        "Medium"   if days_until <= 30 else
        "Low"
    )

    return {
        "predicted_date":    predicted_date.isoformat(),
        "days_until":        days_until,
        "confidence":        round(float(r_squared), 4),
        "avg_interval_days": round(avg_gap, 1),
        "std_dev_days":      round(std_gap, 1),
        "last_maintenance":  dates[-1].isoformat(),
        "history_count":     len(dates),
        "risk_level":        risk_level,
        "model_used":        "LinearRegression (sklearn)",
        "r_squared":         round(float(r_squared), 4),
        "message": (
            f"Next maintenance predicted on {predicted_date.isoformat()} "
            f"({days_until} days). R²={r_squared:.2f}."
        ),
    }


# ─── 2. FLEET HEALTH SCORE ────────────────────────────────────────────────────

def fleet_health_score(vehicle_id: int, db: Session) -> dict:
    """
    Weighted composite health score (0-100) using pandas aggregations.

    Weights: maintenance 30%, trips 25%, cost efficiency 25%, availability 20%
    """
    # Maintenance health
    maint_df = pd.read_sql(
        db.query(Maintenance.status).filter(Maintenance.vehicle_id == vehicle_id).statement,
        db.bind
    )
    total_maint     = len(maint_df)
    completed_maint = maint_df["status"].str.lower().isin(["completed"]).sum()
    maint_score     = (completed_maint / total_maint * 100) if total_maint > 0 else 100.0

    # Trip completion
    trip_df = pd.read_sql(
        db.query(Trip.status).filter(Trip.vehicle_id == vehicle_id).statement,
        db.bind
    )
    total_trips     = len(trip_df)
    completed_trips = (trip_df["status"] == "completed").sum()
    trip_score      = (completed_trips / total_trips * 100) if total_trips > 0 else 100.0

    # Cost efficiency
    total_income  = db.query(func.sum(Income.amount)).filter(Income.vehicle_id == vehicle_id).scalar() or 0.0
    total_expense = db.query(func.sum(Expense.amount)).filter(Expense.vehicle_id == vehicle_id).scalar() or 0.0
    total_flow    = total_income + total_expense
    cost_score    = (total_income / total_flow * 100) if total_flow > 0 else 50.0

    # Availability
    pending_maint = maint_df["status"].str.lower().isin(["pending", "in_progress"]).sum()
    avail_score   = max(0.0, 100.0 - float(pending_maint) * 20)

    # Weighted composite
    composite = round(
        maint_score * 0.30 +
        trip_score  * 0.25 +
        cost_score  * 0.25 +
        avail_score * 0.20,
        1
    )

    grade = (
        "A – Excellent" if composite >= 85 else
        "B – Good"      if composite >= 70 else
        "C – Fair"      if composite >= 55 else
        "D – Poor"      if composite >= 40 else
        "F – Critical"
    )

    return {
        "health_score": composite,
        "grade": grade,
        "breakdown": {
            "maintenance_health": round(float(maint_score), 1),
            "trip_completion":    round(float(trip_score),  1),
            "cost_efficiency":    round(float(cost_score),  1),
            "availability":       round(float(avail_score), 1),
        },
        "stats": {
            "total_maintenance":    int(total_maint),
            "completed_maintenance":int(completed_maint),
            "total_trips":          int(total_trips),
            "completed_trips":      int(completed_trips),
            "total_income":         float(total_income),
            "total_expense":        float(total_expense),
            "pending_maintenance":  int(pending_maint),
        },
    }


def fleet_summary(db: Session) -> dict:
    """Run fleet_health_score and predictive_maintenance for every vehicle."""
    vehicles = db.query(Vehicle).all()
    scores   = []

    for v in vehicles:
        health  = fleet_health_score(v.id, db)
        predict = predictive_maintenance(v.id, db)
        driver  = v.drivers[0].user_id if v.drivers else None

        scores.append({
            "vehicle_id":             v.id,
            "plate_number":           v.plate_number,
            "vehicle":                f"{v.manufacturer} {v.model} ({v.year})",
            "health_score":           health["health_score"],
            "grade":                  health["grade"],
            "next_maintenance":       predict["predicted_date"],
            "days_until_maintenance": predict["days_until"],
            "risk_level":             predict["risk_level"],
            "r_squared":              predict.get("r_squared"),
        })

    scores.sort(key=lambda x: x["health_score"], reverse=True)
    avg_health = round(np.mean([s["health_score"] for s in scores]), 1) if scores else 0

    return {
        "fleet_count":     len(vehicles),
        "average_health":  avg_health,
        "critical_count":  sum(1 for s in scores if s["risk_level"] == "Critical"),
        "high_risk_count": sum(1 for s in scores if s["risk_level"] == "High"),
        "vehicles":        scores,
    }


# ─── 3. EXPENSE ANOMALY DETECTION ─────────────────────────────────────────────

def detect_expense_anomalies(
    vehicle_id: int,
    db: Session,
    method: str = "isolation_forest",
    z_threshold: float = 2.0,
) -> dict:
    """
    Two methods available:
      'zscore'          — scipy z-score (same logic as the PHP version, now using scipy)
      'isolation_forest'— scikit-learn IsolationForest (better for small, skewed datasets)

    IsolationForest is the Python upgrade:
      - Does not assume a normal distribution
      - Works on multi-dimensional data (amount + date features)
      - contamination=0.1 means it expects ~10% of records to be outliers
    """
    expenses = pd.read_sql(
        db.query(Expense.id, Expense.amount, Expense.description, Expense.date)
          .filter(Expense.vehicle_id == vehicle_id)
          .order_by(Expense.date)
          .statement,
        db.bind,
        parse_dates=["date"]
    )

    if len(expenses) < 3:
        return {
            "anomalies":     [],
            "normal":        expenses.to_dict("records"),
            "mean":          None,
            "std_dev":       None,
            "anomaly_count": 0,
            "total_count":   len(expenses),
            "method":        method,
            "message":       "Insufficient data (minimum 3 records required).",
        }

    amounts = expenses["amount"].values.reshape(-1, 1)
    mean    = float(np.mean(amounts))
    std_dev = float(np.std(amounts))

    if method == "isolation_forest":
        # Add a day-of-month feature to make it multi-dimensional
        expenses["day_of_month"] = expenses["date"].dt.day
        features = expenses[["amount", "day_of_month"]].values

        clf = IsolationForest(
            n_estimators=100,
            contamination=0.1,   # expect ~10% outliers
            random_state=42
        )
        preds = clf.fit_predict(features)  # -1 = anomaly, 1 = normal
        scores_raw = clf.decision_function(features)

        expenses["is_anomaly"] = preds == -1
        expenses["anomaly_score"] = -scores_raw  # higher = more anomalous
        expenses["z_score"] = scipy_stats.zscore(expenses["amount"].values)

    else:  # zscore
        expenses["z_score"]    = scipy_stats.zscore(expenses["amount"].values)
        expenses["is_anomaly"] = expenses["z_score"].abs() > z_threshold
        expenses["anomaly_score"] = expenses["z_score"].abs()

    def severity(row):
        score = abs(row["z_score"]) if not pd.isna(row["z_score"]) else 0
        if score >= 4:   return "Critical"
        if score >= 3:   return "High"
        if score >= 2:   return "Medium"
        return "Normal"

    expenses["severity"] = expenses.apply(severity, axis=1)

    anomalies = expenses[expenses["is_anomaly"]].copy()
    normal    = expenses[~expenses["is_anomaly"]].copy()

    def to_records(df: pd.DataFrame) -> list:
        records = []
        for _, row in df.iterrows():
            records.append({
                "id":           int(row["id"]),
                "date":         str(row["date"].date()) if hasattr(row["date"], "date") else str(row["date"]),
                "amount":       float(row["amount"]),
                "description":  row["description"],
                "z_score":      round(float(row["z_score"]), 3),
                "anomaly_score":round(float(row["anomaly_score"]), 4),
                "is_anomaly":   bool(row["is_anomaly"]),
                "severity":     row["severity"],
            })
        return records

    return {
        "anomalies":     to_records(anomalies),
        "normal":        to_records(normal),
        "mean":          round(mean, 2),
        "std_dev":       round(std_dev, 2),
        "anomaly_count": int(len(anomalies)),
        "total_count":   int(len(expenses)),
        "method":        method,
        "threshold":     z_threshold,
        "message": (
            f"{len(anomalies)} anomalous expense(s) detected using {method}."
            if len(anomalies) > 0 else
            "No anomalies detected."
        ),
    }


def fleet_anomaly_report(db: Session) -> dict:
    vehicles        = db.query(Vehicle).all()
    report          = []
    total_anomalies = 0

    for v in vehicles:
        result = detect_expense_anomalies(v.id, db)
        total_anomalies += result["anomaly_count"]
        report.append({
            "vehicle_id":    v.id,
            "vehicle":       f"{v.manufacturer} {v.model} ({v.plate_number})",
            "anomaly_count": result["anomaly_count"],
            "total_expenses":result["total_count"],
            "mean_expense":  result["mean"],
            "method":        result["method"],
            "anomalies":     result["anomalies"],
        })

    report.sort(key=lambda x: x["anomaly_count"], reverse=True)
    return {"total_anomalies": total_anomalies, "vehicles": report}


# ─── 4. DRIVER PERFORMANCE SCORE ──────────────────────────────────────────────

def driver_performance_score(driver_id: int, db: Session) -> dict:
    """
    Multi-factor driver scoring with pandas groupby aggregations.
    Components: trip completion (40%), revenue vs fleet avg (35%), safety (25%).
    """
    driver = db.query(Driver).filter(Driver.id == driver_id).first()
    if not driver:
        return {"error": "Driver not found."}

    # Load trips as a DataFrame
    trips_df = pd.read_sql(
        db.query(Trip.id, Trip.status, Trip.vehicle_id, Trip.start_time)
          .filter(Trip.driver_id == driver_id)
          .statement,
        db.bind,
        parse_dates=["start_time"]
    )

    total_trips     = len(trips_df)
    completed_trips = int((trips_df["status"] == "completed").sum())
    completion_rate = completed_trips / total_trips if total_trips > 0 else 0.0

    # Revenue
    driver_income = db.query(func.sum(Income.amount)).filter(Income.driver_id == driver_id).scalar() or 0.0
    income_per_trip = driver_income / completed_trips if completed_trips > 0 else 0.0

    # Fleet average income per completed trip
    fleet_completed = db.query(func.count(Trip.id)).filter(Trip.status == "completed").scalar() or 0
    fleet_income    = db.query(func.sum(Income.amount)).scalar() or 0.0
    fleet_avg       = fleet_income / fleet_completed if fleet_completed > 0 else 0.0
    revenue_score   = min(1.0, income_per_trip / fleet_avg) if fleet_avg > 0 else (1.0 if income_per_trip > 0 else 0.0)

    # Safety — completed trips with no pending maintenance on the vehicle that day
    incident_free = 0
    completed_rows = trips_df[trips_df["status"] == "completed"]
    for _, row in completed_rows.iterrows():
        trip_date = row["start_time"].date() if pd.notna(row["start_time"]) else None
        if trip_date:
            pending_on_day = (
                db.query(func.count(Maintenance.id))
                  .filter(
                      Maintenance.vehicle_id == row["vehicle_id"],
                      Maintenance.date == trip_date,
                      Maintenance.status.in_(["Pending", "pending"])
                  ).scalar() or 0
            )
            if pending_on_day == 0:
                incident_free += 1

    safety_score = incident_free / completed_trips if completed_trips > 0 else 1.0

    composite = round(
        (completion_rate * 0.40 +
         revenue_score   * 0.35 +
         safety_score    * 0.25) * 100,
        1
    )

    grade = (
        "A – Outstanding"      if composite >= 85 else
        "B – Good"             if composite >= 70 else
        "C – Average"          if composite >= 55 else
        "D – Needs Improvement"if composite >= 40 else
        "F – Underperforming"
    )

    return {
        "driver_id":         driver_id,
        "performance_score": composite,
        "grade":             grade,
        "breakdown": {
            "trip_completion_rate": round(completion_rate * 100, 1),
            "revenue_score":        round(revenue_score  * 100, 1),
            "safety_score":         round(safety_score   * 100, 1),
        },
        "stats": {
            "total_trips":         total_trips,
            "completed_trips":     completed_trips,
            "incident_free_trips": incident_free,
            "income_per_trip":     round(float(income_per_trip), 2),
            "fleet_avg_income":    round(float(fleet_avg), 2),
        },
    }


def all_driver_scores(db: Session) -> dict:
    drivers = db.query(Driver).all()
    scores  = [driver_performance_score(d.id, db) for d in drivers]
    scores  = [s for s in scores if "error" not in s]
    scores.sort(key=lambda x: x["performance_score"], reverse=True)
    avg = round(np.mean([s["performance_score"] for s in scores]), 1) if scores else 0

    return {
        "total_drivers":  len(drivers),
        "average_score":  avg,
        "top_performer":  scores[0] if scores else None,
        "drivers":        scores,
    }


# ─── 5. COST FORECAST — HOLT-WINTERS EWMA ────────────────────────────────────

def cost_forecast(vehicle_id: int, db: Session, months: int = 6) -> dict:
    """
    Forecast next month's expenses using statsmodels ExponentialSmoothing
    (Holt-Winters). This is a proper time-series model, more accurate than
    the manual EWMA weights used in the PHP version.

    If insufficient data, falls back to pandas ewm() weighted average.
    """
    # Build monthly expense totals for the last `months` months
    history = []
    for i in range(months - 1, -1, -1):           # oldest → newest
        d     = datetime.now().replace(day=1) - pd.DateOffset(months=i)
        total = (
            db.query(func.sum(Expense.amount))
              .filter(
                  Expense.vehicle_id == vehicle_id,
                  func.month(Expense.date) == d.month,
                  func.year(Expense.date)  == d.year
              ).scalar() or 0.0
        )
        history.append({"month": d.strftime("%b %Y"), "amount": float(total)})

    amounts = [h["amount"] for h in history]
    series  = pd.Series(amounts)

    # Try Holt-Winters ExponentialSmoothing (handles trend + level)
    forecast_value = 0.0
    model_used     = "Holt-Winters (statsmodels)"

    if series.sum() > 0 and len(series) >= 4:
        try:
            hw_model = ExponentialSmoothing(
                series,
                trend="add",
                initialization_method="estimated"
            )
            hw_fit      = hw_model.fit(optimized=True)
            forecast_value = max(0.0, float(hw_fit.forecast(1).iloc[0]))
        except Exception:
            # Fallback to pandas ewm if Holt-Winters fails (e.g., all zeros)
            forecast_value = float(series.ewm(span=3).mean().iloc[-1])
            model_used     = "EWM fallback (pandas)"
    else:
        # Fallback — manual weights (same as PHP version)
        weights = [0.30, 0.20, 0.15, 0.15, 0.10, 0.10]
        for_calc = list(reversed(amounts))          # newest first
        w_sum    = sum(a * w for a, w in zip(for_calc, weights))
        total_w  = sum(weights[:len(for_calc)])
        forecast_value = w_sum / total_w if total_w > 0 else 0.0
        model_used     = "Manual EWMA (fallback)"

    # Trend detection via linear regression on the series
    if series.sum() > 0:
        x      = np.arange(len(series)).reshape(-1, 1)
        lr     = LinearRegression().fit(x, series.values)
        slope  = lr.coef_[0]
        trend  = "Increasing" if slope > 1 else "Decreasing" if slope < -1 else "Stable"
    else:
        trend = "Stable"

    last_month = amounts[-1]
    alert_level = (
        "High — forecast is 50%+ above last month"   if forecast_value > last_month * 1.5 else
        "Medium — forecast is 20%+ above last month" if forecast_value > last_month * 1.2 else
        "Normal"
    )

    return {
        "vehicle_id":      vehicle_id,
        "forecast_month":  (datetime.now() + pd.DateOffset(months=1)).strftime("%b %Y"),
        "forecasted_cost": round(forecast_value, 2),
        "trend":           trend,
        "alert_level":     alert_level,
        "history":         history,
        "model_used":      model_used,
    }


def fleet_cost_forecast(db: Session) -> dict:
    vehicles  = db.query(Vehicle).all()
    forecasts = [cost_forecast(v.id, db) for v in vehicles]
    forecasts.sort(key=lambda x: x["forecasted_cost"], reverse=True)

    return {
        "forecast_month":       (datetime.now() + pd.DateOffset(months=1)).strftime("%b %Y"),
        "total_fleet_forecast": round(sum(f["forecasted_cost"] for f in forecasts), 2),
        "high_alert_vehicles":  sum(1 for f in forecasts if f["alert_level"].startswith("High")),
        "vehicles":             forecasts,
    }
