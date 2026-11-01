<template>
  <div class="op-wrap" :style="{ '--c-brand': brandColor, '--c-brand-2': brandColor2 }">

    <!-- 1. Salam & Tanggal (Gaya Dashboard Operator) -->
    <section class="op-hero">
      <div class="op-date-badge">{{ todayFormatted }} • {{ divisionName }}</div>
      <h1 class="op-greeting">
        {{ greeting }}, <span class="op-user-name">{{ displayName }}</span>
      </h1>
      <p class="op-sub-greeting">
        Panel Pemantauan Efisiensi Operasional (OEE) {{ divisionName }} • {{ currentShiftText }}
      </p>
    </section>

    <!-- 2. Tombol Aksi Operator (CC, LKH, STB, Kiriman) - DITANGGUHKAN SEMENTARA -->
    <section class="op-actions-row">
      <button type="button" class="op-act act-cc op-act-suspended" @click="handleSuspendedExport('Cost Control (CC)')">
        <span class="op-act-icon">CC</span>
        <span class="op-act-text">
          <b>Cost Control <i class="op-soon">Ditangguhkan</i></b>
          <small>Export laporan CC</small>
        </span>
      </button>

      <button type="button" class="op-act act-lkh op-act-suspended" @click="handleSuspendedExport('LKH Produksi (Excel)')">
        <span class="op-act-icon">XL</span>
        <span class="op-act-text">
          <b>LKH Produksi <i class="op-soon">Ditangguhkan</i></b>
          <small>Export Excel LKH</small>
        </span>
      </button>

      <button type="button" class="op-act act-soon op-act-suspended" @click="handleSuspendedExport('STB Distribusi')">
        <span class="op-act-icon">ST</span>
        <span class="op-act-text">
          <b>STB <i class="op-soon">Ditangguhkan</i></b>
          <small>Distribusi STB</small>
        </span>
      </button>

      <button type="button" class="op-act act-soon op-act-suspended" @click="handleSuspendedExport('Status Kiriman')">
        <span class="op-act-icon">KR</span>
        <span class="op-act-text">
          <b>Kiriman <i class="op-soon">Ditangguhkan</i></b>
          <small>Status pengiriman</small>
        </span>
      </button>
    </section>

    <!-- 3. OEE Bulan Ini & 3 Pilar Utama -->
    <section class="op-hero-oee">
      <div class="op-oee">
        <div class="op-oee-top">
          <span class="op-eyebrow">OEE {{ monthLabel }}</span>
          <span class="op-pill" :class="'tone-' + oeeTone">{{ oeeStatus }}</span>
        </div>
        <div class="op-oee-value" :class="'txt-' + oeeTone">
          {{ oeeVal.toFixed(1) }}<small>%</small>
        </div>
        <div class="op-track op-track-lg">
          <div class="op-fill" :class="'bg-' + oeeTone" :style="{ width: Math.min(100, oeeVal) + '%' }"></div>
          <div class="op-target-mark" :style="{ left: oeeTarget + '%' }">
            <span>{{ oeeTarget }}%</span>
          </div>
        </div>
        <div class="op-oee-foot">
          <span v-if="oeeGap >= 0">
            Di atas target <b class="txt-ok">+{{ oeeGap.toFixed(1) }} poin</b>
          </span>
          <span v-else>
            Di bawah target <b class="txt-bad">{{ oeeGap.toFixed(1) }} poin</b>
          </span>
          <span class="op-muted">Target KPI: {{ oeeTarget }}%</span>
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

    <!-- Ringkasan Volume Bulan Ini -->
    <section class="op-stats">
      <div class="op-stat">
        <span class="op-eyebrow">Total Lembar Proses</span>
        <b>{{ formatNumber(kpi.total_proses) }}</b><small>lembar</small>
      </div>
      <div class="op-stat">
        <span class="op-eyebrow">Produk Baik (Good)</span>
        <b class="txt-ok">{{ formatNumber(kpi.good) }}</b><small>lembar</small>
      </div>
      <div class="op-stat">
        <span class="op-eyebrow">Reject Total</span>
        <b class="txt-bad">{{ (Number(kpi.reject_percent) || 0).toFixed(2) }}%</b>
        <small>{{ formatNumber(kpi.reject_pcs) }} pcs</small>
      </div>
    </section>

    <!-- 4. Grafik Tren OEE (Chart.js) -->
    <section class="op-card mb-15">
      <div class="op-card-head">
        <div>
          <h3>Tren OEE Harian — {{ monthLabel }}</h3>
          <p>Capaian harian efisiensi operasional dibanding target {{ oeeTarget }}%</p>
        </div>
        <div class="op-toggles">
          <button 
            type="button" 
            :class="{ on: trendShow.availability }" 
            @click="toggleTrend('availability')"
          >
            Availability
          </button>
          <button 
            type="button" 
            :class="{ on: trendShow.performance }" 
            @click="toggleTrend('performance')"
          >
            Performance
          </button>
        </div>
      </div>

      <div class="op-canvas-wrap">
        <canvas ref="chartCanvasRef"></canvas>
      </div>

      <div class="op-card-foot">
        <div class="op-legend">
          <span><i class="dot dot-oee" :style="{ background: brandColor }"></i> OEE aktual</span>
          <span><i class="dot dot-target"></i> Target {{ oeeTarget }}%</span>
          <span v-if="trendShow.availability"><i class="dot dot-avail"></i> Availability</span>
          <span v-if="trendShow.performance"><i class="dot dot-perf"></i> Performance</span>
        </div>
        <span class="op-muted">{{ timeline.length }} hari kerja tercatat bulan ini</span>
      </div>
    </section>

    <!-- Modal Export Cost Control (CC) -->
    <div v-if="showModalCC" class="modal-overlay" :class="{ 'bottom-sheet-active': isMobileScreen }" @click.self="showModalCC = false">
      <div class="modal-content op-modal">
        <div class="op-modal-head">
          <h3>Export Cost Control (CC) — {{ divisionName }}</h3>
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
                <option value="1">Shift 1 (07:00 - 15:00)</option>
                <option value="2">Shift 2 (15:00 - 23:00)</option>
                <option value="3">Shift 3 (23:00 - 07:00)</option>
              </select>
            </div>
          </div>

          <div class="op-modal-actions">
            <button type="button" class="op-btn op-btn-ghost" @click="showModalCC = false">Batal</button>
            <div class="op-row-gap">
              <button type="button" class="op-btn op-btn-ghost" @click="submitExportCC('html')" title="Tampilan cetak / simpan ke PDF">Cetak / PDF</button>
              <button type="submit" class="op-btn op-btn-primary">Download Excel (.xlsx)</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Export LKH Produksi -->
    <div v-if="showModalLkh" class="modal-overlay" :class="{ 'bottom-sheet-active': isMobileScreen }" @click.self="showModalLkh = false">
      <div class="modal-content op-modal">
        <div class="op-modal-head">
          <h3>Export LKH Produksi — {{ divisionName }}</h3>
          <button type="button" class="op-btn op-btn-ghost op-btn-sm" @click="showModalLkh = false">✕</button>
        </div>

        <form @submit.prevent="submitExportLkh">
          <div class="op-form-grid">
            <div class="op-field">
              <label>Tanggal *</label>
              <input type="date" v-model="formLkh.tanggal" class="op-input" required />
            </div>
            <div class="op-field">
              <label>Shift *</label>
              <select v-model="formLkh.shift" class="op-input" required>
                <option value="1">Shift 1 (07:00 - 15:00)</option>
                <option value="2">Shift 2 (15:00 - 23:00)</option>
                <option value="3">Shift 3 (23:00 - 07:00)</option>
              </select>
            </div>
          </div>

          <div class="op-modal-actions">
            <button type="button" class="op-btn op-btn-ghost" @click="showModalLkh = false">Batal</button>
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
import { authState } from '../utils/api'
import { showAlert, showSuccess } from '../view/modal/modalService'

