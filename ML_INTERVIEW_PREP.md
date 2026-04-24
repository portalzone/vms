# Interview Preparation — Feaver's Lane AI/ML Spatial Applications Intern
**Candidate:** Victor Muojeke | **Interview Date:** April 24, 2026
**Live Project:** https://vms.basepan.com | **GitHub:** https://github.com/portalzone/vms

---

## PART 1 — YOUR 30-SECOND OPENING PITCH

> "I built and deployed a full-stack Vehicle Management System at vms.basepan.com using
> Laravel 12 as the API backend and Vue 3 as the frontend. To demonstrate AI/ML capability,
> I integrated a five-algorithm machine learning engine directly into the PHP API — covering
> predictive maintenance, fleet health scoring, expense anomaly detection, driver performance
> ranking, and cost forecasting. All five models are live, accessible via REST API endpoints,
> and visualised on a dedicated ML Insights dashboard. The same statistical algorithms that
> power my VMS directly apply to the GIS and environmental data classification work at
> Feaver's Lane."

---

## PART 2 — HOW YOU ACTUALLY BUILT IT (The Real Story)

### The Stack
| Layer | Technology | Hosted At |
|---|---|---|
| Backend API | Laravel 12 (PHP 8.2) | Hostinger — vms.basepan.com/api |
| Frontend SPA | Vue 3 + Tailwind CSS | Hostinger — vms.basepan.com |
| ML Engine | Pure PHP (no external libraries) | Inside Laravel — app/Services/MLService.php |
| Authentication | Laravel Sanctum (Bearer tokens) | All ML routes protected |

### The Files You Created
```
app/Services/MLService.php              ← All 5 ML algorithms (500+ lines)
app/Http/Controllers/Api/MLController.php ← 10 REST API endpoints
routes/api.php                          ← /api/ml/* route group
vms-frontend/vue-project/src/views/MLDashboard.vue ← The visual dashboard
```

### Why Pure PHP Instead of Python?
Hostinger shared hosting does not support persistent Python processes. Rather than
changing the hosting infrastructure, you implemented the mathematics directly in PHP.
This was a deliberate engineering decision, not a limitation.

**Say this in the interview:**
> "I chose to implement the ML algorithms natively in PHP because the hosting
> environment constrained me to a single runtime. This forced me to understand the
> underlying mathematics — mean intervals, z-scores, weighted averages — rather than
> just importing a library. In a Python environment I would use scikit-learn and pandas,
> but the statistical foundations are identical."

---

## PART 3 — THE 5 ALGORITHMS (How You Built Each One)

---

### Algorithm 1 — Predictive Maintenance
**File:** `MLService.php → predictiveMaintenance(int $vehicleId)`
**API:** `GET /api/ml/maintenance/predict/{vehicleId}`

**What it does:** Predicts the date a vehicle will next need maintenance.

**How you built it — step by step:**
1. Query the `maintenances` table for all records for the vehicle, sorted by date
2. Calculate the gap in days between each consecutive maintenance:
   `[maintenance1 → maintenance2 = 42 days, maintenance2 → maintenance3 = 38 days]`
3. Calculate the **mean** (average) of those gaps:
   `mean = (42 + 38) / 2 = 40 days`
4. Add mean to the last maintenance date:
   `predicted_date = last_date + 40 days`
5. Calculate **confidence** using standard deviation:
   - `CV (Coefficient of Variation) = standard_deviation / mean`
   - `confidence = 1 - CV` (closer to 1.0 = more regular servicing pattern)
6. Set **risk level** based on days remaining:
   - `days < 0` → Critical (overdue)
   - `days ≤ 7` → High
   - `days ≤ 30` → Medium
   - `days > 30` → Low

**What the live system showed:**
- Toyota Corolla (ASB-212-EY): "Insufficient data" — needs at least 2 maintenance records
- Honda Civic (ASB-839-UI): "Insufficient data" — same reason
- This is correct behaviour — the algorithm is honest about data limitations

