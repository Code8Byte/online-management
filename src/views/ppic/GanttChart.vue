<template>
  <div class="gantt-shell">
    <!-- Compact Top Bar with Show/Hide Filter Toggle -->
    <div class="gantt-top-bar">
      <div class="d-flex align-center gap-08 flex-wrap">
        <div class="seg-btn-group">
          <button
            type="button"
            class="seg-btn"
            :class="{ active: viewMode === 'machine' }"
            @click="viewMode = 'machine'"
          >
            🖨️ Timeline per Mesin
          </button>
          <button
            type="button"
            class="seg-btn"
            :class="{ active: viewMode === 'pp' }"
            @click="viewMode = 'pp'"
          >
            📦 Timeline per Job Order (PP)
          </button>
        </div>

        <div class="active-filter-summary">
          <span>Rentang: <b>{{ selectedDayRange === 'today' ? 'Hari Ini' : selectedDayRange === '3days' ? '3 Hari' : '5 Hari' }}</b></span>
          <span>&bull;</span>
          <span>Jam: <b>{{ selectedHourSpan === 'shifts' ? '07:00 - 23:00' : '24 Jam' }}</b></span>
          <span v-if="filterStage">&bull; Tahap: <b class="badge-stage-summary">{{ filterStage.toUpperCase() }}</b></span>
          <span v-if="filterExtendedOnly" class="badge-ext-summary">&bull; ⚠️ Auto-Extend Only</span>
        </div>
      </div>

      <button
        type="button"
        class="btn-toggle-filter"
        :class="{ active: showFilter }"
        @click="showFilter = !showFilter"
        title="Klik untuk membuka / menutup opsi filter"
      >
        <span>🔍</span>
        <span>{{ showFilter ? 'Tutup Filter' : 'Filter &amp; Opsi Tampilan' }}</span>
        <span class="caret-icon">{{ showFilter ? '▲' : '▼' }}</span>
      </button>
    </div>

    <!-- Collapsible Filter Drawer (Default: hidden, ditutup agar tidak memenuhi tampilan) -->
    <div v-show="showFilter" class="gantt-controls-card">
      <div class="controls-row">
        <!-- Filter Hari / Rentang Tanggal -->
        <div class="filter-item">
          <label>Rentang Tanggal:</label>
          <select v-model="selectedDayRange" class="gantt-select">
            <option value="today">Hari Ini Saja (01 Nov 2026)</option>
            <option value="3days">3 Hari (01 - 03 Nov 2026)</option>
            <option value="5days">5 Hari (01 - 05 Nov 2026)</option>
          </select>
        </div>

        <!-- Filter Shift Jam -->
        <div class="filter-item">
          <label>Jam Kerja:</label>
          <select v-model="selectedHourSpan" class="gantt-select">
            <option value="shifts">Shift 1 &amp; 2 (07:00 - 23:00 WIB)</option>
            <option value="24h">24 Jam Penuh (00:00 - 24:00 WIB)</option>
          </select>
        </div>

        <div class="spacer"></div>

        <!-- Filter by Stage -->
        <div class="filter-item">
          <label>Tahap:</label>
          <select v-model="filterStage" class="gantt-select">
            <option value="">Semua Tahap</option>
            <option value="cetak">Cetak (Plano)</option>
            <option value="completing">Completing (Plano)</option>
            <option value="cabut">Cabut (Pcs)</option>
            <option value="sortir">Sortir (Pcs)</option>
            <option value="lipat">Lipat (Pcs)</option>
          </select>
        </div>

        <!-- Filter Extended Only -->
        <label class="check-extend-label">
          <input type="checkbox" v-model="filterExtendedOnly" />
          <span>⚠️ Auto-Extend (Molor)</span>
          <span v-if="extendedTasksCount > 0" class="badge-count">{{ extendedTasksCount }}</span>
        </label>
      </div>

      <!-- Quick Legend Bar -->
      <div class="legend-row">
        <span class="legend-item"><span class="leg-box leg-ok"></span> Selesai (100%)</span>
        <span class="legend-item"><span class="leg-box leg-proc"></span> Sedang Proses</span>
        <span class="legend-item"><span class="leg-box leg-queue"></span> Antri Terjadwal</span>
        <span class="legend-item"><span class="leg-box leg-extend"></span> <b>⚠️ Auto-Extend (Perpanjangan Jam)</b></span>
        <span class="legend-item"><span class="leg-line-now"></span> Garis Waktu Sistem Sekarang ({{ CURRENT_SYSTEM_DATETIME }} WIB)</span>
        <span class="legend-item"><b>⏱️ Satuan: Kolom Jam &bull; Perhitungan Posisi Presisi Menit</b></span>
      </div>
    </div>

    <!-- Gantt Chart Timeline Viewport (Tampilan per Jam, Hitungan per Menit) -->
    <div class="gantt-viewport">
      <div class="gantt-container" :style="{ minWidth: totalTimelineWidth + 'px' }">
        
        <!-- Header Tier 1: Dates -->
        <div class="gantt-header-tier1">
          <div class="row-header-label">
            <b>{{ viewMode === 'machine' ? 'Daftar Mesin Produksi' : 'Job Order (PP)' }}</b>
          </div>
          <div class="days-header-wrap">
            <div
              v-for="day in activeDaysList"
              :key="day.dateStr"
              class="day-header-cell"
              :style="{ width: (day.hours.length * hourColWidth) + 'px' }"
              :class="{ 'is-today': day.dateStr === CURRENT_SYSTEM_DATE }"
            >
              <div class="day-title">
                <b>{{ day.dayName }}, {{ day.dateDisplay }}</b>
                <span v-if="day.dateStr === CURRENT_SYSTEM_DATE" class="badge-today">HARI INI</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Header Tier 2: Hours -->
        <div class="gantt-header-tier2">
          <div class="row-header-label-sub">
            <small class="text-muted">Kapasitas &amp; Kecepatan (JPH)</small>
          </div>
          <div class="hours-header-wrap">
            <template v-for="day in activeDaysList" :key="'h-' + day.dateStr">
              <div
                v-for="hr in day.hours"
                :key="day.dateStr + '-' + hr"
                class="hour-col-header"
                :style="{ width: hourColWidth + 'px' }"
              >
                <span>{{ String(hr).padStart(2, '0') }}:00</span>
              </div>
            </template>
          </div>
        </div>

        <!-- Gantt Body Rows -->
        <div class="gantt-body">
          <!-- MODE 1: GROUP BY MESIN -->
          <template v-if="viewMode === 'machine'">
            <div v-for="m in visibleMachines" :key="m.id" class="gantt-lane-row">
              <!-- Lane Label -->
              <div class="lane-label">
                <div class="lane-title">
                  <span class="stage-dot" :class="`dot-${m.stage}`"></span>
                  <b>{{ m.nama }}</b>
                </div>
                <div class="lane-meta">
                  <span class="meta-tag">Kapasitas: {{ formatNumber(m.speed_jph * m.jam_hari) }} {{ m.unit }}/hari</span>
                  <span class="meta-speed">{{ formatNumber(m.speed_jph) }} {{ m.unit }}/jam</span>
                </div>
              </div>

              <!-- Lane Timeline Tracks -->
              <div class="lane-timeline">
                <!-- Grid background column lines per hour -->
                <div class="grid-columns-bg">
                  <template v-for="day in activeDaysList" :key="'grid-' + day.dateStr">
                    <div
                      v-for="hr in day.hours"
                      :key="day.dateStr + '-' + hr"
                      class="grid-hour-col"
                      :style="{ width: hourColWidth + 'px' }"
                    ></div>
                  </template>
                </div>

                <!-- Now vertical marker line -->
                <div
                  v-if="currentNowOffsetPercent >= 0 && currentNowOffsetPercent <= 100"
                  class="now-marker-line"
                  :style="{ left: currentNowOffsetPercent + '%' }"
                >
                  <span class="now-badge">SEKARANG</span>
                </div>

                <!-- Task Bars on this machine with MINUTE-PRECISION -->
                <div class="bars-container">
                  <div
                    v-for="task in getTasksForMachine(m.id)"
                    :key="task.id"
                    class="gantt-bar-wrap"
                    :style="getTaskBarStyle(task)"
                    @click="selectedTask = task"
                  >
                    <!-- Normal Task Bar -->
                    <div class="gantt-bar" :class="[ `bar-${task.stage}`, `status-${task.status}` ]">
                      <div class="bar-progress-fill" :style="{ width: Math.min(task.progress, 100) + '%' }"></div>
                      <div class="bar-content">
                        <div class="bar-top-row">
                          <span class="bar-pp">{{ task.no_pp }}</span>
                          <span class="bar-prod">{{ task.produk_nama }}</span>
                        </div>
                        <div class="bar-bot-row">
                          <span class="bar-time">🕒 {{ formatDateTimeShort(task.start_datetime) }} - {{ formatDateTimeShort(task.planned_end_datetime) }}</span>
                          <span class="bar-qty">{{ formatNumber(task.plan_qty) }} {{ task.unit }} ({{ task.progress.toFixed(0) }}%)</span>
                          <span v-if="task.muatan_pallet" class="bar-muatan">🚚 {{ task.muatan_pallet }} Pallet</span>
                        </div>
                      </div>
                    </div>

                    <!-- Auto-Extend Striped Bar (Jika belum selesai & jam terlewati) -->
                    <div
                      v-if="task.is_extended"
                      class="gantt-bar-extended"
                      :style="getExtendBarStyle(task)"
                    >
                      <div class="extend-stripe-bg"></div>
                      <span class="extend-badge">⚠️ +{{ formatMinutesDisplay(task.extend_minutes) }} Extend</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- MODE 2: GROUP BY JOB ORDER (PP) -->
          <template v-else>
            <div v-for="pp in visiblePPs" :key="pp.no_pp" class="gantt-lane-row">
              <!-- Lane Label -->
              <div class="lane-label">
                <div class="lane-title">
                  <span class="pp-cube-icon">📦</span>
                  <b>{{ pp.no_pp }}</b>
                </div>
                <div class="lane-meta">
                  <span class="text-truncate">{{ pp.produk_nama }}</span>
                  <span class="meta-tag">Oplaag: {{ formatNumber(pp.oplaag_pcs) }} pcs (Up: {{ pp.up_per_plano }})</span>
                  <span v-if="pp.deadline_datetime" class="meta-deadline">🚚 Deadline: {{ pp.deadline_datetime }} WIB</span>
                </div>
              </div>

              <!-- Lane Timeline Tracks -->
              <div class="lane-timeline">
                <div class="grid-columns-bg">
                  <template v-for="day in activeDaysList" :key="'grid-pp-' + day.dateStr">
                    <div
                      v-for="hr in day.hours"
                      :key="day.dateStr + '-' + hr"
                      class="grid-hour-col"
                      :style="{ width: hourColWidth + 'px' }"
                    ></div>
                  </template>
                </div>

                <div
                  v-if="currentNowOffsetPercent >= 0 && currentNowOffsetPercent <= 100"
                  class="now-marker-line"
                  :style="{ left: currentNowOffsetPercent + '%' }"
                ></div>

                <div class="bars-container">
                  <div
                    v-for="task in getTasksForPP(pp.no_pp)"
                    :key="task.id"
                    class="gantt-bar-wrap"
                    :style="getTaskBarStyle(task)"
                    @click="selectedTask = task"
                  >
                    <div class="gantt-bar" :class="[ `bar-${task.stage}`, `status-${task.status}` ]">
                      <div class="bar-progress-fill" :style="{ width: Math.min(task.progress, 100) + '%' }"></div>
                      <div class="bar-content">
                        <span class="bar-pp">{{ task.stage.toUpperCase() }}: {{ task.mesin_nama }}</span>
                        <span class="bar-time">🕒 {{ formatDateTimeShort(task.start_datetime) }} - {{ formatDateTimeShort(task.planned_end_datetime) }}</span>
                        <span class="bar-qty">{{ formatNumber(task.plan_qty) }} {{ task.unit }} ({{ task.progress.toFixed(0) }}%)</span>
                      </div>
                    </div>

                    <div
                      v-if="task.is_extended"
                      class="gantt-bar-extended"
                      :style="getExtendBarStyle(task)"
                    >
                      <div class="extend-stripe-bg"></div>
                      <span class="extend-badge">⚠️ +{{ formatMinutesDisplay(task.extend_minutes) }} Extend</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>

      </div>
    </div>

    <!-- Task Detail Modal Drawer (Saat Bar Gantt Diklik) -->
    <div v-if="selectedTask" class="task-modal-overlay" @click.self="selectedTask = null">
      <div class="task-modal-card">
        <header class="t-modal-head">
          <div>
            <div class="d-flex align-center gap-05">
              <span class="t-pp-badge">{{ selectedTask.no_pp }}</span>
              <span class="badge" :class="getStageBadgeClass(selectedTask.stage)">{{ selectedTask.stage.toUpperCase() }}</span>
            </div>
            <h4 style="margin: 4px 0 0; font-size: 1.05rem;">{{ selectedTask.produk_nama }}</h4>
          </div>
          <button class="t-close-btn" @click="selectedTask = null">✕</button>
        </header>

        <div class="t-modal-body">
          <!-- Auto Extend Alert jika ada -->
          <div v-if="selectedTask.is_extended" class="t-extend-alert">
            <b>⚠️ STATUS JADWAL: AUTO-EXTEND (MOLOR PER JAM)</b>
            <p>{{ selectedTask.extend_reason }}</p>
          </div>

          <!-- Section A: Pengerjaan Mesin & Waktu -->
          <h5 class="t-subhead">1. Alokasi Mesin &amp; Perhitungan Waktu Masuk (Presisi Menit)</h5>
          <div class="t-grid">
            <div class="t-item">
              <span>Mesin Operasional:</span>
              <b>{{ selectedTask.mesin_nama }}</b>
            </div>
            <div class="t-item">
              <span>Target Produksi:</span>
              <b>{{ formatNumber(selectedTask.plan_qty) }} {{ selectedTask.unit }}</b>
            </div>
            <div class="t-item">
              <span>Waktu Masuk Mesin:</span>
              <b class="text-primary">{{ selectedTask.start_datetime || selectedTask.start_date }} WIB</b>
            </div>
            <div class="t-item">
              <span>Rencana Selesai:</span>
              <b>{{ selectedTask.planned_end_datetime || selectedTask.planned_end_date }} WIB</b>
            </div>
            <div class="t-item">
              <span>Durasi Pengerjaan:</span>
              <b class="text-success">{{ formatMinutesDisplay(selectedTask.duration_minutes) }}</b>
            </div>
            <div class="t-item">
              <span>Realisasi Output:</span>
              <b>{{ formatNumber(selectedTask.actual_output) }} {{ selectedTask.unit }} ({{ selectedTask.progress.toFixed(1) }}%)</b>
            </div>
          </div>

          <!-- Section B: Rincian Muatan Logistik & Batas Kirim -->
          <h5 class="t-subhead mt-1">2. Rincian Muatan Logistik &amp; Batas Pengiriman</h5>
          <div class="t-grid" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.8rem;">
            <div class="t-item">
              <span>Batas Pengiriman Maksimal:</span>
              <b class="text-danger" style="font-size: 0.92rem;">
                {{ selectedTask.deadline_datetime || '03 Nov 2026 16:00' }} WIB
              </b>
            </div>
            <div class="t-item">
              <span>Muatan yang Dikirim:</span>
              <b>{{ formatNumber(selectedTask.oplaag_pcs || selectedTask.plan_qty) }} Pcs</b>
              <small class="text-muted">({{ selectedTask.muatan_koli || 200 }} Koli / {{ selectedTask.muatan_pallet || 10 }} Pallet)</small>
            </div>
            <div class="t-item">
              <span>Armada &amp; Ritase:</span>
              <b>{{ selectedTask.ritase || 1 }} Ritase {{ selectedTask.armada || 'Colt Diesel Double (CDD)' }}</b>
            </div>
            <div class="t-item">
              <span>Status Ketepatan Waktu:</span>
              <span class="badge badge-success">🟢 Aman (Sesuai Rencana)</span>
            </div>
          </div>
        </div>

        <footer class="t-modal-foot">
          <router-link :to="'/ppic/input-pp'" class="btn btn-secondary btn-sm" @click="selectedTask = null">
            Buka Form Input PP
          </router-link>
          <button type="button" class="btn btn-primary btn-sm" @click="selectedTask = null">
            Tutup
          </button>
        </footer>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  masterMesinPPIC,
  evaluatedSchedules,
  CURRENT_SYSTEM_DATE,
  CURRENT_SYSTEM_DATETIME,
  diffMinutes,
  formatMinutesToHuman,
  fetchSchedulesFromApi
} from '../../data/schedulePPIC'
import { formatNumber } from '../../data/progressPP'

