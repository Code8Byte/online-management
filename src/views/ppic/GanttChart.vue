<template>
  <div class="gantt-shell">
    <!-- Top Controls Bar -->
    <div class="gantt-controls-card">
      <div class="controls-row">
        <!-- View Grouping Switcher -->
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

        <div class="spacer"></div>

        <!-- Filter by Stage -->
        <div class="filter-item">
          <label>Filter Tahap:</label>
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
          <span>⚠️ Tampilkan yang Auto-Extend (Molor)</span>
          <span v-if="extendedTasksCount > 0" class="badge-count">{{ extendedTasksCount }}</span>
        </label>
      </div>

      <!-- Quick Legend Bar -->
      <div class="legend-row">
        <span class="legend-item"><span class="leg-box leg-ok"></span> Selesai (100%)</span>
        <span class="legend-item"><span class="leg-box leg-proc"></span> Sedang Proses</span>
        <span class="legend-item"><span class="leg-box leg-queue"></span> Antri Terjadwal</span>
        <span class="legend-item"><span class="leg-box leg-extend"></span> <b>⚠️ Auto-Extend (Belum Selesai)</b></span>
        <span class="legend-item"><span class="leg-line-today"></span> Garis Hari Ini ({{ CURRENT_SYSTEM_DATE }})</span>
      </div>
    </div>

    <!-- Gantt Chart Timeline Viewport -->
    <div class="gantt-viewport">
      <div class="gantt-container" :style="{ minWidth: timelineWidth + 'px' }">
        
        <!-- Timeline Header: Dates -->
        <div class="gantt-header-row">
          <div class="row-header-label">
            <b>{{ viewMode === 'machine' ? 'Daftar Mesin Produksi' : 'Job Order (PP)' }}</b>
          </div>
          <div class="timeline-dates-wrap">
            <div
              v-for="d in dateRange"
              :key="d.dateStr"
              class="date-col-header"
              :class="{ 'is-today': d.dateStr === CURRENT_SYSTEM_DATE, 'is-weekend': d.isWeekend }"
            >
              <span class="date-num">{{ d.dayNum }}</span>
              <span class="date-sub">{{ d.monthShort }} &bull; {{ d.dayName }}</span>
              <span v-if="d.dateStr === CURRENT_SYSTEM_DATE" class="today-tag">HARI INI</span>
            </div>
          </div>
        </div>

        <!-- Gantt Rows (Berdasarkan Mesin atau PP) -->
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
                <!-- Grid background column lines -->
                <div class="grid-columns-bg">
                  <div
                    v-for="d in dateRange"
                    :key="d.dateStr"
                    class="grid-col"
                    :class="{ 'col-today': d.dateStr === CURRENT_SYSTEM_DATE, 'col-weekend': d.isWeekend }"
                  ></div>
                </div>

                <!-- Today vertical marker line -->
                <div
                  v-if="todayOffsetPercent >= 0"
                  class="today-marker-line"
                  :style="{ left: todayOffsetPercent + '%' }"
                ></div>

                <!-- Task Bars on this machine -->
                <div class="bars-container">
                  <div
                    v-for="task in getTasksForMachine(m.id)"
                    :key="task.id"
                    class="gantt-bar-wrap"
                    :style="getTaskBarStyle(task)"
                    @click="selectedTask = task"
                  >
                    <!-- Normal Bar Segment -->
                    <div class="gantt-bar" :class="[ `bar-${task.stage}`, `status-${task.status}` ]">
                      <div class="bar-progress-fill" :style="{ width: Math.min(task.progress, 100) + '%' }"></div>
                      <div class="bar-content">
                        <span class="bar-pp">{{ task.no_pp }}</span>
                        <span class="bar-prod">{{ task.produk_nama }}</span>
                        <span class="bar-qty">{{ formatNumber(task.plan_qty) }} {{ task.unit }}</span>
                        <span class="bar-pct">{{ task.progress.toFixed(0) }}%</span>
                      </div>
                    </div>

                    <!-- Auto-Extend Striped Bar (Jika belum selesai & tanggal terlewati) -->
                    <div
                      v-if="task.is_extended"
                      class="gantt-bar-extended"
                      :style="getExtendBarStyle(task)"
                      title="Auto-Extend: Belum selesai, diperpanjang otomatis"
                    >
                      <div class="extend-stripe-bg"></div>
                      <span class="extend-badge">⚠️ +{{ task.extend_days }} Hari</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- MODE 2: GROUP BY JOB ORDER (PP) -->
          <template v-else>
            <div v-for="pp in visibleJobOrders" :key="pp.no_pp" class="gantt-pp-group">
              <!-- PP Header Lane -->
              <div class="gantt-lane-row pp-header-row">
                <div class="lane-label pp-label">
                  <span class="pp-no-badge">{{ pp.no_pp }}</span>
                  <b>{{ pp.produk_nama }}</b>
                  <small>{{ pp.tasks.length }} Tahap Proses</small>
                </div>
                <div class="lane-timeline">
                  <div class="grid-columns-bg">
                    <div
                      v-for="d in dateRange"
                      :key="d.dateStr"
                      class="grid-col"
                      :class="{ 'col-today': d.dateStr === CURRENT_SYSTEM_DATE }"
                    ></div>
                  </div>
                </div>
              </div>

              <!-- Sub-Rows for each stage in this PP -->
              <div v-for="task in pp.tasks" :key="task.id" class="gantt-lane-row sub-stage-row">
                <div class="lane-label sub-label">
                  <span class="sub-num">{{ task.stage_num }}</span>
                  <span class="sub-tag" :class="`tag-${task.stage}`">{{ task.stage.toUpperCase() }}</span>
                  <span class="sub-machine">{{ task.mesin_nama }}</span>
                </div>

                <div class="lane-timeline">
                  <div class="grid-columns-bg">
                    <div
                      v-for="d in dateRange"
                      :key="d.dateStr"
                      class="grid-col"
                      :class="{ 'col-today': d.dateStr === CURRENT_SYSTEM_DATE }"
                    ></div>
                  </div>

                  <div
                    v-if="todayOffsetPercent >= 0"
                    class="today-marker-line"
                    :style="{ left: todayOffsetPercent + '%' }"
                  ></div>

                  <div class="bars-container">
                    <div
                      class="gantt-bar-wrap"
                      :style="getTaskBarStyle(task)"
                      @click="selectedTask = task"
                    >
                      <div class="gantt-bar" :class="[ `bar-${task.stage}`, `status-${task.status}` ]">
                        <div class="bar-progress-fill" :style="{ width: Math.min(task.progress, 100) + '%' }"></div>
                        <div class="bar-content">
                          <span class="bar-qty">{{ formatNumber(task.plan_qty) }} {{ task.unit }}</span>
                          <span class="bar-pct">{{ task.progress.toFixed(0) }}%</span>
                        </div>
                      </div>

                      <div
                        v-if="task.is_extended"
                        class="gantt-bar-extended"
                        :style="getExtendBarStyle(task)"
                      >
                        <div class="extend-stripe-bg"></div>
                        <span class="extend-badge">⚠️ +{{ task.extend_days }}H Extend</span>
                      </div>
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
            <span class="t-pp-badge">{{ selectedTask.no_pp }}</span>
            <h4>{{ selectedTask.produk_nama }}</h4>
          </div>
          <button class="t-close-btn" @click="selectedTask = null">✕</button>
        </header>

        <div class="t-modal-body">
          <div v-if="selectedTask.is_extended" class="t-extend-alert">
            <b>⚠️ STATUS JADWAL: AUTO-EXTEND (MOLOR)</b>
            <p>{{ selectedTask.extend_reason }}</p>
          </div>

          <div class="t-grid">
            <div class="t-item">
              <span>Tahap &amp; Mesin:</span>
              <b>{{ selectedTask.mesin_nama }}</b>
            </div>
            <div class="t-item">
              <span>Satuan:</span>
              <b style="text-transform: uppercase;">{{ selectedTask.unit }}</b>
            </div>
            <div class="t-item">
              <span>Target Plan:</span>
              <b>{{ formatNumber(selectedTask.plan_qty) }} {{ selectedTask.unit }}</b>
            </div>
            <div class="t-item">
              <span>Realisasi Output:</span>
              <b class="txt-ok">{{ formatNumber(selectedTask.actual_output) }} {{ selectedTask.unit }}</b>
            </div>
            <div class="t-item">
              <span>Tanggal Mulai:</span>
              <b>{{ selectedTask.start_date }}</b>
            </div>
            <div class="t-item">
              <span>Rencana Selesai:</span>
              <b>{{ selectedTask.planned_end_date }}</b>
            </div>
            <div v-if="selectedTask.is_extended" class="t-item txt-bad">
              <span>Estimasi Selesai Baru:</span>
              <b>{{ selectedTask.effective_end_date }} (+{{ selectedTask.extend_days }} hari)</b>
            </div>
            <div class="t-item">
              <span>Progress Real-Time:</span>
              <b>{{ selectedTask.progress.toFixed(1) }}%</b>
            </div>
          </div>
        </div>

        <footer class="t-modal-foot">
          <button type="button" class="btn-dark-sm" @click="selectedTask = null">Tutup</button>
        </footer>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  masterMesinPPIC,
  evaluatedSchedules,
  CURRENT_SYSTEM_DATE
} from '../../data/schedulePPIC'
import { formatNumber } from '../../data/progressPP'

