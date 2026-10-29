<template>
  <div class="op-wrap">

    <!-- 1. Salam -->
    <section class="op-hero">
      <div class="op-date-badge">{{ todayFormatted }}</div>
      <h1 class="op-greeting">
        {{ greeting }}, <span class="op-user-name">{{ displayName }}</span>
      </h1>
    </section>

    <!-- 2. Tombol aksi -->
    <section class="op-actions-row">
      <button type="button" class="op-act act-cc" @click="openModalCC">
        <span class="op-act-icon">CC</span>
        <span class="op-act-text">
          <b>Cost Control</b>
          <small>Export laporan CC</small>
        </span>
      </button>

      <button type="button" class="op-act act-lkh" @click="openModalLkhOperator">
        <span class="op-act-icon">XL</span>
        <span class="op-act-text">
          <b>LKH Produksi</b>
          <small>Export Excel LKH</small>
        </span>
      </button>

      <button type="button" class="op-act act-soon" @click="openBlankMenu('STB')">
        <span class="op-act-icon">ST</span>
        <span class="op-act-text">
          <b>STB <i class="op-soon">Segera hadir</i></b>
          <small>Distribusi STB</small>
        </span>
      </button>

      <button type="button" class="op-act act-soon" @click="openBlankMenu('Kiriman')">
        <span class="op-act-icon">KR</span>
        <span class="op-act-text">
          <b>Kiriman <i class="op-soon">Segera hadir</i></b>
          <small>Status pengiriman</small>
        </span>
      </button>
    </section>

    <!-- 3. OEE bulan ini -->
    <section class="op-hero-oee">
      <div class="op-oee">
        <div class="op-oee-top">
          <span class="op-eyebrow">OEE {{ monthLabel }}</span>
          <span class="op-pill" :class="'tone-' + oeeTone">{{ oeeStatus }}</span>
        </div>
        <div class="op-oee-value" :class="'txt-' + oeeTone">{{ oeeVal.toFixed(1) }}<small>%</small></div>
        <div class="op-track op-track-lg">
          <div class="op-fill" :class="'bg-' + oeeTone" :style="{ width: Math.min(100, oeeVal) + '%' }"></div>
          <div class="op-target-mark" style="left: 85%"><span>85%</span></div>
        </div>
        <div class="op-oee-foot">
          <span v-if="oeeGap >= 0">Di atas target <b class="txt-ok">+{{ oeeGap.toFixed(1) }} poin</b></span>
          <span v-else>Di bawah target <b class="txt-bad">{{ oeeGap.toFixed(1) }} poin</b></span>
          <span class="op-muted">Target 85%</span>
        </div>
      </div>

      <div class="op-pillars">
        <div v-for="p in pillars" :key="p.key" class="op-pillar">
          <div class="op-pillar-head">
            <span class="op-eyebrow">{{ p.label }}</span>
            <span class="op-pillar-val" :class="'txt-' + p.tone">{{ p.value.toFixed(1) }}%</span>
          </div>
          <div class="op-track">
            <div class="op-fill" :class="'bg-' + p.tone" :style="{ width: Math.min(100, p.value) + '%' }"></div>
            <div class="op-target-mark" :style="{ left: p.target + '%' }"></div>
          </div>
          <div class="op-pillar-foot">
            <span class="op-muted">Target {{ p.target }}%</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Ringkasan volume bulan ini -->
    <section class="op-stats">
      <div class="op-stat">
        <span class="op-eyebrow">Total proses</span>
        <b>{{ formatNumber(dataMonth.kpi?.total_proses) }}</b><small>lembar</small>
      </div>
      <div class="op-stat">
        <span class="op-eyebrow">Produk baik</span>
        <b class="txt-ok">{{ formatNumber(dataMonth.kpi?.good) }}</b><small>lembar</small>
      </div>
      <div class="op-stat">
        <span class="op-eyebrow">Reject total</span>
        <b class="txt-bad">{{ (Number(dataMonth.kpi?.ng_percent) || 0).toFixed(2) }}%</b>
        <small>{{ formatNumber(totalRejectMonth) }} pcs</small>
      </div>
    </section>

    <!-- 4. Grafik OEE bulan ini -->
    <section class="op-card">
      <div class="op-card-head">
        <div>
          <h3>Tren OEE {{ monthLabel }}</h3>
          <p>Capaian harian dibanding target 85%</p>
        </div>
        <div class="op-toggles">
          <button type="button" :class="{ on: trendShow.availability }" @click="toggleTrend('availability')">Availability</button>
          <button type="button" :class="{ on: trendShow.performance }" @click="toggleTrend('performance')">Performance</button>
        </div>
      </div>

      <div class="op-canvas-wrap">
        <canvas id="operatorOeeChartRef"></canvas>
        <div v-if="loading" class="op-empty">Memuat data grafik OEE...</div>
        <div v-else-if="!currentTimeline.length" class="op-empty">
          Belum ada catatan LKH atas nama {{ displayName }} pada {{ monthLabel }}.
        </div>
      </div>

      <div class="op-card-foot">
        <div class="op-legend">
          <span><i class="dot dot-oee"></i>OEE aktual</span>
          <span><i class="dot dot-target"></i>Target 85%</span>
          <span v-if="trendShow.availability"><i class="dot dot-avail"></i>Availability</span>
          <span v-if="trendShow.performance"><i class="dot dot-perf"></i>Performance</span>
        </div>
        <span v-if="currentTimeline.length" class="op-muted">{{ currentTimeline.length }} hari kerja tercatat</span>
      </div>
    </section>

    <!-- Modal Export Cost Control (CC) -->
    <div v-if="showModalCC" class="modal-overlay" :class="{ 'bottom-sheet-active': isMobileScreen }" @click.self="showModalCC = false">
      <div class="modal-content op-modal">
        <div class="op-modal-head">
          <h3>Export Cost Control (CC)</h3>
          <button type="button" class="op-btn op-btn-ghost op-btn-sm" @click="showModalCC = false">✕</button>
        </div>

        <form @submit.prevent="submitExportCC('xlsx')">
          <div class="op-form-grid">
            <div class="op-field">
              <label>Tanggal *</label>
              <input type="date" v-model="formCC.tanggal" class="op-input" required />
            </div>
            <div class="op-field">
              <label>Shift *</label>
              <select v-model="formCC.shift" class="op-input" required>
                <option value="1">Shift 1</option>
                <option value="2">Shift 2</option>
                <option value="3">Shift 3</option>
              </select>
            </div>
          </div>

          <div class="op-modal-actions">
            <button type="button" class="op-btn op-btn-ghost" @click="showModalCC = false">Batal</button>
            <div class="op-row-gap">
              <button type="button" class="op-btn op-btn-ghost" @click="submitExportCC('html')" title="Tampilan cetak / simpan ke PDF">Cetak / PDF</button>
              <button type="submit" class="op-btn op-btn-primary">Download Excel</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Export LKH Produksi -->
    <div v-if="showModalLkhOperator" class="modal-overlay" :class="{ 'bottom-sheet-active': isMobileScreen }" @click.self="showModalLkhOperator = false">
      <div class="modal-content op-modal">
        <div class="op-modal-head">
          <h3>Export LKH Produksi</h3>
          <button type="button" class="op-btn op-btn-ghost op-btn-sm" @click="showModalLkhOperator = false">✕</button>
        </div>

        <form @submit.prevent="submitExportLkhOperator('xlsx')">
          <div class="op-form-grid">
            <div class="op-field">
              <label>Tanggal *</label>
              <input type="date" v-model="formLkhOperator.tanggal" class="op-input" required />
            </div>
            <div class="op-field">
              <label>Shift *</label>
              <select v-model="formLkhOperator.shift" class="op-input" required>
                <option value="1">Shift 1</option>
                <option value="2">Shift 2</option>
                <option value="3">Shift 3</option>
              </select>
            </div>
          </div>

          <div class="op-modal-actions">
            <button type="button" class="op-btn op-btn-ghost" @click="showModalLkhOperator = false">Batal</button>
            <button type="submit" class="op-btn op-btn-primary">Download Excel (.xlsx)</button>
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
const trendShow = reactive({ availability: false, performance: false })

