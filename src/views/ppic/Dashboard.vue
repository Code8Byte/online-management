<template>
  <div class="ppic-dashboard">
    <!-- Top Bar Title & Quick Stats -->
    <div class="dash-header">
      <div class="header-left">
        <h2 class="dash-title">
          <span class="icon-wrap">🏭</span> PPIC &amp; Scheduling Mesin
        </h2>
        <p class="dash-subtitle">
          Perencanaan kapasitas produksi, kalkulator plano &bull; oplaag, dan Gantt Chart jadwal otomatis per divisi
        </p>
      </div>

      <div class="header-right">
        <div class="date-badge">
          <span class="badge-dot"></span>
          <span>Sistem Hari Ini: <b>01 Nov 2026</b></span>
        </div>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="kpi-grid">
      <div class="kpi-card" :class="{ 'kpi-card-active': activeTab === 'gantt' }" @click="activeTab = 'gantt'">
        <div class="kpi-icon icon-blue">📋</div>
        <div class="kpi-body">
          <span class="kpi-label">Total Job Order PP</span>
          <span class="kpi-value">{{ totalUniquePP }}</span>
          <small class="kpi-hint">Pesanan Terjadwal</small>
        </div>
      </div>

      <div class="kpi-card" :class="{ 'kpi-card-active': activeTab === 'gantt' }" @click="activeTab = 'gantt'">
        <div class="kpi-icon icon-green">🖨️</div>
        <div class="kpi-body">
          <span class="kpi-label">Mesin Beroperasi</span>
          <span class="kpi-value">{{ activeMachinesCount }} <span class="kpi-sub">/ {{ masterMesinPPIC.length }}</span></span>
          <small class="kpi-hint">Kapasitas Sedang Running</small>
        </div>
      </div>

      <div class="kpi-card kpi-warning-card" :class="{ 'kpi-card-active': activeTab === 'gantt' }" @click="activeTab = 'gantt'">
        <div class="kpi-icon icon-amber">⚠️</div>
        <div class="kpi-body">
          <span class="kpi-label">Auto-Extend (Molor)</span>
          <span class="kpi-value text-amber">{{ totalExtendedCount }}</span>
          <small class="kpi-hint">Diperpanjang Otomatis</small>
        </div>
      </div>

      <div class="kpi-card" :class="{ 'kpi-card-active': activeTab === 'gantt' }" @click="activeTab = 'gantt'">
        <div class="kpi-icon icon-indigo">📄</div>
        <div class="kpi-body">
          <span class="kpi-label">Total Plano Terjadwal</span>
          <span class="kpi-value">{{ formatNumber(totalPlanoScheduled) }}</span>
          <small class="kpi-hint">Lembar Plano Cetak</small>
        </div>
      </div>
    </div>

    <!-- Navigation Header & Quick Actions -->
    <div class="tabs-container">
      <div class="nav-tabs">
        <div class="tab-btn active">
          <span>📊</span> Gantt Chart Kapasitas &amp; Jadwal Mesin (Per Jam &bull; Presisi Menit)
        </div>
      </div>

      <div class="tabs-actions">
        <router-link to="/ppic/input-pp" class="btn-new-job" style="text-decoration: none;">
          <span>✍️</span> + Input PP Baru
        </router-link>
      </div>
    </div>

    <!-- View: Gantt Chart -->
    <div class="tab-content">
      <GanttChart ref="ganttRef" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import GanttChart from './GanttChart.vue'
import {
  masterMesinPPIC,
  evaluatedSchedules,
  CURRENT_SYSTEM_DATE
} from '../../data/schedulePPIC'
import { formatNumber } from '../../data/progressPP'

const ganttRef = ref(null)

// Total unique PP
const totalUniquePP = computed(() => {
  const set = new Set(evaluatedSchedules.value.map(t => t.no_pp))
  return set.size
})

