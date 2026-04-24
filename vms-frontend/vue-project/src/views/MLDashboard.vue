<template>
  <div class="ml-container">
    <h2 class="page-title">🤖 ML Insights Dashboard</h2>
    <p class="page-subtitle">AI-powered analytics — fleet health, cost forecasting, anomaly detection & driver performance</p>

    <div v-if="loading" class="loading-state">⏳ Loading ML insights...</div>
    <div v-if="error" class="error-state">⚠️ {{ error }}</div>

    <template v-if="!loading && !error">

      <!-- ── FLEET HEALTH ───────────────────────────────────────── -->
      <section class="section">
        <h3 class="section-title">🏥 Fleet Health Scores</h3>
        <div class="summary-bar">
          <div class="summary-chip blue">Fleet Size: {{ fleet.fleet_count }}</div>
          <div class="summary-chip green">Avg Health: {{ fleet.average_health }}/100</div>
          <div class="summary-chip red">Critical: {{ fleet.critical_count }}</div>
          <div class="summary-chip orange">High Risk: {{ fleet.high_risk_count }}</div>
        </div>

        <div class="table-wrapper">
          <table class="ml-table">
            <thead>
              <tr>
                <th>Vehicle</th>
                <th>Plate</th>
                <th>Driver</th>
                <th>Health Score</th>
                <th>Grade</th>
                <th>Next Maintenance</th>
                <th>Risk</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in fleet.vehicles" :key="v.vehicle_id">
                <td>{{ v.vehicle }}</td>
                <td class="mono">{{ v.plate_number }}</td>
                <td>{{ v.driver }}</td>
                <td>
                  <div class="score-bar-wrapper">
                    <div class="score-bar" :style="{ width: v.health_score + '%', background: scoreColor(v.health_score) }"></div>
                    <span class="score-label">{{ v.health_score }}</span>
                  </div>
                </td>
                <td><span class="badge" :class="gradeBadge(v.health_score)">{{ v.grade }}</span></td>
                <td>{{ v.next_maintenance ?? 'Insufficient data' }}</td>
                <td><span class="risk-chip" :class="riskClass(v.risk_level)">{{ v.risk_level }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ── COST FORECAST ─────────────────────────────────────── -->
      <section class="section">
        <h3 class="section-title">📈 Cost Forecast — {{ forecast.forecast_month }}</h3>
        <p class="section-sub">Total Fleet Forecast: <strong>₦{{ fmt(forecast.total_fleet_forecast) }}</strong></p>

        <div class="forecast-grid">
          <div
            v-for="v in forecast.vehicles"
            :key="v.vehicle_id"
            class="forecast-card"
            :class="alertClass(v.alert_level)"
          >
            <div class="forecast-vehicle">{{ vehicleName(v.vehicle_id) }}</div>
            <div class="forecast-amount">₦{{ fmt(v.forecasted_cost) }}</div>
            <div class="forecast-trend">Trend: {{ v.trend }}</div>
            <div class="forecast-alert text-xs mt-1">{{ v.alert_level }}</div>
            <div class="history-bars">
              <div
                v-for="h in v.history"
                :key="h.month"
                class="bar-col"
                :title="h.month + ': ₦' + fmt(h.amount)"
              >
                <div
                  class="bar-fill"
                  :style="{ height: barHeight(h.amount, v.history) + 'px' }"
                ></div>
                <div class="bar-label">{{ h.month.split(' ')[0] }}</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ── ANOMALY DETECTION ──────────────────────────────────── -->
      <section class="section">
        <h3 class="section-title">🚨 Expense Anomaly Detection</h3>
        <p class="section-sub">Total anomalies found: <strong>{{ anomalies.total_anomalies }}</strong></p>

        <div v-if="anomalies.total_anomalies === 0" class="no-anomalies">
          ✅ No expense anomalies detected across the fleet.
        </div>

        <div v-else class="table-wrapper">
          <table class="ml-table">
            <thead>
              <tr>
                <th>Vehicle</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Z-Score</th>
                <th>Severity</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="v in anomalies.vehicles" :key="v.vehicle_id">
                <tr v-for="a in v.anomalies" :key="a.id">
                  <td>{{ v.vehicle }}</td>
                  <td>{{ a.date }}</td>
                  <td class="font-semibold text-red-600">₦{{ fmt(a.amount) }}</td>
                  <td>{{ a.description }}</td>
                  <td class="mono">{{ a.z_score }}</td>
                  <td><span class="risk-chip" :class="severityClass(a.severity)">{{ a.severity }}</span></td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ── DRIVER PERFORMANCE ─────────────────────────────────── -->
      <section class="section">
        <h3 class="section-title">🧑‍✈️ Driver Performance Rankings</h3>
        <p class="section-sub">Fleet average: <strong>{{ drivers.average_score }}/100</strong></p>

        <div class="table-wrapper">
          <table class="ml-table">
            <thead>
              <tr>
                <th>Rank</th>
                <th>Driver</th>
                <th>Score</th>
                <th>Grade</th>
                <th>Trip Completion</th>
                <th>Revenue Score</th>
                <th>Safety Score</th>
                <th>Trips</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, i) in drivers.drivers" :key="d.driver_id">
                <td class="text-center font-bold text-gray-500">{{ i + 1 }}</td>
                <td>{{ d.driver_name }}</td>
                <td>
                  <div class="score-bar-wrapper">
                    <div class="score-bar" :style="{ width: d.performance_score + '%', background: scoreColor(d.performance_score) }"></div>
                    <span class="score-label">{{ d.performance_score }}</span>
                  </div>
                </td>
                <td><span class="badge" :class="gradeBadge(d.performance_score)">{{ d.grade }}</span></td>
                <td>{{ d.breakdown.trip_completion_rate }}%</td>
                <td>{{ d.breakdown.revenue_score }}%</td>
                <td>{{ d.breakdown.safety_score }}%</td>
                <td>{{ d.stats.completed_trips }}/{{ d.stats.total_trips }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

    </template>

    <p class="generated-at" v-if="generatedAt">Generated at: {{ generatedAt }}</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/axios'

const loading = ref(true)
const error = ref(null)
const fleet = ref({ fleet_count: 0, average_health: 0, critical_count: 0, high_risk_count: 0, vehicles: [] })
const forecast = ref({ forecast_month: '', total_fleet_forecast: 0, vehicles: [] })
const anomalies = ref({ total_anomalies: 0, vehicles: [] })
const drivers = ref({ average_score: 0, drivers: [] })
const generatedAt = ref(null)

const fetchML = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await axios.get('/ml/dashboard')
    fleet.value = res.data.fleet_health
    forecast.value = res.data.fleet_forecast
    anomalies.value = res.data.fleet_anomalies
    drivers.value = res.data.driver_scores
    generatedAt.value = new Date(res.data.generated_at).toLocaleString()
  } catch (err) {
    error.value = err.response?.data?.message ?? 'Failed to load ML dashboard.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchML)

// ── Helpers ──────────────────────────────────────────────────────
const fmt = (n) => Number(n ?? 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const scoreColor = (score) => {
  if (score >= 85) return '#16a34a'
  if (score >= 70) return '#2563eb'
  if (score >= 55) return '#d97706'
  if (score >= 40) return '#ea580c'
  return '#dc2626'
}

const gradeBadge = (score) => {
  if (score >= 85) return 'badge-green'
  if (score >= 70) return 'badge-blue'
  if (score >= 55) return 'badge-yellow'
  if (score >= 40) return 'badge-orange'
  return 'badge-red'
}

const riskClass = (risk) => ({
  'chip-green':  risk === 'Low',
  'chip-yellow': risk === 'Medium',
  'chip-orange': risk === 'High',
  'chip-red':    risk === 'Critical',
  'chip-gray':   risk === 'Unknown',
})

const severityClass = (s) => ({
  'chip-gray':   s === 'Normal',
  'chip-yellow': s === 'Medium',
  'chip-orange': s === 'High',
  'chip-red':    s === 'Critical',
})

const alertClass = (alert) => {
  if (alert?.startsWith('High'))   return 'fc-red'
  if (alert?.startsWith('Medium')) return 'fc-yellow'
  return 'fc-green'
}

const vehicleName = (id) => {
  const v = fleet.value.vehicles?.find(v => v.vehicle_id === id)
  return v ? `${v.vehicle} (${v.plate_number})` : `Vehicle #${id}`
}

const barHeight = (amount, history) => {
  const max = Math.max(...history.map(h => h.amount), 1)
  return Math.max(4, Math.round((amount / max) * 48))
}
</script>

<style scoped>
.ml-container { max-width: 1200px; margin: 0 auto; padding: 1rem; }
.page-title   { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; }
.page-subtitle{ font-size: 0.85rem; color: #6b7280; margin-bottom: 1.5rem; }

.loading-state{ text-align: center; color: #6b7280; padding: 2rem; }
.error-state  { background: #fef2f2; color: #dc2626; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; }

.section      { background: #fff; border-radius: 1rem; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 1px 6px rgba(0,0,0,.06); }
.section-title{ font-size: 1.1rem; font-weight: 700; margin-bottom: 0.75rem; }
.section-sub  { font-size: 0.85rem; color: #6b7280; margin-bottom: 1rem; }

.summary-bar  { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
.summary-chip { font-size: 0.8rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px; }
.summary-chip.blue   { background: #dbeafe; color: #1d4ed8; }
.summary-chip.green  { background: #dcfce7; color: #15803d; }
.summary-chip.red    { background: #fee2e2; color: #b91c1c; }
.summary-chip.orange { background: #ffedd5; color: #c2410c; }

.table-wrapper{ overflow-x: auto; }
.ml-table     { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.ml-table th  { background: #f9fafb; padding: 0.6rem 0.75rem; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.ml-table td  { padding: 0.6rem 0.75rem; border-bottom: 1px solid #f3f4f6; color: #374151; }
.ml-table tr:last-child td { border-bottom: none; }
.ml-table tr:hover td { background: #f9fafb; }
.mono { font-family: monospace; }

.score-bar-wrapper { display: flex; align-items: center; gap: 0.5rem; min-width: 120px; }
.score-bar  { height: 8px; border-radius: 4px; transition: width .3s; }
.score-label{ font-size: 0.8rem; font-weight: 600; white-space: nowrap; }

.badge       { font-size: 0.72rem; padding: 0.2rem 0.55rem; border-radius: 9999px; font-weight: 600; white-space: nowrap; }
.badge-green { background: #dcfce7; color: #15803d; }
.badge-blue  { background: #dbeafe; color: #1d4ed8; }
.badge-yellow{ background: #fef9c3; color: #854d0e; }
.badge-orange{ background: #ffedd5; color: #c2410c; }
.badge-red   { background: #fee2e2; color: #b91c1c; }

.risk-chip    { font-size: 0.72rem; padding: 0.2rem 0.55rem; border-radius: 9999px; font-weight: 600; }
.chip-green   { background: #dcfce7; color: #15803d; }
.chip-yellow  { background: #fef9c3; color: #854d0e; }
.chip-orange  { background: #ffedd5; color: #c2410c; }
.chip-red     { background: #fee2e2; color: #b91c1c; }
.chip-gray    { background: #f3f4f6; color: #6b7280; }

/* Forecast cards */
.forecast-grid   { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
.forecast-card   { border-radius: 0.75rem; padding: 1rem; }
.fc-green  { background: linear-gradient(135deg,#dcfce7,#bbf7d0); }
.fc-yellow { background: linear-gradient(135deg,#fef9c3,#fde68a); }
.fc-red    { background: linear-gradient(135deg,#fee2e2,#fecaca); }
.forecast-vehicle{ font-size: 0.75rem; color: #374151; font-weight: 500; margin-bottom: 0.25rem; }
.forecast-amount { font-size: 1.35rem; font-weight: 700; color: #111827; }
.forecast-trend  { font-size: 0.75rem; color: #6b7280; }
.forecast-alert  { font-size: 0.72rem; color: #374151; }

.history-bars    { display: flex; align-items: flex-end; gap: 3px; margin-top: 0.75rem; height: 56px; }
.bar-col         { display: flex; flex-direction: column; align-items: center; flex: 1; }
.bar-fill        { width: 100%; background: rgba(0,0,0,0.18); border-radius: 2px 2px 0 0; min-height: 4px; }
.bar-label       { font-size: 0.6rem; color: #6b7280; margin-top: 2px; }

.no-anomalies    { background: #dcfce7; color: #15803d; padding: 1rem; border-radius: 0.5rem; font-weight: 500; }
.generated-at    { text-align: center; font-size: 0.75rem; color: #9ca3af; margin-top: 1rem; }
</style>