**Key terms to use:** mean interval, standard deviation, coefficient of variation,
regression, time-series prediction, cold-start problem

---

### Algorithm 2 — Fleet Health Score
**File:** `MLService.php → fleetHealthScore(int $vehicleId)`
**API:** `GET /api/ml/health/{vehicleId}` and `GET /api/ml/health/fleet`

**What it does:** Produces a single 0–100 score for each vehicle's overall condition.

**How you built it — the formula:**
```
Health Score = (Maintenance% × 0.30) + (Trip% × 0.25)
             + (Cost Efficiency × 0.25) + (Availability × 0.20)
```

| Component | Weight | How Calculated |
|---|---|---|
| Maintenance completion | 30% | completed_maintenances / total_maintenances × 100 |
| Trip completion | 25% | completed_trips / total_trips × 100 |
| Cost efficiency | 25% | income / (income + expenses) × 100 |
| Availability | 20% | 100 − (pending_maintenance_count × 20) |

**Grading:**
- A ≥ 85, B ≥ 70, C ≥ 55, D ≥ 40, F < 40

**What the live system showed:**
- Toyota Corolla: **100/100 — A Excellent** (assigned driver, 1 completed trip, no issues)
- Honda Civic: **87.5/100 — A Excellent** (no driver assigned = slight cost efficiency penalty)
- Fleet average: **93.8/100**

**Why the weights are 30/25/25/20:**
Maintenance completion is the strongest predictor of vehicle reliability, so it gets the
highest weight. Availability is partly a result of the other three, so it gets the lowest.

**Key terms to use:** weighted composite scoring, normalisation, domain-weighted model,
feature engineering, interpretable ML

---

### Algorithm 3 — Expense Anomaly Detection
**File:** `MLService.php → detectExpenseAnomalies(int $vehicleId)`
**API:** `GET /api/ml/anomalies/{vehicleId}` and `GET /api/ml/anomalies/fleet`

**What it does:** Automatically flags expense records that are statistically unusual.

**How you built it — z-score method:**
```
z = (expense_amount − mean) / standard_deviation
```
- Calculate mean (μ) of all expense amounts for a vehicle
- Calculate standard deviation (σ)
- For each expense, compute z-score
- Flag as anomaly if |z| > 2.0

**Severity scale:**
| Z-Score | Severity |
|---|---|
| |z| < 2 | Normal |
| 2 ≤ |z| < 3 | Medium |
| 3 ≤ |z| < 4 | High |
| |z| ≥ 4 | Critical |

**What the live system showed:**
- Total anomalies: **0** — no expenses have been recorded yet
- System correctly reported: "No expense anomalies detected across the fleet"
- Once expenses are added, the algorithm activates automatically

**Real-world example to give in interview:**
> "If the average oil change for a vehicle costs ₦8,000 and someone records ₦80,000,
> that has a z-score of around 3.5 — flagged as High severity. The system alerts the
> manager without anyone having to manually review every expense."

**Why z > 2.0 threshold?**
A z-score of 2.0 corresponds to the 95th percentile of a normal distribution.
Only 5% of legitimate expenses will be flagged — a good balance between sensitivity
and false positives.

**Key terms to use:** z-score, standard deviation, normal distribution, unsupervised
anomaly detection, statistical outlier, false positive rate, 95th percentile

---

### Algorithm 4 — Driver Performance Score
**File:** `MLService.php → driverPerformanceScore(int $driverId)`
**API:** `GET /api/ml/driver/{driverId}/score` and `GET /api/ml/driver/scores/all`

**What it does:** Objectively ranks every driver 0–100 across three dimensions.

**How you built it — the formula:**
```
Score = (trip_completion × 0.40) + (revenue_score × 0.35) + (safety_score × 0.25)
```

