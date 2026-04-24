from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from datetime import datetime

from database import get_db
from services.ml_service import (
    predictive_maintenance,
    fleet_health_score,
    fleet_summary,
    detect_expense_anomalies,
    fleet_anomaly_report,
    driver_performance_score,
    all_driver_scores,
    cost_forecast,
    fleet_cost_forecast,
)

router = APIRouter(prefix="/ml", tags=["ML Insights"])


# ── Predictive Maintenance ────────────────────────────────────────────────────
@router.get("/maintenance/predict/{vehicle_id}")
def predict_maintenance(vehicle_id: int, db: Session = Depends(get_db)):
    return predictive_maintenance(vehicle_id, db)


# ── Fleet Health ──────────────────────────────────────────────────────────────
@router.get("/health/fleet")
def get_fleet_health(db: Session = Depends(get_db)):
    return fleet_summary(db)


@router.get("/health/{vehicle_id}")
def get_vehicle_health(vehicle_id: int, db: Session = Depends(get_db)):
    return fleet_health_score(vehicle_id, db)


# ── Anomaly Detection ─────────────────────────────────────────────────────────
@router.get("/anomalies/fleet")
def get_fleet_anomalies(
    method: str = "isolation_forest",
    db: Session = Depends(get_db)
):
    return fleet_anomaly_report(db)


@router.get("/anomalies/{vehicle_id}")
def get_vehicle_anomalies(
    vehicle_id: int,
    method: str = "isolation_forest",
    threshold: float = 2.0,
    db: Session = Depends(get_db)
):
    return detect_expense_anomalies(vehicle_id, db, method, threshold)


# ── Driver Performance ────────────────────────────────────────────────────────
@router.get("/driver/scores/all")
def get_all_driver_scores(db: Session = Depends(get_db)):
    return all_driver_scores(db)


@router.get("/driver/{driver_id}/score")
def get_driver_score(driver_id: int, db: Session = Depends(get_db)):
    result = driver_performance_score(driver_id, db)
    if "error" in result:
        raise HTTPException(status_code=404, detail=result["error"])
    return result


# ── Cost Forecast ─────────────────────────────────────────────────────────────
@router.get("/forecast/fleet")
def get_fleet_forecast(db: Session = Depends(get_db)):
    return fleet_cost_forecast(db)


@router.get("/forecast/{vehicle_id}")
def get_vehicle_forecast(vehicle_id: int, db: Session = Depends(get_db)):
    return cost_forecast(vehicle_id, db)


# ── All-in-One Dashboard ──────────────────────────────────────────────────────
@router.get("/dashboard")
def get_dashboard(db: Session = Depends(get_db)):
    return {
        "fleet_health":    fleet_summary(db),
        "fleet_forecast":  fleet_cost_forecast(db),
        "fleet_anomalies": fleet_anomaly_report(db),
        "driver_scores":   all_driver_scores(db),
        "generated_at":    datetime.utcnow().isoformat() + "Z",
    }
