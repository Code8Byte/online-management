<template>
  <div class="dashboard-operator-view op-wrap">

    <!-- 1. Salam di Tengah -->
    <section class="op-hero">
      <div class="op-date-badge">📅 {{ todayFormatted }}</div>
      <h1 class="op-greeting">
        {{ greeting }}, <span class="op-user-name">{{ displayName }}</span>!
      </h1>
      <p class="op-greeting-sub">
        Ringkasan pencapaian OEE pribadi &amp; shortcut operasional Anda di Divisi Sortir.
      </p>
    </section>

    <!-- 2. Empat Tombol Aksi Disusun Horizontal -->
    <section class="op-actions-row">
      <!-- 1. Cost Control -->
      <div class="op-btn-card op-card-cc" @click="openModalCC" role="button" tabindex="0">
        <div class="op-btn-icon">📑</div>
        <div class="op-btn-text">
          <b>Cost Control</b>
          <small>Export Laporan CC</small>
        </div>
      </div>

      <!-- 2. LKH Produksi -->
      <div class="op-btn-card op-card-lkh" @click="openModalLkhOperator" role="button" tabindex="0">
        <div class="op-btn-icon">📗</div>
        <div class="op-btn-text">
          <b>LKH Produksi</b>
          <small>Export Excel LKH</small>
        </div>
      </div>

      <!-- 3. STB (Segera Hadir) -->
      <div class="op-btn-card op-card-disabled" @click="openBlankMenu('STB')" role="button" tabindex="0">
        <div class="op-btn-icon">📦</div>
        <div class="op-btn-text">
          <div class="d-flex align-center gap-03">
            <b>STB</b>
            <span class="op-tag-badge">Segera Hadir</span>
          </div>
          <small>Distribusi STB</small>
        </div>
      </div>

      <!-- 4. Kiriman (Segera Hadir) -->
      <div class="op-btn-card op-card-disabled" @click="openBlankMenu('Kiriman')" role="button" tabindex="0">
        <div class="op-btn-icon">🚚</div>
        <div class="op-btn-text">
          <div class="d-flex align-center gap-03">
            <b>Kiriman</b>
            <span class="op-tag-badge">Segera Hadir</span>
          </div>
          <small>Status Pengiriman</small>
        </div>
      </div>
    </section>

    <!-- 3. Ringkasan Metrik OEE (1 Bulan & 1 Minggu) -->
    <section class="op-kpi-grid">
      <!-- Card OEE 1 Bulan -->
      <div class="op-kpi-box highlight">
        <div class="op-kpi-top">
          <span class="op-eyebrow">Pencapaian OEE 1 Bulan</span>
          <span class="op-pill" :class="'tone-' + oeeMonthTone">{{ oeeMonthStatus }}</span>
        </div>
        <div class="op-kpi-main">
          <div class="op-kpi-val" :class="'txt-' + oeeMonthTone">
            {{ oeeMonth.toFixed(1) }}<small>%</small>
          </div>
          <div class="op-kpi-sub">
            Target: <b>85%</b> &middot; Selisih <b :class="oeeMonth >= 85 ? 'txt-ok' : 'txt-bad'">{{ (oeeMonth - 85) >= 0 ? '+' : '' }}{{ (oeeMonth - 85).toFixed(1) }}%</b>
          </div>
        </div>
        <div class="op-kpi-pillars">
          <div>
            <span>Availability</span>
            <b :class="'txt-' + rateTone(dataMonth.kpi?.availability, 90)">{{ (Number(dataMonth.kpi?.availability) || 0).toFixed(1) }}%</b>
            <small>Target 90%</small>
          </div>
          <div>
            <span>Performance</span>
            <b :class="'txt-' + rateTone(dataMonth.kpi?.performance, 95)">{{ (Number(dataMonth.kpi?.performance) || 0).toFixed(1) }}%</b>
            <small>Target 95%</small>
          </div>
          <div>
            <span>Quality OEE</span>
            <b :class="'txt-' + rateTone(dataMonth.kpi?.quality_oee, 99)">{{ (Number(dataMonth.kpi?.quality_oee) || 0).toFixed(1) }}%</b>
            <small>Target 99%</small>
          </div>
        </div>
      </div>

      <!-- Card OEE 1 Minggu -->
      <div class="op-kpi-box">
        <div class="op-kpi-top">
          <span class="op-eyebrow">Pencapaian OEE 1 Minggu (7 Hari)</span>
          <span class="op-pill" :class="'tone-' + oeeWeekTone">{{ oeeWeekStatus }}</span>
        </div>
        <div class="op-kpi-main">
          <div class="op-kpi-val" :class="'txt-' + oeeWeekTone">
            {{ oeeWeek.toFixed(1) }}<small>%</small>
          </div>
          <div class="op-kpi-sub">
            Target: <b>85%</b> &middot; Selisih <b :class="oeeWeek >= 85 ? 'txt-ok' : 'txt-bad'">{{ (oeeWeek - 85) >= 0 ? '+' : '' }}{{ (oeeWeek - 85).toFixed(1) }}%</b>
          </div>
        </div>
        <div class="op-kpi-pillars">
          <div>
            <span>Total Proses</span>
            <b>{{ formatNumber(dataWeek.kpi?.total_proses) }}</b>
            <small>lembar</small>
          </div>
          <div>
            <span>Produk Baik</span>
            <b class="txt-ok">{{ formatNumber(dataWeek.kpi?.good) }}</b>
            <small>lembar</small>
          </div>
          <div>
            <span>Reject Total</span>
            <b class="txt-bad">{{ (Number(dataWeek.kpi?.ng_percent) || 0).toFixed(2) }}%</b>
            <small>{{ formatNumber(totalRejectWeek) }} pcs</small>
          </div>
        </div>
      </div>
    </section>

    <!-- 4. Grafik Tren OEE (Khusus Akun Operator Ini) -->
    <section class="op-chart-card">
      <div class="op-chart-head">
        <div>
          <h3>Grafik Tren OEE {{ rangeMode === 'week' ? '1 Minggu (7 Hari)' : '1 Bulan (30 Hari)' }}</h3>
          <p>Data performa harian khusus akun <b>{{ displayName }}</b> vs Garis Target Standar 85%</p>
        </div>
        <div class="op-toggles">
          <button
            type="button"
            class="op-toggle-btn"
            :class="{ on: trendShow.availability }"
            @click="toggleTrend('availability')"
          >
            Availability (90%)
          </button>
          <button
            type="button"
            class="op-toggle-btn"
            :class="{ on: trendShow.performance }"
            @click="toggleTrend('performance')"
          >
            Performance (95%)
          </button>
          <div class="op-seg">
            <button type="button" :class="{ on: rangeMode === 'week' }" @click="setRangeMode('week')">1 Minggu</button>
            <button type="button" :class="{ on: rangeMode === 'month' }" @click="setRangeMode('month')">1 Bulan</button>
          </div>
        </div>
      </div>

      <div class="op-canvas-wrap">
        <canvas id="operatorOeeChartRef"></canvas>
        <div v-if="loading" class="op-empty">Memuat data grafik OEE...</div>
        <div v-else-if="!currentTimeline.length" class="op-empty">
          Belum ada catatan LKH untuk akun {{ displayName }} pada periode {{ rangeMode === 'week' ? '7 hari' : '30 hari' }} ini.
        </div>
      </div>

      <div class="op-chart-foot">
        <div class="op-legend-info">
          <span class="legend-dot dot-oee"></span> OEE Aktual
          <span class="legend-dot dot-target"></span> Garis Target OEE 85%
          <span v-if="trendShow.availability" class="legend-dot dot-avail"></span> <template v-if="trendShow.availability">Availability</template>
          <span v-if="trendShow.performance" class="legend-dot dot-perf"></span> <template v-if="trendShow.performance">Performance</template>
        </div>
        <span v-if="currentTimeline.length" class="op-muted">
          {{ currentTimeline.length }} hari kerja tercatat
        </span>
      </div>
    </section>

    <!-- Modal Export Cost Control (CC) -->
    <div v-if="showModalCC" class="modal-overlay" @click.self="showModalCC = false">
      <div class="modal-content" style="max-width: 480px;">
        <div class="d-flex justify-between align-center mb-1">
          <h3 style="margin: 0; font-size: 1.15rem;">📑 Export Cost Control (CC)</h3>
          <button type="button" class="btn btn-sm btn-secondary" @click="showModalCC = false" style="padding: 0.2rem 0.5rem;">✕</button>
        </div>

        <form @submit.prevent="submitExportCC('xlsx')">
          <div class="form-group mb-1">
            <label>Tanggal <span class="text-danger">*</span></label>
            <input type="date" v-model="formCC.tanggal" class="form-control" required />
          </div>

          <div class="form-group mb-1">
            <label>Pilihan Shift <span class="text-danger">*</span></label>
            <select v-model="formCC.shift" class="form-control" required>
              <option value="1">Shift 1</option>
              <option value="2">Shift 2</option>
              <option value="3">Shift 3</option>
            </select>
          </div>

          <div class="d-flex justify-between align-center gap-05 mt-1" style="border-top: 1px solid var(--border-light); padding-top: 1rem;">
            <button type="button" class="btn btn-secondary" @click="showModalCC = false">
              Batal
            </button>
            <div class="d-flex gap-05">
              <button type="button" class="btn btn-secondary" @click="submitExportCC('html')" title="Tampilan cetak / simpan langsung ke PDF">
                🖨️ Cetak / PDF
              </button>
              <button type="submit" class="btn btn-primary">
                📥 Download Excel
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Export LKH Produksi (Khusus Operator: Hanya Tanggal & Shift) -->
    <div v-if="showModalLkhOperator" class="modal-overlay" @click.self="showModalLkhOperator = false">
      <div class="modal-content" style="max-width: 480px;">
        <div class="d-flex justify-between align-center mb-1">
          <h3 style="margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 6px;">
            <span>📗</span> Export LKH Produksi
          </h3>
          <button type="button" class="btn btn-sm btn-secondary" @click="showModalLkhOperator = false" style="padding: 0.2rem 0.5rem;">✕</button>
        </div>

        <form @submit.prevent="submitExportLkhOperator('xlsx')">
          <div class="form-group mb-1">
            <label>Tanggal <span class="text-danger">*</span></label>
            <input type="date" v-model="formLkhOperator.tanggal" class="form-control" required />
          </div>

          <div class="form-group mb-1">
            <label>Pilihan Shift <span class="text-danger">*</span></label>
            <select v-model="formLkhOperator.shift" class="form-control" required>
              <option value="1">Shift 1</option>
              <option value="2">Shift 2</option>
              <option value="3">Shift 3</option>
            </select>
          </div>

          <div class="d-flex justify-between align-center gap-05 mt-1" style="border-top: 1px solid var(--border-light); padding-top: 1rem;">
            <button type="button" class="btn btn-secondary" @click="showModalLkhOperator = false">
              Batal
            </button>
            <div class="d-flex gap-05">
              <button type="submit" class="btn btn-success">
                <span>📥</span> Download Excel (.xlsx)
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue'
import Chart from 'chart.js/auto'
import { apiFetch, authState } from '../../utils/api'