const dataMonth = ref({ kpi: {}, trend_timeline: [] })

let chartInstance = null

const isMobileScreen = ref(false)
const checkMobileScreen = () => { isMobileScreen.value = window.innerWidth <= 768 }

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

const monthLabel = computed(() => {
  return new Date().toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

const rateTone = (val, target) => {
  const n = Number(val) || 0
  if (n >= target) return 'ok'
  if (n >= target - 10) return 'warn'
  return 'bad'
}

const oeeVal = computed(() => Number(dataMonth.value.kpi?.oee) || 0)
const oeeTone = computed(() => (oeeVal.value >= 85 ? 'ok' : oeeVal.value >= 70 ? 'warn' : 'bad'))
const oeeStatus = computed(() => (oeeVal.value >= 85 ? 'Optimal' : oeeVal.value >= 70 ? 'Waspada' : 'Kritis'))
const oeeGap = computed(() => oeeVal.value - OEE_TARGET)

const pillars = computed(() => {
  const k = dataMonth.value.kpi || {}
  const a = Number(k.availability) || 0
  const p = Number(k.performance) || 0
  const q = Number(k.quality_oee) || 0
  return [
    { key: 'a', label: 'Availability', value: a, target: 90, tone: rateTone(a, 90) },
    { key: 'p', label: 'Performance', value: p, target: 95, tone: rateTone(p, 95) },
    { key: 'q', label: 'Quality', value: q, target: 99, tone: rateTone(q, 99) }
  ]
})

const totalRejectMonth = computed(() => (Number(dataMonth.value.kpi?.reject) || 0) + (Number(dataMonth.value.kpi?.setup_reject) || 0))

const currentTimeline = computed(() => dataMonth.value.trend_timeline || [])

const formatNumber = (val) => {
  if (val === undefined || val === null || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}

const toLocalDate = (d) => {
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

const getLocalDate = () => toLocalDate(new Date())

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
      backgroundColor: 'rgba(79, 70, 229, 0.10)',
      fill: true,
      tension: 0.3,
      borderWidth: 2.5,
      pointRadius: tl.length > 20 ? 1 : 3,
      pointBackgroundColor: '#4f46e5',
      pointBorderColor: '#fff',
      pointBorderWidth: 2
    },
    {
      label: 'Target OEE 85%',
      data: tl.map(() => OEE_TARGET),
      borderColor: '#e11d48',
      borderWidth: 1.5,
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
      borderWidth: 1.5,
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
      borderWidth: 1.5,
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
          ticks: { font: { size: 10 }, color: '#666c8a', maxTicksLimit: 12 }
        },
        y: {
          beginAtZero: true,
          suggestedMax: 100,
          grid: { color: '#eef0f8' },
          border: { display: false },
          ticks: { callback: v => v + '%', font: { size: 10 }, color: '#666c8a', stepSize: 25 }
        }
      }
    }
  })
}