| Component | Weight | How Calculated |
|---|---|---|
| Trip completion rate | 40% | completed_trips / total_trips |
| Revenue score | 35% | driver's avg income per trip ÷ fleet avg income per trip (max 1.0) |
| Safety score | 25% | trips with no pending maintenance on the vehicle that day |

**Revenue normalisation explained:**
A driver on a short city route earns less per trip than one on a long highway route.
Dividing by the fleet average means you compare relative performance, not raw numbers.
This is fair and is the same approach Uber uses for driver ratings.

**What the live system showed:**
- Driver User: **100/100 — A Outstanding**
  - Trip completion: 100% (1/1 trips completed)
  - Revenue score: 100%
  - Safety score: 100%
  - No anomalies, no missed trips

**Key terms to use:** multi-factor scoring, normalisation, relative performance,
feature weighting, ranking algorithm, objective evaluation

---

### Algorithm 5 — Cost Forecast (EWMA)
**File:** `MLService.php → costForecast(int $vehicleId)`
**API:** `GET /api/ml/forecast/{vehicleId}` and `GET /api/ml/forecast/fleet`

**What it does:** Predicts next month's vehicle expenses using the last 6 months of data,
giving more weight to recent months.

**How you built it — Exponentially Weighted Moving Average:**
```
Forecast = Σ(monthly_expense × weight) / Σ(weights)

Weights applied (newest month first):
Month -1 (most recent): 0.30
Month -2:               0.20
Month -3:               0.15
Month -4:               0.15
Month -5:               0.10
Month -6 (oldest):      0.10
Total:                  1.00
```

**Why not a simple average?**
A simple average treats ₦5,000 from 6 months ago equally with ₦50,000 from last month.
EWMA gives recent spending more influence on the forecast, which is more useful for budgeting.

**Alert levels:**
- Normal — forecast is close to last month
- Medium — forecast is 20%+ above last month
- High — forecast is 50%+ above last month (flag for budget review)

**What the live system showed:**
- Both vehicles: **₦0.00 forecast** — no expense history yet
- Trend: **Stable** — correct with no data
- As expenses are recorded month-by-month, the forecast will update automatically

**Key terms to use:** EWMA, time series forecasting, moving average, recency bias,
weighted average, trend detection, budget forecasting

---

## PART 4 — KEY ML TERMS YOU MUST KNOW

### Core Statistics
| Term | Simple Definition | Used In Your Project |
|---|---|---|
| **Mean (μ)** | Sum of values ÷ count | Maintenance interval, expense average |
| **Standard Deviation (σ)** | How spread out values are from the mean | Anomaly detection, confidence score |
| **Variance** | Standard deviation squared | Underlying calculation in z-score |
| **Z-Score** | How many standard deviations a value is from the mean | Expense anomaly detection |
| **Normal Distribution** | Bell curve — most data clusters around the mean | Basis of z-score anomaly detection |
| **Coefficient of Variation** | σ ÷ μ — relative measure of spread | Maintenance prediction confidence |
| **Weighted Average** | Average where some values matter more | Fleet health score, EWMA forecast |
| **Normalisation** | Scaling values to a 0–1 or 0–100 range | All five algorithms |

### ML Concepts
| Term | Simple Definition | Used In Your Project |
|---|---|---|
| **Supervised Learning** | Model trained on labelled data (input → known output) | Not used — no labelled data available |
| **Unsupervised Learning** | Model finds patterns in unlabelled data | All 5 of your algorithms |
| **Regression** | Predicting a continuous number (e.g., next date, next cost) | Maintenance prediction, cost forecast |
| **Classification** | Predicting a category (e.g., Low/Medium/High risk) | Risk level in maintenance prediction |
| **Anomaly Detection** | Finding data points that don't fit the pattern | Expense anomaly detection |
| **Feature** | A measurable input variable used by an ML model | Date gaps, trip counts, expense amounts |
| **Feature Engineering** | Transforming raw data into useful ML inputs | Converting dates into day-gap intervals |
| **Cold-Start Problem** | Model can't predict when there is no historical data | "Insufficient data" message in VMS |
| **Overfitting** | Model too tuned to training data, fails on new data | Avoided by using simple statistical models |
| **Training Data** | Historical data used to build a model | Past maintenance records, trip history |
| **Inference** | Using a trained model to make a prediction | Every time the ML API is called |
| **Model Accuracy** | How correct the model's predictions are | Improves as more VMS data is added |
| **Confidence Score** | How certain the model is of its prediction | Returned by predictive maintenance |
| **Threshold** | The cutoff value that triggers a classification | z > 2.0 for anomaly, days < 7 for High risk |