const OEE_TARGET = 85
const loading = ref(false)
const rangeMode = ref('week') // 'week' | 'month'
const trendShow = reactive({ availability: false, performance: false })

const dataWeek = ref({ kpi: {}, trend_timeline: [] })
const dataMonth = ref({ kpi: {}, trend_timeline: [] })

let chartInstance = null

const displayName = computed(() => authState.value?.username || 'Operator')

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour >= 4 && hour < 11) return 'Selamat Pagi'
  if (hour >= 11 && hour < 15) return 'Selamat Siang'
  if (hour >= 15 && hour < 18) return 'Selamat Sore'
  return 'Selamat Malam'
})

const todayFormatted = computed(() => {
  return new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

const rateTone = (val, target) => {
  const n = Number(val) || 0
  if (n >= target) return 'ok'
  if (n >= target - 10) return 'warn'
  return 'bad'
}

const oeeMonth = computed(() => Number(dataMonth.value.kpi?.oee) || 0)
const oeeMonthTone = computed(() => rateTone(oeeMonth.value, 85))
const oeeMonthStatus = computed(() => (oeeMonth.value >= 85 ? 'Optimal' : oeeMonth.value >= 70 ? 'Waspada' : 'Kritis'))

const oeeWeek = computed(() => Number(dataWeek.value.kpi?.oee) || 0)
const oeeWeekTone = computed(() => rateTone(oeeWeek.value, 85))
const oeeWeekStatus = computed(() => (oeeWeek.value >= 85 ? 'Optimal' : oeeWeek.value >= 70 ? 'Waspada' : 'Kritis'))

const totalRejectWeek = computed(() => (Number(dataWeek.value.kpi?.reject) || 0) + (Number(dataWeek.value.kpi?.setup_reject) || 0))

const currentTimeline = computed(() => {
  return rangeMode.value === 'week' ? (dataWeek.value.trend_timeline || []) : (dataMonth.value.trend_timeline || [])
})

const formatNumber = (val) => {
  if (val === undefined || val === null || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}

const toLocalDate = (d) => {
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

const getLocalDate = () => toLocalDate(new Date())

const setRangeMode = (mode) => {
  rangeMode.value = mode
  renderChart()
}

const toggleTrend = (key) => {
  trendShow[key] = !trendShow[key]
  renderChart()
}

const renderChart = () => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
  const el = document.getElementById('operatorOeeChartRef')
  if (!el) return

  const tl = currentTimeline.value
  if (!tl.length) return

  const labels = tl.map(t => (t.tanggal || '').substring(5))

  const datasets = [
    {
      label: 'OEE Aktual',
      data: tl.map(t => t.oee),
      borderColor: '#4f46e5',
      backgroundColor: 'rgba(79, 70, 229, 0.12)',
      fill: true,
      tension: 0.3,
      borderWidth: 2.5,
      pointRadius: tl.length > 20 ? 1 : 4,
      pointBackgroundColor: '#4f46e5',
      pointBorderColor: '#fff',
      pointBorderWidth: 2
    },
    {
      label: 'Target OEE 85%',
      data: tl.map(() => OEE_TARGET),
      borderColor: '#e11d48',
      borderWidth: 2,
      borderDash: [6, 4],
      pointRadius: 0,
      fill: false
    }
  ]

  if (trendShow.availability) {
    datasets.push({
      label: 'Availability',
      data: tl.map(t => t.availability),
      borderColor: '#10b981',
      borderWidth: 1.8,
      pointRadius: 0,
      tension: 0.3,
      fill: false
    })
  }

  if (trendShow.performance) {
    datasets.push({
      label: 'Performance',
      data: tl.map(t => t.performance),
      borderColor: '#d97706',
      borderWidth: 1.8,
      pointRadius: 0,
      tension: 0.3,
      fill: false
    })
  }

  chartInstance = new Chart(el, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (c) => `${c.dataset.label}: ${Number(c.parsed.y).toFixed(1)}%`
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 11 }, color: '#64748b', maxTicksLimit: rangeMode.value === 'week' ? 7 : 12 }
        },
        y: {
          beginAtZero: true,
          suggestedMax: 100,
          grid: { color: '#f1f5f9' },
          border: { display: false },
          ticks: { callback: v => v + '%', font: { size: 11 }, color: '#64748b', stepSize: 25 }
        }
      }
    }
  })
}