// OEE dihitung per bulan berjalan: tanggal 1 sampai hari ini
const loadOperatorDashboard = async () => {
  loading.value = true
  const now = new Date()
  const monthStart = new Date(now.getFullYear(), now.getMonth(), 1)
  const start = toLocalDate(monthStart)
  const end = toLocalDate(now)
  const user = displayName.value

  try {
    const res = await apiFetch(`dashboard.php?pic=${encodeURIComponent(user)}&start_date=${start}&end_date=${end}`)
    dataMonth.value = res || { kpi: {}, trend_timeline: [] }
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

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    showModalCC.value = false
    showModalLkhOperator.value = false
  }
}

onMounted(() => {
  checkMobileScreen()
  window.addEventListener('resize', checkMobileScreen)
  document.addEventListener('keydown', handleKeyDown)
  loadOperatorDashboard()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobileScreen)
  document.removeEventListener('keydown', handleKeyDown)
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
.bg-ok { background: var(--c-ok); }
.bg-warn { background: var(--c-warn); }
.bg-bad { background: var(--c-bad); }

.op-eyebrow { font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--c-muted); }
.op-muted { font-size: 0.78rem; color: var(--c-muted); }
.op-row-gap { display: flex; gap: 0.5rem; }

/* ---------- Hero salam ---------- */
.op-hero {
  text-align: center;
  padding: 1.25rem 1rem;
  margin-bottom: 0.9rem;
  background: linear-gradient(165deg, #ffffff 0%, #f8f8fe 60%, #f3f2fd 100%);
  border: 1px solid var(--c-line);
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05);
  position: relative;
  overflow: hidden;
}
.op-hero::before {
  content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
  background: linear-gradient(90deg, var(--c-brand), var(--c-brand-2));
}
.op-date-badge {
  display: inline-block;
  background: #fff; border: 1px solid #c7d2fe; color: #4338ca;
  font-size: 0.74rem; font-weight: 700; padding: 3px 12px; border-radius: 99px; margin-bottom: 0.55rem;
}
.op-greeting { margin: 0; font-size: 1.8rem; font-weight: 800; color: var(--c-ink); letter-spacing: -0.02em; }
.op-user-name {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}