// Mesin yang saat ini sedang aktif (punya task yang 'in_progress' atau start <= today <= end)
const activeMachinesCount = computed(() => {
  const activeMachineIds = new Set()
  evaluatedSchedules.value.forEach(t => {
    if (t.status === 'in_progress' || (t.actual_start_date <= CURRENT_SYSTEM_DATE && t.actual_end_date >= CURRENT_SYSTEM_DATE)) {
      activeMachineIds.add(t.mesin_id)
    }
  })
  return activeMachineIds.size
})

// Total yang mengalami Auto-Extend
const totalExtendedCount = computed(() => {
  return evaluatedSchedules.value.filter(t => t.is_extended).length
})

// Total plano terjadwal di mesin cetak
const totalPlanoScheduled = computed(() => {
  return evaluatedSchedules.value
    .filter(t => t.stage === 'cetak')
    .reduce((acc, cur) => acc + (cur.target_qty || 0), 0)
})

// Handler saat schedule baru berhasil dibuat dari form
const onScheduleCreated = (newPP) => {
  // Beralih ke tab Gantt Chart untuk melihat hasil visual jadwal
  activeTab.value = 'gantt'
}
</script>

<style scoped>
.ppic-dashboard {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 0.5rem 0 2rem;
}

/* Header */
.dash-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  padding-bottom: 0.5rem;
}

.dash-title {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 1.45rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.02em;
}

.icon-wrap {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: #eff6ff;
  border-radius: 10px;
  font-size: 1.2rem;
}

.dash-subtitle {
  margin: 0.25rem 0 0;
  font-size: 0.88rem;
  color: #64748b;
}

.date-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.9rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.82rem;
  color: #334155;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.badge-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}

/* KPI Cards */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.kpi-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.1rem 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.07);
  border-color: #cbd5e1;
}

.kpi-warning-card {
  border-color: #fed7aa;
  background: linear-gradient(180deg, #fffcf9 0%, #ffffff 100%);
}

.kpi-warning-card:hover {
  border-color: #f97316;
}

.kpi-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 12px;
  font-size: 1.4rem;
  flex-shrink: 0;
}

.icon-blue { background: #eff6ff; color: #2563eb; }
.icon-green { background: #ecfdf5; color: #059669; }
.icon-amber { background: #fff7ed; color: #ea580c; }
.icon-indigo { background: #eef2ff; color: #4f46e5; }

.kpi-body {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.kpi-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.kpi-value {
  font-size: 1.55rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
  margin-top: 2px;
}

.kpi-sub {
  font-size: 0.95rem;
  font-weight: 600;
  color: #94a3b8;
}

.text-amber {
  color: #ea580c;
}

.kpi-hint {
  font-size: 0.72rem;
  color: #94a3b8;
  margin-top: 2px;
}

/* Tabs */
.tabs-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 0.25rem;
}

.nav-tabs {
  display: flex;
  gap: 0.5rem;
}

.tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.65rem 1.25rem;
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  margin-bottom: -0.35rem;
  font-size: 0.92rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s ease;
}

.tab-btn:hover {
  color: #1e293b;
}

.tab-btn.active {
  color: #4f46e5;
  border-bottom-color: #4f46e5;
  font-weight: 700;
}

.tabs-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-new-job {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  background: #4f46e5;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-new-job:hover {
  background: #4338ca;
}

.btn-back-gantt {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-back-gantt:hover {
  background: #e2e8f0;
}

.tab-content {
  min-height: 400px;
}

@media (max-width: 1024px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  .tabs-container {
    flex-direction: column;
    align-items: stretch;
  }
  .nav-tabs {
    flex-direction: column;
    width: 100%;
  }
  .tab-btn {
    border-bottom: none;
    border-left: 3px solid transparent;
    padding: 0.6rem 0.8rem;
    margin-bottom: 0;
  }
  .tab-btn.active {
    border-bottom: none;
    border-left-color: #4f46e5;
    background: #f8fafc;
  }
}
</style>
