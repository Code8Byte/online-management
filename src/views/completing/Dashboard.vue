<template>
  <DivisionOperatorDashboard
    division-key="completing"
    division-name="Divisi Completing"
    brand-color="#534AB7"
    brand-color2="#7c3aed"
    :oee-target="85"
    :kpi="kpiData"
    :timeline="timelineData"
  />
</template>

<script setup>
import DivisionOperatorDashboard from '../../components/DivisionOperatorDashboard.vue'

const kpiData = {
  oee: 86.8,
  availability: 93.2,
  performance: 94.1,
  quality: 99.1,
  total_proses: 178000,
  good: 175100,
  reject_percent: 1.63,
  reject_pcs: 2900
}

const timelineData = [
  { tgl: '2026-10-18', oee: 85.0, availability: 91.8, performance: 93.2 },
  { tgl: '2026-10-19', oee: 86.2, availability: 92.5, performance: 93.8 },
  { tgl: '2026-10-20', oee: 87.0, availability: 93.0, performance: 94.2 },
  { tgl: '2026-10-21', oee: 85.5, availability: 92.0, performance: 93.6 },
  { tgl: '2026-10-22', oee: 87.5, availability: 93.8, performance: 94.5 },
  { tgl: '2026-10-23', oee: 86.9, availability: 93.2, performance: 94.1 },
  { tgl: '2026-10-24', oee: 86.0, availability: 92.6, performance: 93.7 },
  { tgl: '2026-10-25', oee: 87.8, availability: 94.0, performance: 94.8 },
  { tgl: '2026-10-26', oee: 86.4, availability: 93.0, performance: 94.0 },
  { tgl: '2026-10-27', oee: 85.8, availability: 92.4, performance: 93.5 },
  { tgl: '2026-10-28', oee: 88.0, availability: 94.2, performance: 95.0 },
  { tgl: '2026-10-29', oee: 87.1, availability: 93.4, performance: 94.3 },
  { tgl: '2026-10-30', oee: 86.9, availability: 93.1, performance: 94.2 },
  { tgl: '2026-10-31', oee: 86.8, availability: 93.2, performance: 94.1 }
]
</script>
