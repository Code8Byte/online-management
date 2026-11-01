<template>
  <div class="ppic-jadwal-view">
    <!-- Header -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>📅</span> Penjadwalan &amp; Gantt Chart Kapasitas Mesin
          </h2>
          <small class="text-muted">
            Tracking jadwal produksi otomatis berbasis kapasitas mesin, oplaag &bull; plano kalkulasi, serta deteksi auto-extend jika pekerjaan belum selesai
          </small>
        </div>
        <div class="d-flex gap-05 align-center">
          <button
            type="button"
            class="btn"
            :class="activeView === 'gantt' ? 'btn-primary' : 'btn-secondary'"
            @click="activeView = 'gantt'"
          >
            <span>📊</span> Gantt Chart
          </button>
          <button
            type="button"
            class="btn"
            :class="activeView === 'form' ? 'btn-primary' : 'btn-secondary'"
            @click="activeView = 'form'"
          >
            <span>➕</span> Input Jadwal Baru
          </button>
        </div>
      </div>
    </div>

    <!-- Main View Component -->
    <div v-if="activeView === 'gantt'">
      <GanttChart @create-new="activeView = 'form'" />
    </div>
    <div v-else>
      <ScheduleForm
        @saved="handleScheduleSaved"
        @cancel="activeView = 'gantt'"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import GanttChart from './GanttChart.vue'
import ScheduleForm from './ScheduleForm.vue'
import { showAlert } from '../../view/modal/modalService'

const activeView = ref('gantt')

const handleScheduleSaved = async (newJob) => {
  await showAlert(
    `Jadwal untuk No PP ${newJob?.no_pp || '-'} berhasil ditambahkan ke dalam sistem antrian kapasitas mesin!`,
    {
      title: 'Jadwal Berhasil Disimpan',
      type: 'success'
    }
  )
  activeView.value = 'gantt'
}
</script>

<style scoped>
.ppic-jadwal-view {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
</style>