const viewMode = ref('machine') // 'machine' | 'pp'
const filterStage = ref('')
const filterExtendedOnly = ref(false)
const selectedTask = ref(null)

// Rentang tanggal timeline (16 hari: 25 Okt s/d 09 Nov 2026)
const START_DATE_TIMELINE = '2026-10-25'
const TOTAL_DAYS_TIMELINE = 16

const dateRange = computed(() => {
  const dates = []
  const [y, m, d] = START_DATE_TIMELINE.split('-').map(Number)
  const dt = new Date(y, m - 1, d)

  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
  const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']

  for (let i = 0; i < TOTAL_DAYS_TIMELINE; i++) {
    const cur = new Date(dt)
    cur.setDate(dt.getDate() + i)

    const pad = (n) => String(n).padStart(2, '0')
    const dateStr = `${cur.getFullYear()}-${pad(cur.getMonth() + 1)}-${pad(cur.getDate())}`
    const dayOfWeek = cur.getDay()

    dates.push({
      dateStr,
      dayNum: pad(cur.getDate()),
      monthShort: monthNames[cur.getMonth()],
      dayName: dayNames[dayOfWeek],
      isWeekend: dayOfWeek === 0 || dayOfWeek === 6
    })
  }
  return dates
})

const timelineWidth = computed(() => {
  // Label width (280px) + 16 days * 85px = 1640px
  return 280 + (TOTAL_DAYS_TIMELINE * 85)
})