const loadOperatorDashboard = async () => {
  loading.value = true
  const todayObj = new Date()
  const d7 = new Date()
  d7.setDate(d7.getDate() - 6)
  const d30 = new Date()
  d30.setDate(d30.getDate() - 29)

  const today = toLocalDate(todayObj)
  const weekAgo = toLocalDate(d7)
  const monthAgo = toLocalDate(d30)
  const user = displayName.value

  try {
    const [resWeek, resMonth] = await Promise.all([
      apiFetch(`dashboard.php?pic=${encodeURIComponent(user)}&start_date=${weekAgo}&end_date=${today}`),
      apiFetch(`dashboard.php?pic=${encodeURIComponent(user)}&start_date=${monthAgo}&end_date=${today}`)
    ])
    dataWeek.value = resWeek || { kpi: {}, trend_timeline: [] }
    dataMonth.value = resMonth || { kpi: {}, trend_timeline: [] }
    await nextTick()
    renderChart()
  } catch (err) {
    console.error('Failed to load operator dashboard', err)
  } finally {
    loading.value = false
  }
}

// State Modal CC
const showModalCC = ref(false)
const formCC = ref({
  tanggal: getLocalDate(),
  shift: '1'
})

const openModalCC = () => {
  formCC.value.tanggal = getLocalDate()
  formCC.value.shift = '1'
  showModalCC.value = true
}

