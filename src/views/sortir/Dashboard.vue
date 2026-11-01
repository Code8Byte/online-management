<template>
  <DivisionOperatorDashboard
    division-key="sortir"
    division-name="Divisi Sortir"
    brand-color="#0F6E56"
    brand-color2="#10b981"
    :oee-target="85"
    :kpi="kpiData"
    :timeline="timelineData"
  />
</template>

<script setup>
import DivisionOperatorDashboard from '../../components/DivisionOperatorDashboard.vue'

const kpiData = {
  oee: 88.5,
  availability: 92.4,
  performance: 96.1,
  quality: 99.6,
  total_proses: 185000,
  good: 181300,
  reject_percent: 2.00,
  reject_pcs: 3700
}

const timelineData = [
  { tgl: '2026-10-18', oee: 86.2, availability: 91.0, performance: 95.0 },
  { tgl: '2026-10-19', oee: 87.5, availability: 91.5, performance: 95.8 },
  { tgl: '2026-10-20', oee: 88.0, availability: 92.0, performance: 96.0 },
  { tgl: '2026-10-21', oee: 85.8, availability: 90.5, performance: 95.2 },
  { tgl: '2026-10-22', oee: 89.2, availability: 93.0, performance: 96.5 },
  { tgl: '2026-10-23', oee: 88.4, availability: 92.5, performance: 96.0 },
  { tgl: '2026-10-24', oee: 87.9, availability: 91.8, performance: 95.9 },
  { tgl: '2026-10-25', oee: 89.5, availability: 93.5, performance: 96.8 },
  { tgl: '2026-10-26', oee: 88.1, availability: 92.0, performance: 96.2 },
  { tgl: '2026-10-27', oee: 87.0, availability: 91.2, performance: 95.5 },
  { tgl: '2026-10-28', oee: 89.8, availability: 93.8, performance: 97.0 },
  { tgl: '2026-10-29', oee: 88.6, availability: 92.7, performance: 96.3 },
  { tgl: '2026-10-30', oee: 89.0, availability: 93.0, performance: 96.4 },
  { tgl: '2026-10-31', oee: 88.5, availability: 92.4, performance: 96.1 }
]
</script>
