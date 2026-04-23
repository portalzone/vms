# ML Integration in VMS — Interview Preparation Guide
### Feaver's Lane — AI/ML Spatial Applications Intern
**Candidate:** Victor Muojeke | **Date:** April 2026

---

## 1. What ML did you integrate and why?

I integrated a five-algorithm Machine Learning engine directly into the VMS (Vehicle Management System) Laravel API. The engine is written in pure PHP and does not require any external Python service, which was a deliberate architectural decision suited to the Hostinger shared hosting environment.

The five models are:

| # | Model | Real-World Problem Solved |
|---|---|---|
| 1 | Predictive Maintenance | Anticipate vehicle breakdowns before they happen |
| 2 | Fleet Health Score | Give managers a single number representing each vehicle's overall condition |
| 3 | Expense Anomaly Detection | Catch fraudulent or erroneous cost entries automatically |
| 4 | Driver Performance Score | Objectively rank drivers without manual reviews |
| 5 | Cost Forecast (EWMA) | Budget planning — predict next month's expenses |

---

## 2. Explaining Each Algorithm in Plain English

### 2.1 Predictive Maintenance — Mean-Interval Regression

**The idea:** If a vehicle historically gets serviced every 45 days on average, the next service is 45 days after the last one.

**How I calculate it:**
1. Pull all maintenance records for a vehicle, sorted by date.
2. Compute the gap (in days) between each consecutive pair: `[day 0 → day 42, day 42 → day 51, ...]`
3. Take the mean of those gaps: `mean = sum(gaps) / count(gaps)`
4. `predicted_date = last_maintenance_date + mean`
5. Calculate **confidence** using the Coefficient of Variation:  
   `CV = σ / μ` where σ is standard deviation of the gaps  
   `confidence = 1 − CV` (0 = unpredictable, 1 = perfectly regular)

**Risk levels:**
- Critical → overdue (days_until < 0)
- High → due within 7 days
- Medium → due within 30 days
- Low → more than 30 days away

**Interview talking point:** "This is essentially simple linear regression reduced to one feature — time. In a future iteration with GPS/odometer data, I would switch to a multi-variate regression with mileage as an additional predictor, which is how BMW and Tesla implement their service-due algorithms."

---

### 2.2 Fleet Health Score — Weighted Composite Score

**The idea:** A single 0–100 score per vehicle, like a credit score for cars.

**Formula:**
```
Health = (Maintenance% × 0.30) + (Trip% × 0.25) + (Cost Efficiency × 0.25) + (Availability × 0.20)
```

Each component normalised to 0–100:
- **Maintenance%** = completed maintenances / total maintenances
- **Trip%** = completed trips / total trips
- **Cost Efficiency** = income / (income + expenses) — higher ratio means the vehicle earns more than it costs
- **Availability** = 100 − (pending_maintenance_count × 20)

**Grades:** A ≥ 85, B ≥ 70, C ≥ 55, D ≥ 40, F < 40

**Interview talking point:** "The weights were chosen based on domain knowledge — maintenance completion is the strongest signal of fleet health (30%), while availability is a downstream effect of the other factors (20%). In production I would run a Principal Component Analysis to let the data determine the optimal weights."

---

### 2.3 Expense Anomaly Detection — Z-Score

**The idea:** If the average oil change costs ₦8,000, a record for ₦80,000 should be flagged automatically.

**Formula:**
```
z = (x − μ) / σ
```
Where x = expense amount, μ = mean of all expenses for that vehicle, σ = standard deviation.

- Flag as anomaly when `|z| > 2.0` (outside 95% of the normal distribution)
- Severity: Medium (z: 2–3), High (z: 3–4), Critical (z > 4)

**Interview talking point:** "Z-score is the simplest form of unsupervised anomaly detection. The next level would be Isolation Forest or DBSCAN clustering — both work well on multi-dimensional expense data (amount, frequency, category). I kept z-score here because it is interpretable — a manager can understand '3 standard deviations above average' without any ML background."