const submitExportCC = (format = 'xlsx') => {
  const query = new URLSearchParams()
  if (formCC.value.tanggal) query.append('tanggal', formCC.value.tanggal)
  if (formCC.value.shift) query.append('shift', formCC.value.shift)
  if (format === 'xlsx') {
    query.append('format', 'xlsx')
  }
  window.open(`/api/export_cc.php?${query.toString()}`, '_blank')
  showModalCC.value = false
}

// State Modal LKH Produksi Operator
const showModalLkhOperator = ref(false)
const formLkhOperator = ref({
  tanggal: getLocalDate(),
  shift: '1'
})

const openModalLkhOperator = () => {
  formLkhOperator.value = {
    tanggal: getLocalDate(),
    shift: '1'
  }
  showModalLkhOperator.value = true
}

const submitExportLkhOperator = (format = 'xlsx') => {
  const query = new URLSearchParams()
  if (formLkhOperator.value.tanggal) {
    query.append('start_date', formLkhOperator.value.tanggal)
    query.append('end_date', formLkhOperator.value.tanggal)
  }
  if (formLkhOperator.value.shift) {
    query.append('shift', formLkhOperator.value.shift)
  }
  if (format === 'csv') {
    query.append('format', 'csv')
  }
  window.open(`/api/export.php?${query.toString()}`, '_blank')
  showModalLkhOperator.value = false
}