### Time Series
| Term | Simple Definition |
|---|---|
| **Time Series** | Data collected at regular time intervals |
| **Moving Average** | Average of the last N data points, recalculated as new data arrives |
| **EWMA** | Moving average where recent values have higher weight |
| **Trend** | Whether values are generally going up, down, or staying flat |
| **Seasonality** | Patterns that repeat on a calendar cycle (monthly, yearly) |
| **Forecasting** | Predicting future values based on past patterns |

---

## PART 5 — GIS AND SPATIAL ML TERMS (Feaver's Lane Specific)

This is what Feaver's Lane actually does — know these terms.

| Term | Definition | Example |
|---|---|---|
| **GIS** | Geographic Information System — software that links data to geographic locations | ArcGIS, QGIS, Google Earth Engine |
| **Raster Data** | Grid of pixels, each with a value (like a photo) | Satellite imagery, elevation maps, temperature grids |
| **Vector Data** | Points, lines, polygons representing geographic features | Species GPS locations, coastlines, survey routes |
| **Spatial Analysis** | Finding patterns in geographic data | Which areas have highest species density |
| **Ecoregion** | A geographic area defined by similar ecology | Boreal forest, Atlantic coast, Arctic tundra |
| **Species Classification** | Using ML to identify species from images/sounds/sensor data | Identify whale species from acoustic recordings |
| **Remote Sensing** | Collecting data about Earth from satellites or drones | NDVI (vegetation index), sea surface temperature |
| **NDVI** | Normalised Difference Vegetation Index — measures plant health from satellite | Used to map forest health and deforestation |
| **Object Detection** | ML model that identifies and locates objects in images | YOLO, Faster R-CNN for species in drone footage |
| **Image Classification** | ML model that labels what an entire image contains | ResNet, VGG for marine species photos |
| **Georeferencing** | Linking an image or dataset to real-world GPS coordinates | Attaching lat/lng to a species sighting |
| **Shapefile** | Common GIS file format for vector geographic data | .shp files used in ArcGIS and QGIS |
| **K-Means Clustering** | Groups data points into K clusters by similarity | Group habitats by environmental conditions |
| **Random Forest** | ML model that uses many decision trees and votes | Classify land use type from satellite features |
| **CNN (Convolutional Neural Network)** | Deep learning model designed for image recognition | Identify marine species from underwater photos |
| **Transfer Learning** | Reusing a pre-trained model on a new but similar task | Fine-tune ResNet (trained on ImageNet) to classify NL fish species |
| **Spatial Autocorrelation** | Nearby locations tend to have similar values | Temperature in one bay predicts temperature in adjacent bay |
| **Interpolation** | Estimating values between known data points | Estimate species count in unsampled areas |

### How Your VMS Algorithms Connect to GIS Work

| Your VMS Algorithm | Feaver's Lane Spatial Equivalent |
|---|---|
| Predictive Maintenance | Predict when a water quality sensor in a bay needs recalibration based on its service history |
| Fleet Health Score | Score each ecoregion's health from multiple indicators: water temperature, pH, species count, human disturbance |
| Expense Anomaly Detection | Flag unusual GPS coordinates in a species survey — a data point recorded in the wrong ocean |
| Driver Performance Score | Score field researchers by data collection quality: GPS coverage, observation completeness, recording accuracy |
| EWMA Cost Forecast | Forecast survey costs for next quarter based on seasonal patterns (more boat trips in summer) |