onMounted(() => {
  fetchSchedulesFromApi()
})

const viewMode = ref('machine') // 'machine' | 'pp'
const filterStage = ref('')
const filterExtendedOnly = ref(false)
const selectedTask = ref(null)
const showFilter = ref(false) // Default: tertutup agar tidak memenuhi tampilan layar

const selectedDayRange = ref('3days') // 'today' | '3days' | '5days'
const selectedHourSpan = ref('shifts') // 'shifts' (07-23) | '24h' (00-24)
const hourColWidth = 55 // px per hour

// Menghasilkan daftar hari dan jam aktif
const activeDaysList = computed(() => {
  const daysCount = selectedDayRange.value === 'today' ? 1 : selectedDayRange.value === '3days' ? 3 : 5
  const hoursList = selectedHourSpan.value === 'shifts'
    ? [7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23]
    : Array.from({ length: 24 }, (_, i) => i)

  const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']
  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
  const [y, m, d] = CURRENT_SYSTEM_DATE.split('-').map(Number)
  const dt = new Date(y, m - 1, d)

  const result = []
  for (let i = 0; i < daysCount; i++) {
    const cur = new Date(dt)
    cur.setDate(dt.getDate() + i)
    const pad = (n) => String(n).padStart(2, '0')
    const dateStr = `${cur.getFullYear()}-${pad(cur.getMonth() + 1)}-${pad(cur.getDate())}`
    result.push({
      dateStr,
      dateDisplay: `${cur.getDate()} ${monthNames[cur.getMonth()]}`,
      dayName: dayNames[cur.getDay()],
      hours: hoursList
    })
  }
  return result
})