const openBlankMenu = (menuName) => {
  alert(`Menu "${menuName}" masih dalam tahap persiapan / segera hadir.`)
}

onMounted(() => {
  loadOperatorDashboard()
})

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})
</script>

<style scoped>
.op-wrap {
  --c-brand: #4f46e5;
  --c-brand-2: #8b5cf6;
  --c-ok: #10b981;
  --c-warn: #d97706;
  --c-bad: #e11d48;
  --c-ink: #15172b;
  --c-text: #3a3f5c;
  --c-muted: #666c8a;
  --c-line: #e4e5f1;
  --c-soft: #eef0fb;
  max-width: 1040px;
  margin: 0.5rem auto 2rem;
  color: var(--c-text);
}

.txt-ok { color: var(--c-ok); }
.txt-warn { color: var(--c-warn); }
.txt-bad { color: var(--c-bad); }

.op-eyebrow {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--c-muted);
}
.op-muted {
  font-size: 0.78rem;
  color: var(--c-muted);
}
.gap-03 { gap: 0.35rem; }

/* 1. Hero Greeting */
.op-hero {
  text-align: center;
  padding: 1.5rem 1.25rem;
  margin-bottom: 1.25rem;
  background: linear-gradient(135deg, #ffffff 0%, #f8f8fe 60%, #eef0fb 100%);
  border: 1px solid #e2e4f0;
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(79, 70, 229, 0.06);
  position: relative;
  overflow: hidden;
}
.op-hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #4f46e5, #8b5cf6);
}
.op-date-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #fff;
  border: 1px solid #c7d2fe;
  color: #4338ca;
  font-size: 0.76rem;
  font-weight: 700;
  padding: 3px 12px;
  border-radius: 99px;
  margin-bottom: 0.6rem;
  box-shadow: 0 1px 3px rgba(79, 70, 229, 0.08);
}
.op-greeting {
  margin: 0;
  font-size: 1.9rem;
  font-weight: 800;
  color: #15172b;
  letter-spacing: -0.02em;
}
.op-user-name {
  color: #4f46e5;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.op-greeting-sub {
  margin: 0.45rem 0 0;
  font-size: 0.88rem;
  color: #666c8a;
}