/* ---------- Tombol aksi ---------- */
.op-actions-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.6rem; margin-bottom: 0.9rem; }
.op-act {
  display: flex; align-items: center; gap: 0.7rem; width: 100%;
  padding: 0.65rem 0.85rem; text-align: left; font-family: inherit;
  background: #fff; border: 1px solid var(--c-line); border-radius: 12px;
  box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05);
  cursor: pointer; transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
}
.op-act:hover { background: var(--c-soft); box-shadow: 0 4px 14px rgba(21, 23, 55, 0.08); }
.op-act-icon {
  flex: none; width: 36px; height: 36px; border-radius: 9px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 0.74rem; font-weight: 800; letter-spacing: 0.02em;
}
.act-cc .op-act-icon { background: #eef2ff; color: var(--c-brand); }
.act-lkh .op-act-icon { background: #d1fae5; color: #065f46; }
.act-soon .op-act-icon { background: #f1f5f9; color: #64748b; }
.act-soon { border-style: dashed; }
.op-act-text { display: flex; flex-direction: column; min-width: 0; }
.op-act-text b { font-size: 0.86rem; font-weight: 700; color: var(--c-ink); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.op-act-text small { font-size: 0.72rem; color: var(--c-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.op-soon { font-style: normal; font-size: 0.62rem; font-weight: 700; padding: 1px 6px; border-radius: 99px; background: #e2e8f0; color: #64748b; }

/* ---------- OEE bulan ini ---------- */
.op-hero-oee { display: grid; grid-template-columns: 1.1fr 2fr; gap: 0.9rem; margin-bottom: 0.9rem; }
.op-oee, .op-pillars, .op-stats, .op-card {
  background: #fff; border: 1px solid var(--c-line); border-radius: 14px;
  box-shadow: 0 2px 10px rgba(21, 23, 55, 0.05);
}
.op-oee {
  padding: 1.1rem 1.25rem; display: flex; flex-direction: column; gap: 0.5rem;
  background: linear-gradient(165deg, #ffffff 0%, #f8f8fe 60%, #f3f2fd 100%);
  position: relative; overflow: hidden;
}
.op-oee::before {
  content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
  background: linear-gradient(90deg, var(--c-brand), var(--c-brand-2));
}
.op-oee-top { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; }
.op-oee-value { font-size: 3.2rem; font-weight: 800; line-height: 1; letter-spacing: -0.03em; }
.op-oee-value small { font-size: 1.4rem; font-weight: 700; margin-left: 2px; }
.op-oee-foot { display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--c-text); }

.op-pill { font-size: 0.72rem; font-weight: 700; padding: 3px 9px; border-radius: 99px; }
.op-pill.tone-ok { background: #d1fae5; color: #065f46; }
.op-pill.tone-warn { background: #fef3c7; color: #92400e; }
.op-pill.tone-bad { background: #fee2e2; color: #991b1b; }

.op-track { position: relative; height: 8px; background: #eef2f7; border-radius: 99px; }
.op-track-lg { height: 12px; margin-top: 1.1rem; }
.op-fill { height: 100%; border-radius: 99px; transition: width 0.4s ease; }
.op-target-mark { position: absolute; top: -3px; bottom: -3px; width: 2px; background: var(--c-ink); border-radius: 2px; }
.op-target-mark span { position: absolute; top: -17px; left: 50%; transform: translateX(-50%); font-size: 0.65rem; font-weight: 700; color: var(--c-ink); }

.op-pillars { display: grid; grid-template-columns: repeat(3, 1fr); padding: 0.4rem 0; }
.op-pillar { padding: 0.8rem 1.1rem; display: flex; flex-direction: column; gap: 0.5rem; justify-content: center; }
.op-pillar + .op-pillar { border-left: 1px solid var(--c-line); }
.op-pillar-head { display: flex; justify-content: space-between; align-items: baseline; }
.op-pillar-val { font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; }

/* ---------- Ringkasan volume ---------- */
.op-stats { display: grid; grid-template-columns: repeat(3, 1fr); margin-bottom: 0.9rem; }
.op-stat { padding: 0.75rem 1.1rem; display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 6px; }
.op-stat + .op-stat { border-left: 1px solid var(--c-line); }
.op-stat .op-eyebrow { width: 100%; margin-bottom: 2px; }
.op-stat b { font-size: 1.3rem; font-weight: 800; color: var(--c-ink); letter-spacing: -0.01em; }
.op-stat small { font-size: 0.76rem; color: var(--c-muted); }

/* ---------- Grafik ---------- */
.op-card { padding: 1rem 1.1rem; }
.op-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.75rem; flex-wrap: wrap; }
.op-card-head h3 { margin: 0; font-size: 0.98rem; font-weight: 700; color: var(--c-ink); }
.op-card-head p { margin: 2px 0 0; font-size: 0.75rem; color: var(--c-muted); }
.op-toggles { display: flex; gap: 4px; }
.op-toggles button {
  border: 1px solid var(--c-line); background: #fff; color: var(--c-muted);
  font-size: 0.7rem; font-weight: 600; padding: 3px 9px; border-radius: 99px; cursor: pointer;
}
.op-toggles button.on { background: #eef2ff; border-color: #c7d2fe; color: var(--c-brand); }

.op-canvas-wrap { position: relative; height: 280px; width: 100%; }
.op-empty {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; text-align: center;
  padding: 0 1rem; font-size: 0.82rem; color: var(--c-muted); background: rgba(255, 255, 255, 0.88);
}
.op-card-foot {
  display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;
  margin-top: 0.6rem; padding-top: 0.6rem; border-top: 1px solid var(--c-line);
}
.op-legend { display: flex; align-items: center; gap: 0.9rem; flex-wrap: wrap; font-size: 0.74rem; color: var(--c-muted); }
.op-legend span { display: inline-flex; align-items: center; gap: 5px; }
.dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
.dot-oee { background: #4f46e5; }
.dot-target { background: #e11d48; }
.dot-avail { background: #10b981; }
.dot-perf { background: #d97706; }

/* ---------- Tombol & input (modal) ---------- */
.op-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  height: 34px; padding: 0 0.85rem; font-size: 0.82rem; font-weight: 600;
  border-radius: 8px; border: 1px solid transparent; cursor: pointer; white-space: nowrap;
  transition: background 0.15s, border-color 0.15s;
}
.op-btn-sm { height: 30px; padding: 0 0.6rem; }
.op-btn-primary { background: linear-gradient(135deg, #6366f1, var(--c-brand)); color: #fff; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3); }
.op-btn-primary:hover { background: linear-gradient(135deg, #5457ea, #4338ca); }
.op-btn-ghost { background: #fff; color: var(--c-text); border-color: var(--c-line); }
.op-btn-ghost:hover { background: var(--c-soft); }

.op-input {
  width: 100%; height: 34px; padding: 0 0.6rem; font-size: 0.82rem;
  border: 1px solid var(--c-line); border-radius: 8px; background: #fff; color: var(--c-ink); min-width: 0;
}
.op-input:focus { outline: 2px solid rgba(79, 70, 229, 0.2); border-color: var(--c-brand); }
.op-field { display: flex; flex-direction: column; gap: 3px; }
.op-field label { font-size: 0.72rem; font-weight: 700; color: var(--c-muted); }

.op-modal { max-width: 480px; width: 95%; }
.op-modal-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 0.9rem; }
.op-modal-head h3 { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--c-ink); }
.op-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.op-modal-actions { display: flex; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; margin-top: 1.1rem; padding-top: 0.75rem; border-top: 1px solid var(--c-line); }

/* ---------- Responsive ---------- */
@media (max-width: 900px) {
  .op-actions-row { grid-template-columns: repeat(2, 1fr); }
  .op-hero-oee { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .op-pillars { grid-template-columns: 1fr; }
  .op-pillar + .op-pillar { border-left: none; border-top: 1px solid var(--c-line); }
  .op-oee-value { font-size: 2.6rem; }
  .op-form-grid { grid-template-columns: 1fr; }
  .op-canvas-wrap { height: 240px; }

  .bottom-sheet-active { align-items: flex-end !important; padding: 0 !important; }
  .op-modal {
    max-width: 100% !important; width: 100% !important; max-height: 85vh !important;
    border-radius: 14px 14px 0 0 !important; animation: opSlideUp 0.25s ease-out;
  }
}

@media (max-width: 600px) {
  .op-greeting { font-size: 1.45rem; }
  .op-act-text small { display: none; }
  .op-stat { padding: 0.65rem 0.75rem; }
  .op-stat b { font-size: 1.1rem; }
}

@keyframes opSlideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
</style>