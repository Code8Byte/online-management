<template>
  <DivisionOperatorDashboard
    division-key="lipat"
    division-name="Divisi Lipat"
    brand-color="#993556"
    brand-color2="#e11d48"
    :oee-target="85"
    :kpi="kpiData"
    :timeline="timelineData"
  />
</template>

<script setup>
import DivisionOperatorDashboard from '../../components/DivisionOperatorDashboard.vue'

const kpiData = {
  oee: 89.2,
  availability: 94.6,
  performance: 95.0,
  quality: 99.4,
  total_proses: 210000,
  good: 207200,
  reject_percent: 1.33,
  reject_pcs: 2800
}

const timelineData = [
  { tgl: '2026-10-18', oee: 87.5, availability: 93.5, performance: 94.0 },
  { tgl: '2026-10-19', oee: 88.6, availability: 94.0, performance: 94.8 },
  { tgl: '2026-10-20', oee: 89.4, availability: 94.8, performance: 95.2 },
  { tgl: '2026-10-21', oee: 87.9, availability: 93.8, performance: 94.2 },
  { tgl: '2026-10-22', oee: 89.8, availability: 95.0, performance: 95.5 },
  { tgl: '2026-10-23', oee: 89.2, availability: 94.6, performance: 95.1 },
  { tgl: '2026-10-24', oee: 88.5, availability: 94.1, performance: 94.7 },
  { tgl: '2026-10-25', oee: 90.1, availability: 95.2, performance: 95.8 },
  { tgl: '2026-10-26', oee: 89.0, availability: 94.4, performance: 95.0 },
  { tgl: '2026-10-27', oee: 88.2, availability: 93.9, performance: 94.5 },
  { tgl: '2026-10-28', oee: 90.4, availability: 95.4, performance: 96.0 },
  { tgl: '2026-10-29', oee: 89.6, availability: 94.8, performance: 95.3 },
  { tgl: '2026-10-30', oee: 89.4, availability: 94.7, performance: 95.1 },
  { tgl: '2026-10-31', oee: 89.2, availability: 94.6, performance: 95.0 }
]
</script>