---

### 2.4 Driver Performance Score — Multi-Factor Scoring

**The idea:** An objective driver ranking built from three dimensions.

**Formula:**
```
Score = (completion_rate × 0.40) + (revenue_score × 0.35) + (safety_score × 0.25)
```

- **Completion rate** = completed_trips / total_trips
- **Revenue score** = driver's average income per trip ÷ fleet average income per trip (capped at 1.0)
- **Safety score** = trips completed without a pending maintenance issue on the same vehicle that day

**Interview talking point:** "The revenue score is normalised against the fleet average, so a driver is not penalised for operating on a lower-value route — it is relative performance that matters. This approach mirrors how Uber's driver rating system normalises scores by city and route type."

---

### 2.5 Cost Forecast — Exponentially Weighted Moving Average (EWMA)

**The idea:** Predict next month's vehicle expenses based on recent trends, giving more weight to recent months.

**Formula:**
```
Forecast = Σ(monthly_expense × weight) / Σ(weights)
Weights (newest → oldest): 0.30, 0.20, 0.15, 0.15, 0.10, 0.10
```

**Why EWMA over simple average?**  
A simple average treats a ₦5,000 expense from 6 months ago the same as last month's ₦50,000. EWMA gives more influence to recent data, which matters for budgeting.

**Alert levels:**
- Normal
- Medium — forecast is 20%+ above last month
- High — forecast is 50%+ above last month

**Interview talking point:** "EWMA is the same algorithm used in stock market technical analysis (EMA indicator) and in AWS CloudWatch for metric anomaly detection. For a more accurate forecast with seasonal patterns (e.g., more maintenance in rainy season), I would apply Holt-Winters triple exponential smoothing."

---

## 3. How Does This Relate to Feaver's Lane's Work?

Feaver's Lane builds AI/ML tools for GIS / environmental data. The same algorithms I used in VMS map directly to spatial problems:

| VMS Algorithm | Feaver's Lane Equivalent |
|---|---|
| Predictive Maintenance (interval regression) | Predict when a marine monitoring sensor will fail based on service history |
| Fleet Health Score (composite scoring) | Score habitat health from multiple environmental indicators (water temperature, species count, turbidity) |
| Anomaly Detection (z-score) | Flag GPS track anomalies — a fishing vessel in a protected zone, or an unexpected species sighting outside its range |
| Driver Performance Score | Score field researchers by data quality, GPS coverage area, and observation counts |
| Cost Forecast (EWMA) | Predict survey resource needs based on seasonal patterns — more expeditions in summer |

**Key message:** "The algorithms themselves are domain-agnostic. What changes is the data source — vehicle telemetry vs. GIS coordinates vs. species observation records."

---

## 4. Technical Questions You May Face

### Q: Why PHP instead of Python for ML?
**A:** The VMS is hosted on Hostinger shared hosting which does not support persistent Python processes. PHP is the native runtime for the existing Laravel API. For heavier ML workloads (neural networks, large datasets), I would expose a Python FastAPI microservice and call it from Laravel via HTTP — the same pattern used by companies like Shopify (Ruby → Python ML service).

### Q: How do you handle the cold-start problem (no data for a new vehicle)?
**A:** All five algorithms include graceful fallbacks:
- Predictive maintenance returns `"Insufficient data"` if fewer than 2 maintenance records exist
- Health score defaults components to 100 if no data (benefit of the doubt)
- Anomaly detection requires at least 3 records
- EWMA returns $0 if no expense history exists

### Q: What ML libraries would you use if you had full server control?
**A:** 
- **scikit-learn** (Python) — for Random Forest or Gradient Boosting on maintenance prediction
- **pandas + statsmodels** — for time series forecasting
- **Isolation Forest** (sklearn) — for anomaly detection on multi-dimensional data
- **TensorFlow Lite** or **ONNX** — if deploying on-device (IoT sensor / mobile)

