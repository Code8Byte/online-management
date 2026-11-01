<template>
  <DivisionOperatorDashboard
    division-key="cetak"
    division-name="Divisi Cetak"
    brand-color="#185FA5"
    brand-color2="#2563eb"
    :oee-target="85"
    :kpi="kpiData"
    :timeline="timelineData"
  />
</template>

<script setup>
import DivisionOperatorDashboard from '../../components/DivisionOperatorDashboard.vue'

const kpiData = {
  oee: 85.6,
  availability: 91.5,
  performance: 94.8,
  quality: 98.9,
  total_proses: 245000,
  good: 239800,
  reject_percent: 2.12,
  reject_pcs: 5200
}

const timelineData = [
  { tgl: '2026-10-18', oee: 83.5, availability: 89.5, performance: 93.8 },
  { tgl: '2026-10-19', oee: 84.8, availability: 90.2, performance: 94.2 },
  { tgl: '2026-10-20', oee: 85.2, availability: 91.0, performance: 94.5 },
  { tgl: '2026-10-21', oee: 84.0, availability: 90.0, performance: 94.0 },
  { tgl: '2026-10-22', oee: 86.4, availability: 92.0, performance: 95.2 },
  { tgl: '2026-10-23', oee: 85.9, availability: 91.6, performance: 95.0 },
  { tgl: '2026-10-24', oee: 84.5, availability: 90.5, performance: 94.1 },
  { tgl: '2026-10-25', oee: 86.8, availability: 92.4, performance: 95.6 },
  { tgl: '2026-10-26', oee: 85.5, availability: 91.2, performance: 94.7 },
  { tgl: '2026-10-27', oee: 84.2, availability: 90.4, performance: 94.0 },
  { tgl: '2026-10-28', oee: 87.1, availability: 92.8, performance: 95.8 },
  { tgl: '2026-10-29', oee: 86.0, availability: 91.9, performance: 95.1 },
  { tgl: '2026-10-30', oee: 85.8, availability: 91.7, performance: 94.9 },
  { tgl: '2026-10-31', oee: 85.6, availability: 91.5, performance: 94.8 }
]
</script>