// Total lebar timeline
const totalTimelineWidth = computed(() => {
  let totalHours = 0
  activeDaysList.value.forEach(d => {
    totalHours += d.hours.length
  })
  return 280 + (totalHours * hourColWidth)
})

// Waktu awal dan akhir visible timeline
const timelineStartDateTime = computed(() => {
  const firstDay = activeDaysList.value[0]
  if (!firstDay) return `${CURRENT_SYSTEM_DATE} 07:00`
  const firstHour = String(firstDay.hours[0]).padStart(2, '0')
  return `${firstDay.dateStr} ${firstHour}:00`
})

const timelineEndDateTime = computed(() => {
  const lastDay = activeDaysList.value[activeDaysList.value.length - 1]
  if (!lastDay) return `${CURRENT_SYSTEM_DATE} 23:59`
  const lastHour = lastDay.hours[lastDay.hours.length - 1]
  const pad = (n) => String(n).padStart(2, '0')
  return `${lastDay.dateStr} ${pad(lastHour)}:59`
})

const totalTimelineMinutes = computed(() => {
  return Math.max(60, diffMinutes(timelineStartDateTime.value, timelineEndDateTime.value))
})

// Posisi garis sekarang (%)
const currentNowOffsetPercent = computed(() => {
  const minsFromStart = diffMinutes(timelineStartDateTime.value, CURRENT_SYSTEM_DATETIME)
  return (minsFromStart / totalTimelineMinutes.value) * 100
})