**Key message for the interview:**
> "The mathematical techniques are identical — what changes is the data source.
> In VMS I apply z-score to expense amounts. At Feaver's Lane I would apply the
> same z-score to water temperature readings or species population counts to flag
> environmental anomalies."

---

## PART 6 — LIKELY INTERVIEW QUESTIONS AND YOUR ANSWERS

### Q: Walk me through your VMS project.
**A:** "VMS is a vehicle fleet management system I built and deployed at vms.basepan.com.
The backend is a Laravel 12 REST API with role-based access control for four user types —
admin, manager, vehicle owner, and driver. The frontend is a Vue 3 single-page application.
I integrated a machine learning engine with five algorithms: predictive maintenance,
fleet health scoring, expense anomaly detection, driver performance ranking, and cost
forecasting using EWMA. All five models run live and output data to a dedicated
ML Insights dashboard accessible from the navigation bar."

---

### Q: What is machine learning?
**A:** "Machine learning is the process of using mathematical algorithms that learn patterns
from data and use those patterns to make predictions or decisions — without being explicitly
programmed with rules for every situation. Instead of writing 'if expense > ₦50,000 then
flag it,' you let the algorithm learn what is normal from historical data and flag anything
that deviates significantly from that pattern."

---

### Q: What is the difference between supervised and unsupervised ML?
**A:** "In supervised learning, you train a model on labelled data — each input has a
known correct output. For example, if I had thousands of past expenses labelled 'fraudulent'
or 'legitimate,' I could train a classifier. In unsupervised learning, there are no labels —
the algorithm finds structure in the data on its own. My VMS uses unsupervised techniques
because the system has no pre-labelled examples. Z-score anomaly detection is unsupervised:
it defines 'normal' from the data itself, then flags deviations."

---

### Q: What is a z-score and how does it detect anomalies?
**A:** "A z-score measures how far a data point is from the mean in units of standard
deviation. The formula is z = (x − μ) / σ. If the average vehicle expense is ₦10,000
with a standard deviation of ₦3,000, an expense of ₦25,000 has a z-score of 5 —
five standard deviations above the mean. In a normal distribution, only 0.00003% of
values fall that far out. So I flag any expense with |z| > 2.0 as potentially anomalous.
In my system, that threshold captures outliers beyond the 95th percentile."

---

### Q: What is EWMA and why did you use it for cost forecasting?
**A:** "EWMA stands for Exponentially Weighted Moving Average. It forecasts future values
by averaging past values but giving more weight to recent data. I used weights of
0.30, 0.20, 0.15, 0.15, 0.10, 0.10 for the last six months — newest to oldest.
A simple average would treat spending from six months ago equally with last month's
spending, which is misleading. EWMA correctly reflects that recent behaviour is a
better predictor of the future than old behaviour."

---

### Q: How would you improve these models with more data?
**A:** "For predictive maintenance, I would add odometer mileage as a second feature —
distance-based maintenance prediction is more accurate than time-based. For anomaly
detection, I would use Isolation Forest which handles multi-dimensional data better than
z-score. For cost forecasting, I would apply Holt-Winters triple exponential smoothing
to capture seasonal patterns — for example, vehicles tend to need more maintenance in
rainy season. For driver performance, I would integrate GPS telemetry to detect harsh
braking and speeding events."

---

### Q: How does this relate to marine species classification?
**A:** "The statistical foundations are the same. Species classification from acoustic
or image data uses supervised learning — typically a Convolutional Neural Network trained
on labelled recordings or photos. My VMS anomaly detection uses the same statistical
outlier logic you'd apply to flag unusual species sightings outside their known range.
The fleet health composite score is the same architecture as an environmental health
index — combining water temperature, species count, and turbidity into a single score.
The domain changes; the mathematics does not."