// Offset horizontal garis hari ini (%)
const todayOffsetPercent = computed(() => {
  const idx = dateRange.value.findIndex(d => d.dateStr === CURRENT_SYSTEM_DATE)
  if (idx === -1) return -1
  return ((idx + 0.5) / TOTAL_DAYS_TIMELINE) * 100
})

// Schedulings yang telah dievaluasi Auto-Extend
const allTasks = computed(() => {
  return evaluatedSchedules.value.filter(t => {
    if (filterStage.value && t.stage !== filterStage.value) return false
    if (filterExtendedOnly.value && !t.is_extended) return false
    return true
  })
})

const extendedTasksCount = computed(() => {
  return evaluatedSchedules.value.filter(t => t.is_extended).length
})

// Mesin yang tampil
const visibleMachines = computed(() => {
  return masterMesinPPIC.filter(m => {
    if (filterStage.value && m.stage !== filterStage.value) return false
    return true
  })
})

const getTasksForMachine = (machineId) => {
  return allTasks.value.filter(t => t.mesin_id === machineId)
}

// Group by PP
const visibleJobOrders = computed(() => {
  const map = {}
  allTasks.value.forEach(task => {
    if (!map[task.no_pp]) {
      map[task.no_pp] = {
        no_pp: task.no_pp,
        produk_nama: task.produk_nama,
        tasks: []
      }
    }
    map[task.no_pp].tasks.push(task)
  })
  return Object.values(map)
})

// Perhitungan posisi bar Gantt Chart
function getDateDiffDays(d1Str, d2Str) {
  const d1 = new Date(d1Str)
  const d2 = new Date(d2Str)
  const diffTime = d2.getTime() - d1.getTime()
  return diffTime / (1000 * 3600 * 24)
}

function getTaskBarStyle(task) {
  const startOffsetDays = Math.max(0, getDateDiffDays(START_DATE_TIMELINE, task.start_date))
  const plannedDurationDays = Math.max(1, getDateDiffDays(task.start_date, task.planned_end_date))

  const leftPercent = (startOffsetDays / TOTAL_DAYS_TIMELINE) * 100
  const widthPercent = (plannedDurationDays / TOTAL_DAYS_TIMELINE) * 100

  return {
    left: `${leftPercent}%`,
    width: `${widthPercent}%`
  }
}