// Filter data
const visibleMachines = computed(() => {
  if (!filterStage.value) return masterMesinPPIC
  return masterMesinPPIC.filter(m => m.stage === filterStage.value)
})

const visiblePPs = computed(() => {
  const ppMap = new Map()
  evaluatedSchedules.value.forEach(t => {
    if (!ppMap.has(t.no_pp)) {
      ppMap.set(t.no_pp, {
        no_pp: t.no_pp,
        produk_nama: t.produk_nama,
        oplaag_pcs: t.oplaag_pcs,
        up_per_plano: t.up_per_plano,
        deadline_datetime: t.deadline_datetime
      })
    }
  })
  return Array.from(ppMap.values())
})

const extendedTasksCount = computed(() => {
  return evaluatedSchedules.value.filter(t => t.is_extended).length
})

const getTasksForMachine = (mesinId) => {
  return evaluatedSchedules.value.filter(t => {
    if (t.mesin_id !== mesinId) return false
    if (filterStage.value && t.stage !== filterStage.value) return false
    if (filterExtendedOnly.value && !t.is_extended) return false
    return true
  })
}

const getTasksForPP = (noPP) => {
  return evaluatedSchedules.value.filter(t => {
    if (t.no_pp !== noPP) return false
    if (filterStage.value && t.stage !== filterStage.value) return false
    if (filterExtendedOnly.value && !t.is_extended) return false
    return true
  })
}