/* 2. Empat Tombol Aksi Horizontal */
.op-actions-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.85rem;
  margin-bottom: 1.25rem;
}
.op-btn-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  background: #fff;
  border: 1px solid var(--c-line);
  border-radius: 12px;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
}
.op-btn-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.1);
}
.op-card-cc {
  border-color: #c7d2fe;
  background: linear-gradient(135deg, #ffffff 0%, #f5f7ff 100%);
}
.op-card-cc .op-btn-icon {
  background: #eef2ff;
  color: #4f46e5;
}
.op-card-lkh {
  border-color: #bbf7d0;
  background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
}
.op-card-lkh .op-btn-icon {
  background: #ecfdf5;
  color: #059669;
}
.op-card-disabled {
  border-style: dashed;
  background: #f8fafc;
  opacity: 0.9;
}
.op-card-disabled .op-btn-icon {
  background: #f1f5f9;
  color: #64748b;
}
.op-btn-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  flex: none;
}
.op-btn-text {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.op-btn-text b {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--c-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.op-btn-text small {
  font-size: 0.74rem;
  color: var(--c-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.op-tag-badge {
  background: #e2e8f0;
  color: #64748b;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 4px;
}

/* 3. Ringkasan Metrik OEE Grid */
.op-kpi-grid {
  display: grid;
  grid-template-columns: 1.1fr 1fr;
  gap: 0.9rem;
  margin-bottom: 1.25rem;
}
.op-kpi-box {
  background: #fff;
  border: 1px solid var(--c-line);
  border-radius: 14px;
  padding: 1.1rem 1.25rem;
  box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.op-kpi-box.highlight {
  background: linear-gradient(165deg, #ffffff 0%, #f8f8fe 60%, #f3f2fd 100%);
  border-color: #c7d2fe;
}
.op-kpi-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.op-pill {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 99px;
}
.op-pill.tone-ok { background: #d1fae5; color: #065f46; }
.op-pill.tone-warn { background: #fef3c7; color: #92400e; }
.op-pill.tone-bad { background: #fee2e2; color: #991b1b; }

.op-kpi-main {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.op-kpi-val {
  font-size: 2.6rem;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.03em;
}
.op-kpi-val small {
  font-size: 1.3rem;
  font-weight: 700;
  margin-left: 2px;
}
.op-kpi-sub {
  font-size: 0.8rem;
  color: var(--c-text);
}

.op-kpi-pillars {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
  padding-top: 0.65rem;
  border-top: 1px solid var(--c-line);
}
.op-kpi-pillars > div {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.op-kpi-pillars span {
  font-size: 0.72rem;
  color: var(--c-muted);
}
.op-kpi-pillars b {
  font-size: 1rem;
  font-weight: 800;
  color: var(--c-ink);
}
.op-kpi-pillars small {
  font-size: 0.68rem;
  color: var(--c-muted);
}

/* 4. Chart Card */
.op-chart-card {
  background: #fff;
  border: 1px solid var(--c-line);
  border-radius: 14px;
  padding: 1.1rem 1.25rem;
  box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05);
}
.op-chart-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 0.85rem;
  flex-wrap: wrap;
}
.op-chart-head h3 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--c-ink);
}
.op-chart-head p {
  margin: 3px 0 0;
  font-size: 0.76rem;
  color: var(--c-muted);
}
.op-toggles {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-wrap: wrap;
}
.op-toggle-btn {
  height: 28px;
  padding: 0 0.65rem;
  font-size: 0.72rem;
  font-weight: 600;
  background: #fff;
  border: 1px solid var(--c-line);
  color: var(--c-muted);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s;
}
.op-toggle-btn:hover {
  background: var(--c-soft);
}
.op-toggle-btn.on {
  background: #eef2ff;
  color: var(--c-brand);
  border-color: #c7d2fe;
}
.op-seg {
  display: inline-flex;
  background: var(--c-soft);
  border-radius: 6px;
  padding: 2px;
}
.op-seg button {
  border: none;
  background: transparent;
  padding: 0 0.65rem;
  height: 24px;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--c-muted);
  border-radius: 4px;
  cursor: pointer;
}
.op-seg button.on {
  background: #fff;
  color: var(--c-brand);
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.1);
}

.op-canvas-wrap {
  position: relative;
  height: 280px;
  width: 100%;
}
.op-empty {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.82rem;
  color: var(--c-muted);
  background: rgba(255, 255, 255, 0.9);
}

.op-chart-foot {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.65rem;
  padding-top: 0.65rem;
  border-top: 1px solid var(--c-line);
  font-size: 0.74rem;
  color: var(--c-muted);
  flex-wrap: wrap;
}
.op-legend-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.legend-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-right: 2px;
}
.dot-oee { background: #4f46e5; }
.dot-target { background: #e11d48; }
.dot-avail { background: #10b981; }
.dot-perf { background: #d97706; }

/* Responsive */
@media (max-width: 900px) {
  .op-actions-row {
    grid-template-columns: repeat(2, 1fr);
  }
  .op-kpi-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .op-greeting {
    font-size: 1.5rem;
  }
  .op-actions-row {
    grid-template-columns: 1fr;
  }
  .op-kpi-main {
    flex-direction: column;
    align-items: flex-start;
  }
  .op-chart-head {
    flex-direction: column;
  }
  .op-toggles {
    width: 100%;
  }
}
</style>