### Q: How would you improve accuracy with more data?
**A:**
1. Add odometer/mileage as a feature for maintenance prediction (distance-based model)
2. Incorporate GPS route data for driver scoring (harsh braking, speeding events)
3. Use seasonal decomposition for expense forecasting (wet vs dry season in NL)
4. Label historical data and train a proper supervised model with cross-validation

### Q: What is the difference between supervised and unsupervised ML?
**A:**
- **Supervised:** You train a model with labelled examples (e.g., "this maintenance was unplanned = 1, planned = 0"). The model learns the mapping.
- **Unsupervised:** No labels. The algorithm finds patterns on its own — e.g., z-score anomaly detection groups expenses without being told what "normal" looks like.
- My VMS uses **unsupervised** approaches (mean intervals, z-score, EWMA) because we do not have labelled training data. Supervised models would be the next evolution.

### Q: What is GIS and how does it connect to ML?
**A:** GIS (Geographic Information System) combines location data with attribute data — "this marine mammal was spotted at lat/lng X with these environmental conditions." ML on GIS data involves:
- **Classification** — identify species from acoustic or image data
- **Clustering** — group habitat zones by similarity (K-Means on environmental features)
- **Spatial regression** — predict species density as a function of water temperature, depth, salinity
- **Object detection** — identify species in drone imagery (YOLO, ResNet)

In VMS, the spatial layer is the vehicle's route (start/end locations). A GIS extension would add GPS polylines and heat maps showing high-traffic corridors.

---

## 5. The API Endpoints — Know These Cold

```
Base: https://vms.basepan.com/api/ml/

GET /dashboard                         All ML outputs (admin/manager)
GET /health/fleet                      All vehicle health scores
GET /health/{vehicleId}                Single vehicle health
GET /maintenance/predict/{vehicleId}   Next maintenance date
GET /anomalies/fleet                   Fleet expense anomalies
GET /anomalies/{vehicleId}             Single vehicle anomalies
GET /forecast/fleet                    Fleet cost forecast
GET /forecast/{vehicleId}              Single vehicle forecast
GET /driver/scores/all                 All driver rankings
GET /driver/{driverId}/score           Single driver score
```

All require `Authorization: Bearer <token>` header.

---

## 6. Project Architecture — 30-Second Elevator Pitch

"VMS is a vehicle fleet management system deployed at vms.basepan.com. It is built on Laravel 12 with a Vue 3 frontend. I integrated a Machine Learning engine — five statistical algorithms — directly into the PHP API: predictive maintenance scheduling using mean-interval regression, a fleet health scoring model using weighted composite metrics, expense anomaly detection using z-score analysis, driver performance ranking using multi-factor scoring, and cost forecasting using exponentially weighted moving averages. The same statistical foundations power GIS spatial analysis tools at organisations like Feaver's Lane."

---

## 7. Quick Revision — Key Numbers

- **5** ML algorithms integrated
- **0** external ML libraries — pure PHP statistics
- **10** ML API endpoints
- **4** user roles with scoped ML access
- Anomaly threshold: **z > 2.0** (95th percentile)
- EWMA newest-month weight: **0.30**
- Fleet Health weights: **30 / 25 / 25 / 20**
- Driver Performance weights: **40 / 35 / 25**

---

## 8. Closing Statement for Interview

*"I believe the most valuable skill in AI/ML is not knowing which framework to import — it is knowing which mathematical technique fits the problem, and being able to implement it in whatever runtime the deployment environment demands. Building VMS's ML engine in PHP for Hostinger hosting was a deliberate constraint that sharpened my understanding of the underlying statistics. At Feaver's Lane, I would apply those same first-principles skills to marine species classification and ecoregion documentation, while also leveraging Python's richer ML ecosystem where infrastructure allows."*