// Perhitungan posisi horizontal presisi menit
const getTaskBarStyle = (task) => {
  const startDt = task.start_datetime || `${task.start_date} 08:00`
  const endDt = task.planned_end_datetime || `${task.planned_end_date} 16:00`

  const minsFromStart = diffMinutes(timelineStartDateTime.value, startDt)
  const durationMins = Math.max(20, diffMinutes(startDt, endDt))

  const leftPercent = Math.max(0, (minsFromStart / totalTimelineMinutes.value) * 100)
  const widthPercent = Math.max(1, (durationMins / totalTimelineMinutes.value) * 100)

  return {
    left: `${leftPercent}%`,
    width: `${widthPercent}%`
  }
}

const getExtendBarStyle = (task) => {
  if (!task.is_extended || !task.extend_minutes) return { display: 'none' }
  const extendWidthPercent = (task.extend_minutes / totalTimelineMinutes.value) * 100
  return {
    left: '100%',
    width: `${extendWidthPercent}%`
  }
}

const formatDateTimeShort = (dtStr) => {
  if (!dtStr) return '-'
  const parts = dtStr.split(' ')
  const time = parts[1] || ''
  const dateParts = parts[0]?.split('-')
  const d = dateParts ? dateParts[2] : ''
  return `${d} Nov ${time}`
}