const props = defineProps({
  divisionKey: { type: String, required: true },
  divisionName: { type: String, required: true },
  brandColor: { type: String, default: '#4f46e5' },
  brandColor2: { type: String, default: '#7c3aed' },
  oeeTarget: { type: Number, default: 85 },
  kpi: {
    type: Object,
    default: () => ({
      oee: 86.5,
      availability: 92.0,
      performance: 94.5,
      quality: 99.2,
      total_proses: 165000,
      good: 161800,
      reject_percent: 1.94,
      reject_pcs: 3200
    })
  },
  timeline: {
    type: Array,
    default: () => []
  }
})

const chartCanvasRef = ref(null)
let chartInstance = null

const trendShow = reactive({ availability: false, performance: false })

const isMobileScreen = ref(false)
const checkMobileScreen = () => { isMobileScreen.value = window.innerWidth <= 768 }

const displayName = computed(() => {
  return authState.value?.username || props.divisionName
})

const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour >= 4 && hour < 11) return 'Selamat Pagi'
  if (hour >= 11 && hour < 15) return 'Selamat Siang'
  if (hour >= 15 && hour < 18) return 'Selamat Sore'
  return 'Selamat Malam'
})

const currentShiftText = computed(() => {
  const hour = new Date().getHours()
  if (hour >= 7 && hour < 15) return 'Shift 1 (07:00 - 15:00)'
  if (hour >= 15 && hour < 23) return 'Shift 2 (15:00 - 23:00)'
  return 'Shift 3 (23:00 - 07:00)'
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

const oeeVal = computed(() => Number(props.kpi?.oee) || 0)
const oeeTone = computed(() => (oeeVal.value >= props.oeeTarget ? 'ok' : oeeVal.value >= (props.oeeTarget - 12) ? 'warn' : 'bad'))
const oeeStatus = computed(() => (oeeVal.value >= props.oeeTarget ? 'Optimal' : oeeVal.value >= (props.oeeTarget - 12) ? 'Waspada' : 'Kritis'))
const oeeGap = computed(() => oeeVal.value - props.oeeTarget)

const rateTone = (val, target) => {
  const n = Number(val) || 0
  if (n >= target) return 'ok'
  if (n >= target - 10) return 'warn'
  return 'bad'
}

const pillars = computed(() => {
  const k = props.kpi || {}
  const a = Number(k.availability) || 0
  const p = Number(k.performance) || 0
  const q = Number(k.quality) || 0
  return [
    { key: 'a', label: 'Availability', value: a, target: 90, tone: rateTone(a, 90) },
    { key: 'p', label: 'Performance', value: p, target: 95, tone: rateTone(p, 95) },
    { key: 'q', label: 'Quality Rate', value: q, target: 99, tone: rateTone(q, 99) }
  ]
})

const formatNumber = (val) => {
  if (val === undefined || val === null || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}

const toggleTrend = (key) => {
  trendShow[key] = !trendShow[key]
  renderChart()
}

// Modal State
const showModalCC = ref(false)
const showModalLkh = ref(false)

const getLocalDate = () => {
  const d = new Date()
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

const formCC = reactive({
  tanggal: getLocalDate(),
  shift: '1'
})

const formLkh = reactive({
  tanggal: getLocalDate(),
  shift: '1'
})

const openModalCC = () => {
  formCC.tanggal = getLocalDate()
  showModalCC.value = true
}

const openModalLkh = () => {
  formLkh.tanggal = getLocalDate()
  showModalLkh.value = true
}

const submitExportCC = (format = 'xlsx') => {
  showModalCC.value = false
  showSuccess(
    `Laporan Cost Control (${format.toUpperCase()}) ${props.divisionName}\nTanggal: ${formCC.tanggal}\nShift: ${formCC.shift}\n\nLaporan berhasil dipersiapkan.`,
    { title: 'Export Berhasil' }
  )
}

const submitExportLkh = () => {
  showModalLkh.value = false
  showSuccess(
    `Laporan LKH Produksi (.xlsx) ${props.divisionName}\nTanggal: ${formLkh.tanggal}\nShift: ${formLkh.shift}\n\nLaporan berhasil diexport.`,
    { title: 'Export LKH Berhasil' }
  )
}

const handleSuspendedExport = (featureName) => {
  showAlert(
    `Tombol export untuk "${featureName}" saat ini ditangguhkan sementara karena fiturnya belum dibuat / masih dalam tahap pengembangan.`,
    { title: 'Fitur Belum Tersedia' }
  )
}

const openBlankMenu = (menuName) => {
  showAlert(
    `Menu "${menuName}" untuk ${props.divisionName} sedang dalam integrasi sistem.`,
    { title: 'Pemberitahuan Sistem' }
  )
}

const renderChart = () => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
  const el = chartCanvasRef.value
  if (!el) return

  const tl = props.timeline || []
  if (!tl.length) return

  const labels = tl.map(t => (t.tanggal || t.tgl || '').substring(5) || t.label)

  const datasets = [
    {
      label: 'OEE Aktual',
      data: tl.map(t => t.oee),
      borderColor: props.brandColor,
      backgroundColor: 'rgba(79, 70, 229, 0.08)',
      fill: true,
      tension: 0.3,
      borderWidth: 2.5,
      pointRadius: tl.length > 20 ? 1 : 3.5,
      pointBackgroundColor: props.brandColor,
      pointBorderColor: '#fff',
      pointBorderWidth: 2
    },
    {
      label: `Target OEE ${props.oeeTarget}%`,
      data: tl.map(() => props.oeeTarget),
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
        y: {
          min: 60,
          max: 100,
          ticks: {
            stepSize: 10,
            callback: (v) => v + '%'
          },
          grid: { color: '#f1f5f9' }
        },
        x: {
          grid: { display: false }
        }
      }
    }
  })
}

onMounted(() => {
  checkMobileScreen()
  window.addEventListener('resize', checkMobileScreen)
  nextTick(() => {
    renderChart()
  })
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobileScreen)
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})
</script>

