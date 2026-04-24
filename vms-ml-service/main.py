"""
VMS Python ML Microservice
==========================
FastAPI service exposing the same 10 ML endpoints as the PHP version,
but powered by scikit-learn, statsmodels, scipy, pandas, and numpy.

Run locally:
    uvicorn main:app --reload --port 8001

Auto-generated API docs:
    http://localhost:8001/docs
"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from routers.ml import router as ml_router

app = FastAPI(
    title="VMS ML Service",
    description="Machine Learning microservice for the Vehicle Management System",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["https://vms.basepan.com", "http://localhost:5173"],
    allow_credentials=True,
    allow_methods=["GET"],
    allow_headers=["*"],
)

app.include_router(ml_router, prefix="/api")


@app.get("/")
def root():
    return {
        "service": "VMS ML Microservice",
        "version": "1.0.0",
        "docs":    "/docs",
        "endpoints": [
            "GET /api/ml/dashboard",
            "GET /api/ml/health/fleet",
            "GET /api/ml/health/{vehicle_id}",
            "GET /api/ml/maintenance/predict/{vehicle_id}",
            "GET /api/ml/anomalies/fleet",
            "GET /api/ml/anomalies/{vehicle_id}",
            "GET /api/ml/forecast/fleet",
            "GET /api/ml/forecast/{vehicle_id}",
            "GET /api/ml/driver/scores/all",
            "GET /api/ml/driver/{driver_id}/score",
        ]
    }


@app.get("/health")
def health():
    return {"status": "ok"}