const formatMinutesDisplay = (minutes) => {
  if (!minutes) return '0 Menit'
  return formatMinutesToHuman(minutes)
}

const getStageBadgeClass = (stage) => {
  if (stage === 'cetak') return 'badge-primary'
  if (stage === 'completing') return 'badge-warning'
  if (stage === 'cabut') return 'badge-info'
  if (stage === 'sortir') return 'badge-success'
  if (stage === 'lipat') return 'badge-secondary'
  return 'badge-primary'
}
</script>

<style scoped>
.gantt-shell {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

/* Top Bar with Filter Toggle */
.gantt-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 8px 14px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}
.active-filter-summary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.76rem;
  color: #475569;
}
.badge-stage-summary {
  background: #eff6ff;
  color: #1e40af;
  padding: 1px 5px;
  border-radius: 4px;
  font-size: 0.7rem;
}
.badge-ext-summary {
  color: #dc2626;
  font-weight: 700;
  font-size: 0.74rem;
}
.btn-toggle-filter {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid #cbd5e1;
  background: #f8fafc;
  color: #334155;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
}
.btn-toggle-filter:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}
.btn-toggle-filter.active {
  background: #eff6ff;
  border-color: #3b82f6;
  color: #1d4ed8;
}
.caret-icon {
  font-size: 0.7rem;
}