---

### Q: What Python libraries would you use for this at Feaver's Lane?
**A:**
- **scikit-learn** — classification (Random Forest, SVM), anomaly detection (Isolation Forest), clustering (K-Means)
- **pandas** — data manipulation and time series
- **NumPy** — numerical operations (the same mean/std/z-score I did manually in PHP)
- **TensorFlow / PyTorch** — deep learning for image-based species classification
- **GeoPandas** — spatial data analysis (GIS + pandas)
- **Rasterio / GDAL** — reading and processing satellite/raster imagery
- **Shapely** — geometric operations on spatial data
- **matplotlib / seaborn** — data visualisation

---

### Q: What is a REST API and how did you secure yours?
**A:** "A REST API is a web service that returns data (usually JSON) in response to
HTTP requests. I built 10 ML endpoints under /api/ml/. I secured them with Laravel
Sanctum — every request must include an Authorization: Bearer token obtained at login.
Additionally, I implemented role-based access control: fleet-wide endpoints like
/api/ml/dashboard are restricted to admin and manager roles. A driver can only access
insights for their own assigned vehicle."

---

### Q: What is the cold-start problem?
**A:** "The cold-start problem occurs when an ML model has no historical data to learn
from — like a new vehicle just registered in the system. My VMS handles this gracefully:
predictive maintenance returns 'Insufficient data' if fewer than 2 maintenance records
exist; anomaly detection requires at least 3 expense records; and EWMA returns ₦0
if no expense history exists. This is honest feedback rather than a misleading prediction."

---

## PART 7 — LIVE RESULTS FROM YOUR DASHBOARD (Verified April 23, 2026)

These are the real numbers your live system produced — reference these in the interview.

**Fleet Health (vms.basepan.com/ml-insights):**
- Fleet size: 2 vehicles
- Average fleet health: 93.8/100
- Toyota Corolla (ASB-212-EY): 100/100 — A Excellent
- Honda Civic (ASB-839-UI): 87.5/100 — A Excellent
- Critical vehicles: 0 | High risk: 0

**Driver Performance:**
- Driver User: 100/100 — A Outstanding
- Trip completion: 100% | Revenue: 100% | Safety: 100%

**API verified working:**
```
curl https://vms.basepan.com/api/ml/health/fleet
  -H "Authorization: Bearer <token>"
→ Returns JSON with fleet_count: 2, average_health: 93.8
```

---

## PART 8 — KEY NUMBERS TO KNOW BY HEART

| Fact | Number |
|---|---|
| ML algorithms | 5 |
| ML API endpoints | 10 |
| External ML libraries used | 0 |
| Lines in MLService.php | ~500 |
| User roles | 4 (admin, manager, vehicle_owner, driver) |
| Anomaly z-score threshold | 2.0 |
| EWMA weight (newest month) | 0.30 |
| Fleet health weights | 30 / 25 / 25 / 20 |
| Driver score weights | 40 / 35 / 25 |
| Minimum records for anomaly detection | 3 |
| Minimum records for maintenance prediction | 2 |
| Live URL | vms.basepan.com |
| Live fleet avg health | 93.8/100 |

---

## PART 9 — CLOSING STATEMENT

> "I believe the most valuable AI/ML skill is not knowing which library to import —
> it is understanding the mathematics well enough to implement it in any language
> the environment demands. Building VMS's ML engine in PHP on shared hosting forced
> me to work through every formula by hand: mean intervals, z-scores, weighted averages,
> coefficient of variation. That mathematical grounding means I can apply the same
> thinking to marine species classification, ecoregion health scoring, and spatial
> anomaly detection at Feaver's Lane — and then scale it up with Python's full ML
> ecosystem when the infrastructure supports it. I am not just someone who calls
> model.fit() — I understand what is happening inside."