<style scoped>
.op-wrap {
  --c-ok: #10b981;
  --c-warn: #d97706;
  --c-bad: #e11d48;
  --c-ink: #15172b;
  --c-text: #3a3f5c;
  --c-muted: #666c8a;
  --c-line: #e4e5f1;
  --c-soft: #f4f5fd;
  max-width: 1080px;
  margin: 0.5rem auto 2rem;
  color: var(--c-text);
}

.txt-ok { color: var(--c-ok); }
.txt-warn { color: var(--c-warn); }
.txt-bad { color: var(--c-bad); }
.bg-ok { background: var(--c-ok); }
.bg-warn { background: var(--c-warn); }
.bg-bad { background: var(--c-bad); }

.op-eyebrow { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--c-muted); }
.op-muted { font-size: 0.78rem; color: var(--c-muted); }
.op-row-gap { display: flex; gap: 0.5rem; }

/* ---------- Hero salam ---------- */
.op-hero {
  text-align: center;
  padding: 1.35rem 1rem;
  margin-bottom: 0.9rem;
  background: linear-gradient(165deg, #ffffff 0%, #f9faff 60%, #f2f5fd 100%);
  border: 1px solid var(--c-line);
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(21, 23, 55, 0.04);
  position: relative;
  overflow: hidden;
}
.op-hero::before {
  content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
  background: linear-gradient(90deg, var(--c-brand), var(--c-brand-2));
}
.op-date-badge {
  display: inline-block;
  background: #fff; border: 1px solid #c7d2fe; color: var(--c-brand);
  font-size: 0.75rem; font-weight: 700; padding: 3px 14px; border-radius: 99px; margin-bottom: 0.55rem;
}
.op-greeting { margin: 0; font-size: 1.85rem; font-weight: 800; color: var(--c-ink); letter-spacing: -0.02em; }
.op-user-name {
  background: linear-gradient(135deg, var(--c-brand), var(--c-brand-2));
  -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
}
.op-sub-greeting { margin: 4px 0 0; font-size: 0.86rem; color: var(--c-muted); font-weight: 500; }

/* ---------- Tombol aksi ---------- */
.op-actions-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 0.9rem; }
.op-act {
  display: flex; align-items: center; gap: 0.75rem; width: 100%;
  padding: 0.75rem 0.95rem; text-align: left; font-family: inherit;
  background: #fff; border: 1px solid var(--c-line); border-radius: 12px;
  box-shadow: 0 2px 8px rgba(21, 23, 55, 0.04);
  cursor: pointer; transition: all 0.15s ease;
}
.op-act:hover { background: var(--c-soft); border-color: var(--c-brand); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(21, 23, 55, 0.08); }
.op-act-icon {
  flex: none; width: 38px; height: 38px; border-radius: 9px;
  display: inline-flex; align-items: center; justify-content: center;
  font-size: 0.76rem; font-weight: 800; letter-spacing: 0.02em;
}
.act-cc .op-act-icon { background: #eef2ff; color: var(--c-brand); }
.act-lkh .op-act-icon { background: #d1fae5; color: #065f46; }
.act-soon .op-act-icon { background: #f1f5f9; color: #64748b; }
.act-soon { border-style: dashed; }
.op-act-text { display: flex; flex-direction: column; min-width: 0; }
.op-act-text b { font-size: 0.88rem; font-weight: 700; color: var(--c-ink); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.op-act-text small { font-size: 0.72rem; color: var(--c-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.op-soon { font-style: normal; font-size: 0.62rem; font-weight: 700; padding: 1px 6px; border-radius: 99px; background: #e2e8f0; color: #64748b; }

/* ---------- OEE bulan ini ---------- */
.op-hero-oee { display: grid; grid-template-columns: 1.15fr 2fr; gap: 0.9rem; margin-bottom: 0.9rem; }
.op-oee, .op-pillars, .op-stats, .op-card {
  background: #fff; border: 1px solid var(--c-line); border-radius: 14px;
  box-shadow: 0 2px 10px rgba(21, 23, 55, 0.04);
}
.op-oee {
  padding: 1.25rem 1.4rem; display: flex; flex-direction: column; gap: 0.5rem;
  background: linear-gradient(165deg, #ffffff 0%, #f9faff 60%, #f2f5fd 100%);
  position: relative; overflow: hidden;
}
.op-oee::before {
  content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
  background: linear-gradient(90deg, var(--c-brand), var(--c-brand-2));
}
.op-oee-top { display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; }
.op-oee-value { font-size: 3.4rem; font-weight: 800; line-height: 1; letter-spacing: -0.03em; }
.op-oee-value small { font-size: 1.5rem; font-weight: 700; margin-left: 2px; }
.op-oee-foot { display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--c-text); margin-top: 0.35rem; }

.op-pill { font-size: 0.74rem; font-weight: 700; padding: 3px 10px; border-radius: 99px; }
.op-pill.tone-ok { background: #d1fae5; color: #065f46; }
.op-pill.tone-warn { background: #fef3c7; color: #92400e; }
.op-pill.tone-bad { background: #fee2e2; color: #991b1b; }

.op-track { position: relative; height: 8px; background: #eef2f7; border-radius: 99px; }
.op-track-lg { height: 12px; margin-top: 1rem; }
.op-fill { height: 100%; border-radius: 99px; transition: width 0.4s ease; }
.op-target-mark { position: absolute; top: -3px; bottom: -3px; width: 2px; background: var(--c-ink); border-radius: 2px; }
.op-target-mark span { position: absolute; top: -17px; left: 50%; transform: translateX(-50%); font-size: 0.65rem; font-weight: 700; color: var(--c-ink); }

.op-pillars { display: grid; grid-template-columns: repeat(3, 1fr); padding: 0.5rem 0; }
.op-pillar { padding: 0.9rem 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; justify-content: center; }
.op-pillar + .op-pillar { border-left: 1px solid var(--c-line); }
.op-pillar-head { display: flex; justify-content: space-between; align-items: baseline; }
.op-pillar-val { font-size: 1.55rem; font-weight: 800; letter-spacing: -0.02em; }

/* ---------- Ringkasan volume ---------- */
.op-stats { display: grid; grid-template-columns: repeat(3, 1fr); margin-bottom: 0.9rem; }
.op-stat { padding: 0.85rem 1.25rem; display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 6px; }
.op-stat + .op-stat { border-left: 1px solid var(--c-line); }
.op-stat .op-eyebrow { width: 100%; margin-bottom: 2px; }
.op-stat b { font-size: 1.35rem; font-weight: 800; color: var(--c-ink); letter-spacing: -0.01em; }
.op-stat small { font-size: 0.78rem; color: var(--c-muted); }

/* ---------- Grafik ---------- */
.op-card { padding: 1.15rem 1.25rem; }
.op-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.85rem; flex-wrap: wrap; }
.op-card-head h3 { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--c-ink); }
.op-card-head p { margin: 2px 0 0; font-size: 0.76rem; color: var(--c-muted); }
.op-toggles { display: flex; gap: 6px; }
.op-toggles button {
  border: 1px solid var(--c-line); background: #fff; color: var(--c-muted);
  font-size: 0.72rem; font-weight: 600; padding: 4px 10px; border-radius: 99px; cursor: pointer;
  transition: all 0.15s ease;
}
.op-toggles button.on { background: #eef2ff; border-color: #c7d2fe; color: var(--c-brand); }

.op-canvas-wrap { position: relative; height: 280px; width: 100%; }
.op-card-foot {
  display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;
  margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--c-line);
}
.op-legend { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; font-size: 0.76rem; color: var(--c-muted); }
.op-legend span { display: inline-flex; align-items: center; gap: 5px; }
.dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; }
.dot-oee { background: var(--c-brand); }
.dot-target { background: #e11d48; }
.dot-avail { background: #10b981; }
.dot-perf { background: #d97706; }

/* ---------- Modals ---------- */
.op-modal { max-width: 480px; width: 95%; }
.op-modal-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 0.9rem; }
.op-modal-head h3 { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--c-ink); }
.op-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.op-modal-actions { display: flex; justify-content: space-between; gap: 0.5rem; flex-wrap: wrap; margin-top: 1.1rem; padding-top: 0.75rem; border-top: 1px solid var(--c-line); }

.op-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  height: 36px; padding: 0 0.95rem; font-size: 0.82rem; font-weight: 600;
  border-radius: 8px; border: 1px solid transparent; cursor: pointer; white-space: nowrap;
  transition: all 0.15s;
}
.op-btn-sm { height: 30px; padding: 0 0.6rem; }
.op-btn-primary { background: linear-gradient(135deg, var(--c-brand), var(--c-brand-2)); color: #fff; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); }
.op-btn-primary:hover { opacity: 0.92; transform: translateY(-1px); }
.op-btn-ghost { background: #fff; color: var(--c-text); border-color: var(--c-line); }
.op-btn-ghost:hover { background: var(--c-soft); }

.op-input {
  width: 100%; height: 36px; padding: 0 0.65rem; font-size: 0.82rem;
  border: 1px solid var(--c-line); border-radius: 8px; background: #fff; color: var(--c-ink); min-width: 0;
}
.op-input:focus { outline: 2px solid rgba(79, 70, 229, 0.2); border-color: var(--c-brand); }
.op-field { display: flex; flex-direction: column; gap: 3px; }
.op-field label { font-size: 0.72rem; font-weight: 700; color: var(--c-muted); }

/* ---------- Responsive ---------- */
@media (max-width: 900px) {
  .op-actions-row { grid-template-columns: repeat(2, 1fr); }
  .op-hero-oee { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .op-pillars { grid-template-columns: 1fr; }
  .op-pillar + .op-pillar { border-left: none; border-top: 1px solid var(--c-line); }
  .op-oee-value { font-size: 2.7rem; }
  .op-form-grid { grid-template-columns: 1fr; }
  .op-canvas-wrap { height: 230px; }

  .bottom-sheet-active { align-items: flex-end !important; padding: 0 !important; }
  .op-modal {
    max-width: 100% !important; width: 100% !important; max-height: 85vh !important;
    border-radius: 14px 14px 0 0 !important; animation: opSlideUp 0.25s ease-out;
  }
}

@media (max-width: 600px) {
  .op-greeting { font-size: 1.45rem; }
  .op-stat { padding: 0.65rem 0.75rem; }
  .op-stat b { font-size: 1.15rem; }
}

@keyframes opSlideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
</style>