/* Controls */
.gantt-controls-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.85rem 1.15rem;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.controls-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.seg-btn-group {
  display: inline-flex;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 3px;
}
.seg-btn {
  border: none;
  background: transparent;
  padding: 6px 14px;
  font-size: 0.8rem;
  font-weight: 700;
  color: #64748b;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s;
}
.seg-btn.active {
  background: #ffffff;
  color: #4f46e5;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
}

.spacer { flex: 1; }

.filter-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  color: #475569;
}
.gantt-select {
  height: 32px;
  padding: 0 0.6rem;
  font-size: 0.78rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #fff;
}

.check-extend-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #b91c1c;
  background: #fef2f2;
  border: 1px solid #fecaca;
  padding: 4px 10px;
  border-radius: 6px;
  cursor: pointer;
}
.badge-count {
  background: #dc2626;
  color: #fff;
  font-size: 0.68rem;
  padding: 1px 6px;
  border-radius: 99px;
}

.btn-input-new-pp {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #2563eb;
  color: #ffffff;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.15s;
}
.btn-input-new-pp:hover {
  background: #1d4ed8;
}

/* Legend */
.legend-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  font-size: 0.74rem;
  color: #64748b;
  border-top: 1px dashed #e2e8f0;
  padding-top: 0.5rem;
}
.legend-item { display: inline-flex; align-items: center; gap: 5px; }
.leg-box { width: 14px; height: 10px; border-radius: 2px; }
.leg-ok { background: #10b981; }
.leg-proc { background: #4f46e5; }
.leg-queue { background: #94a3b8; }
.leg-extend { background: repeating-linear-gradient(45deg, #ef4444, #ef4444 4px, #dc2626 4px, #dc2626 8px); }
.leg-line-now { width: 3px; height: 12px; background: #dc2626; border-radius: 1px; }

/* Viewport */
.gantt-viewport {
  overflow-x: auto;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
}

.gantt-container {
  display: flex;
  flex-direction: column;
}

/* Header Tier 1: Dates */
.gantt-header-tier1 {
  display: grid;
  grid-template-columns: 280px 1fr;
  background: #f8fafc;
  border-bottom: 1px solid #cbd5e1;
  position: sticky;
  top: 0;
  z-index: 10;
}
.row-header-label {
  padding: 10px 14px;
  font-size: 0.84rem;
  font-weight: 800;
  color: #0f172a;
  border-right: 2px solid #cbd5e1;
  display: flex;
  align-items: center;
}
.days-header-wrap {
  display: flex;
}
.day-header-cell {
  padding: 8px 10px;
  border-right: 2px solid #cbd5e1;
  text-align: center;
  background: #f1f5f9;
}
.day-header-cell.is-today {
  background: #eff6ff;
}
.day-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 0.8rem;
  color: #1e293b;
}
.badge-today {
  background: #2563eb;
  color: #fff;
  font-size: 0.65rem;
  font-weight: 800;
  padding: 1px 6px;
  border-radius: 4px;
}

/* Header Tier 2: Hours */
.gantt-header-tier2 {
  display: grid;
  grid-template-columns: 280px 1fr;
  background: #f8fafc;
  border-bottom: 2px solid #cbd5e1;
}
.row-header-label-sub {
  padding: 6px 14px;
  border-right: 2px solid #cbd5e1;
  display: flex;
  align-items: center;
}
.hours-header-wrap {
  display: flex;
}
.hour-col-header {
  padding: 4px 2px;
  text-align: center;
  font-size: 0.7rem;
  font-weight: 700;
  color: #64748b;
  border-right: 1px solid #e2e8f0;
  flex-shrink: 0;
}

/* Lane Rows */
.gantt-lane-row {
  display: grid;
  grid-template-columns: 280px 1fr;
  min-height: 64px;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s;
}
.gantt-lane-row:hover {
  background: #fcfdfe;
}
.lane-label {
  padding: 8px 12px;
  border-right: 2px solid #cbd5e1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 3px;
  background: #ffffff;
}
.lane-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.83rem;
  color: #1e293b;
}
.stage-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.dot-cetak { background: #3b82f6; }
.dot-completing { background: #f59e0b; }
.dot-cabut { background: #ea580c; }
.dot-sortir { background: #10b981; }
.dot-lipat { background: #0284c7; }

.lane-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.72rem;
  color: #64748b;
  flex-wrap: wrap;
}
.meta-tag {
  background: #f1f5f9;
  padding: 1px 6px;
  border-radius: 4px;
}
.meta-speed {
  font-weight: 700;
  color: #475569;
}
.meta-deadline {
  color: #dc2626;
  font-weight: 700;
}
.pp-cube-icon { font-size: 1rem; }

/* Timeline Track */
.lane-timeline {
  position: relative;
  display: flex;
  align-items: center;
}
.grid-columns-bg {
  position: absolute;
  inset: 0;
  display: flex;
  pointer-events: none;
}
.grid-hour-col {
  border-right: 1px solid #f1f5f9;
  height: 100%;
  flex-shrink: 0;
}

/* Now Marker Line */
.now-marker-line {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #dc2626;
  z-index: 5;
  pointer-events: none;
}
.now-badge {
  position: absolute;
  top: 2px;
  left: 4px;
  background: #dc2626;
  color: #fff;
  font-size: 0.6rem;
  font-weight: 800;
  padding: 1px 4px;
  border-radius: 3px;
}

/* Bars Container */
.bars-container {
  position: relative;
  width: 100%;
  height: 48px;
  z-index: 2;
}

.gantt-bar-wrap {
  position: absolute;
  top: 4px;
  height: 40px;
  display: flex;
  cursor: pointer;
  transition: transform 0.15s ease;
}
.gantt-bar-wrap:hover {
  transform: translateY(-2px);
  z-index: 10;
}

.gantt-bar {
  position: relative;
  flex: 1;
  height: 100%;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
  display: flex;
  align-items: center;
}
.bar-cetak { background: #2563eb; color: #ffffff; }
.bar-completing { background: #d97706; color: #ffffff; }
.bar-cabut { background: #c2410c; color: #ffffff; }
.bar-sortir { background: #059669; color: #ffffff; }
.bar-lipat { background: #0284c7; color: #ffffff; }

.bar-progress-fill {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.25);
  pointer-events: none;
}

.bar-content {
  position: relative;
  z-index: 2;
  padding: 0 8px;
  width: 100%;
  overflow: hidden;
  white-space: nowrap;
}
.bar-top-row {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.74rem;
  font-weight: 800;
}
.bar-bot-row {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.68rem;
  opacity: 0.95;
}
.bar-muatan {
  background: rgba(0, 0, 0, 0.2);
  padding: 0 4px;
  border-radius: 3px;
}

.gantt-bar-extended {
  position: absolute;
  top: 0;
  height: 100%;
  border-radius: 0 6px 6px 0;
  overflow: hidden;
  display: flex;
  align-items: center;
}
.extend-stripe-bg {
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(45deg, #ef4444, #ef4444 6px, #dc2626 6px, #dc2626 12px);
}
.extend-badge {
  position: relative;
  z-index: 2;
  font-size: 0.65rem;
  font-weight: 800;
  color: #fff;
  padding: 0 6px;
  white-space: nowrap;
}

/* Modal Drawer */
.task-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(2px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.task-modal-card {
  background: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 620px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
  overflow: hidden;
}
.t-modal-head {
  padding: 1rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.t-close-btn {
  border: none;
  background: transparent;
  font-size: 1.2rem;
  cursor: pointer;
  color: #64748b;
}
.t-pp-badge {
  background: #eff6ff;
  color: #1d4ed8;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
}
.t-modal-body {
  padding: 1.25rem;
  max-height: 70vh;
  overflow-y: auto;
}
.t-subhead {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
  margin: 0 0 0.5rem;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 3px;
}
.t-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}
.t-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 0.8rem;
}
.t-item span {
  font-size: 0.72rem;
  color: #64748b;
}
.t-extend-alert {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  padding: 0.75rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  font-size: 0.82rem;
}
.t-modal-foot {
  padding: 0.85rem 1.25rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
</style>
