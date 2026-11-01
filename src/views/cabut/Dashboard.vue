<template>
  <DivisionOperatorDashboard
    division-key="cabut"
    division-name="Divisi Cabut"
    brand-color="#993C1D"
    brand-color2="#ea580c"
    :oee-target="85"
    :kpi="kpiData"
    :timeline="timelineData"
  />
</template>

<script setup>
import DivisionOperatorDashboard from '../../components/DivisionOperatorDashboard.vue'

const kpiData = {
  oee: 87.4,
  availability: 93.8,
  performance: 93.9,
  quality: 99.3,
  total_proses: 152000,
  good: 149800,
  reject_percent: 1.45,
  reject_pcs: 2200
}

const timelineData = [
  { tgl: '2026-10-18', oee: 85.8, availability: 92.5, performance: 93.0 },
  { tgl: '2026-10-19', oee: 86.9, availability: 93.1, performance: 93.6 },
  { tgl: '2026-10-20', oee: 87.5, availability: 93.8, performance: 94.0 },
  { tgl: '2026-10-21', oee: 86.2, availability: 92.8, performance: 93.4 },
  { tgl: '2026-10-22', oee: 88.0, availability: 94.2, performance: 94.4 },
  { tgl: '2026-10-23', oee: 87.6, availability: 93.9, performance: 94.1 },
  { tgl: '2026-10-24', oee: 86.8, availability: 93.4, performance: 93.7 },
  { tgl: '2026-10-25', oee: 88.5, availability: 94.6, performance: 94.8 },
  { tgl: '2026-10-26', oee: 87.1, availability: 93.5, performance: 93.9 },
  { tgl: '2026-10-27', oee: 86.5, availability: 93.0, performance: 93.5 },
  { tgl: '2026-10-28', oee: 88.7, availability: 94.8, performance: 94.9 },
  { tgl: '2026-10-29', oee: 87.8, availability: 94.0, performance: 94.2 },
  { tgl: '2026-10-30', oee: 87.6, availability: 93.9, performance: 94.0 },
  { tgl: '2026-10-31', oee: 87.4, availability: 93.8, performance: 93.9 }
]
</script>