function getExtendBarStyle(task) {
  if (!task.is_extended || !task.extend_days) return { display: 'none' }

  // Width extend dihitung dari extend_days
  const extendWidthPercent = (task.extend_days / TOTAL_DAYS_TIMELINE) * 100

  return {
    left: '100%',
    width: `${extendWidthPercent}%`
  }
}
</script>

<style scoped>
.gantt-shell {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Controls Card */
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
.leg-line-today { width: 3px; height: 12px; background: #dc2626; border-radius: 1px; }

/* Viewport & Container */
.gantt-viewport {
  overflow-x: auto;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
}

.gantt-container {
  display: flex;
  flex-direction: column;
}

/* Header Row */
.gantt-header-row {
  display: grid;
  grid-template-columns: 280px 1fr;
  background: #f8fafc;
  border-bottom: 2px solid #cbd5e1;
  position: sticky;
  top: 0;
  z-index: 10;
}
.row-header-label {
  padding: 0.85rem 1rem;
  font-size: 0.82rem;
  color: #0f172a;
  display: flex;
  align-items: center;
  border-right: 1px solid #cbd5e1;
}
.timeline-dates-wrap {
  display: grid;
  grid-template-columns: repeat(16, 1fr);
}
.date-col-header {
  padding: 0.5rem 0.2rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  border-right: 1px solid #e2e8f0;
  font-size: 0.72rem;
  color: #64748b;
  position: relative;
}
.date-col-header.is-weekend { background: #f1f5f9; }
.date-col-header.is-today { background: #fee2e2; border-color: #fca5a5; }
.date-num { font-size: 0.95rem; font-weight: 800; color: #0f172a; }
.date-col-header.is-today .date-num { color: #b91c1c; }
.today-tag {
  background: #dc2626; color: #fff; font-size: 0.58rem; font-weight: 800;
  padding: 1px 4px; border-radius: 2px; margin-top: 2px;
}

/* Lane Rows */
.gantt-body {
  display: flex;
  flex-direction: column;
}

.gantt-lane-row {
  display: grid;
  grid-template-columns: 280px 1fr;
  min-height: 52px;
  border-bottom: 1px solid #e2e8f0;
  position: relative;
}
.gantt-lane-row:hover {
  background: #fafbff;
}

.lane-label {
  padding: 0.6rem 0.85rem;
  border-right: 1px solid #cbd5e1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 2px;
  background: #ffffff;
}
.lane-title { display: flex; align-items: center; gap: 6px; font-size: 0.8rem; color: #0f172a; }
.stage-dot { width: 8px; height: 8px; border-radius: 50%; }
.dot-cetak { background: #4f46e5; }
.dot-completing { background: #d97706; }
.dot-cabut { background: #0284c7; }
.dot-lipat { background: #16a34a; }
.dot-sortir { background: #7c3aed; }

.lane-meta { display: flex; gap: 6px; font-size: 0.68rem; color: #64748b; flex-wrap: wrap; }
.meta-tag { background: #f1f5f9; padding: 1px 4px; border-radius: 3px; }

/* Timeline Track */
.lane-timeline {
  position: relative;
  width: 100%;
  height: 100%;
}

.grid-columns-bg {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  display: grid;
  grid-template-columns: repeat(16, 1fr);
  pointer-events: none;
}
.grid-col { border-right: 1px solid #f1f5f9; }
.grid-col.col-weekend { background: rgba(241, 245, 249, 0.4); }
.grid-col.col-today { background: rgba(254, 226, 226, 0.3); border-right-color: #fca5a5; }

/* Today Vertical Line Marker */
.today-marker-line {
  position: absolute;
  top: 0; bottom: 0;
  width: 2px;
  background: #ef4444;
  z-index: 5;
  pointer-events: none;
}

/* Bars Container */
.bars-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
}

.gantt-bar-wrap {
  position: absolute;
  height: 36px;
  cursor: pointer;
  z-index: 6;
  display: flex;
  transition: transform 0.15s;
}
.gantt-bar-wrap:hover {
  transform: translateY(-2px);
  z-index: 8;
}

/* Gantt Bar Normal */
.gantt-bar {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 6px;
  background: #334155;
  color: #ffffff;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
  display: flex;
  align-items: center;
}
.bar-progress-fill {
  position: absolute;
  top: 0; left: 0; bottom: 0;
  background: rgba(255, 255, 255, 0.25);
  transition: width 0.3s ease;
}

.bar-cetak { background: #4f46e5; }
.bar-completing { background: #d97706; }
.bar-cabut { background: #0284c7; }
.bar-lipat { background: #16a34a; }
.bar-sortir { background: #7c3aed; }

.status-selesai { background: #10b981; }
.status-antri { background: #64748b; }

.bar-content {
  position: relative;
  z-index: 2;
  padding: 0 8px;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
}
.bar-pp { background: rgba(0, 0, 0, 0.25); padding: 1px 5px; border-radius: 3px; font-weight: 800; }
.bar-prod { flex: 1; overflow: hidden; text-overflow: ellipsis; }
.bar-qty { opacity: 0.9; font-size: 0.68rem; }
.bar-pct { font-size: 0.72rem; font-weight: 800; background: rgba(255, 255, 255, 0.25); padding: 1px 4px; border-radius: 3px; }

/* Gantt Bar Extended Segment */
.gantt-bar-extended {
  position: absolute;
  top: 0; bottom: 0;
  border-radius: 0 6px 6px 0;
  border: 1.5px dashed #b91c1c;
  border-left: none;
  background: #fee2e2;
  overflow: hidden;
  display: flex;
  align-items: center;
  padding: 0 4px;
}
.extend-stripe-bg {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: repeating-linear-gradient(45deg, rgba(239, 68, 68, 0.3), rgba(239, 68, 68, 0.3) 5px, rgba(220, 38, 38, 0.5) 5px, rgba(220, 38, 38, 0.5) 10px);
}
.extend-badge {
  position: relative;
  z-index: 2;
  font-size: 0.65rem;
  font-weight: 800;
  color: #7f1d1d;
  background: #fff;
  padding: 1px 5px;
  border-radius: 3px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  white-space: nowrap;
}

/* Mode PP Grouping Styles */
.gantt-pp-group { border-bottom: 2px solid #cbd5e1; }
.pp-header-row { background: #f8fafc; font-weight: 800; }
.pp-label { flex-direction: row; align-items: center; gap: 8px; font-size: 0.85rem; }
.pp-no-badge { background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; }

.sub-stage-row { min-height: 40px; }
.sub-label { flex-direction: row; align-items: center; gap: 6px; font-size: 0.74rem; }
.sub-num { width: 16px; height: 16px; border-radius: 50%; background: #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 800; }
.sub-tag { font-size: 0.65rem; font-weight: 800; padding: 1px 5px; border-radius: 3px; }
.tag-cetak { background: #e0e7ff; color: #4338ca; }
.tag-completing { background: #fef3c7; color: #b45309; }
.tag-cabut { background: #e0f2fe; color: #0369a1; }
.tag-lipat { background: #dcfce7; color: #15803d; }
.tag-sortir { background: #ede9fe; color: #6d28d9; }
.sub-machine { color: #64748b; font-size: 0.72rem; }

/* Detail Modal Drawer */
.task-modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}
.task-modal-card {
  width: 100%;
  max-width: 480px;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: popIn 0.2s ease-out;
}
@keyframes popIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.t-modal-head {
  padding: 1rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.t-pp-badge { font-size: 0.75rem; font-weight: 800; color: #4f46e5; background: #eef2ff; padding: 2px 8px; border-radius: 4px; }
.t-modal-head h4 { margin: 4px 0 0; font-size: 1.05rem; color: #0f172a; }
.t-close-btn { background: none; border: none; font-size: 1.1rem; color: #94a3b8; cursor: pointer; }

.t-modal-body { padding: 1.25rem; display: flex; flex-direction: column; gap: 0.9rem; }
.t-extend-alert {
  background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 0.7rem 0.9rem;
}
.t-extend-alert b { font-size: 0.8rem; color: #991b1b; }
.t-extend-alert p { margin: 2px 0 0; font-size: 0.76rem; color: #7f1d1d; }

.t-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; font-size: 0.82rem; }
.t-item { display: flex; flex-direction: column; gap: 2px; }
.t-item span { font-size: 0.7rem; color: #64748b; }
.t-item b { color: #0f172a; }

.t-modal-foot {
  padding: 0.75rem 1.25rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
}
.btn-dark-sm {
  background: #1e293b; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-weight: 700; cursor: pointer;
}
</style>
