<template>
  <div class="ppic-jadwal-view">
    <!-- Header -->
    <div class="card mb-1">
      <div>
        <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
          <span>📅</span> Penjadwalan Mesin &amp; Gantt Chart Kapasitas
        </h2>
        <small class="text-muted">
          Tracking jadwal produksi otomatis per jam dengan kalkulasi durasi per menit, alokasi mesin bertingkat, dan evaluasi batas pengiriman logistik
        </small>
      </div>
    </div>

    <!-- Gantt Chart Component (Tampilan per jam, hitungan per menit) -->
    <GanttChart />
  </div>
</template>

<script setup>
import GanttChart from './GanttChart.vue'
</script>

<style scoped>
.ppic-jadwal-view {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
</style>
