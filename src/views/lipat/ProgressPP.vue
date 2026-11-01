<template>
  <div class="div-pv">
    <!-- Division KPI Stats Strip -->
    <div v-if="showHeader" class="div-stats">
      <div class="div-stat">
        <span class="div-eyebrow">Total PP Lipat</span>
        <b>{{ list.length }}</b>
        <small>Job Order</small>
      </div>
      <div class="div-stat">
        <span class="div-eyebrow">Lipat / Lem Selesai</span>
        <b class="txt-ok">{{ kpiSelesaiCount }}</b>
        <small>PP rampung</small>
      </div>
      <div class="div-stat">
        <span class="div-eyebrow">Sedang Berjalan</span>
        <b class="txt-brand">{{ kpiProsesCount }}</b>
        <small>di mesin gluer</small>
      </div>
      <div class="div-stat">
        <span class="div-eyebrow">Antri / Dilewati</span>
        <b class="txt-warn">{{ kpiAntriCount + kpiSkipCount }}</b>
        <small>{{ kpiSkipCount }} PP dilewati</small>
      </div>
      <div class="div-stat">
        <span class="div-eyebrow">Total Box Good</span>
        <b class="txt-ok">{{ formatNumber(totalGoodSheets) }}</b>
        <small>pcs selesai lem</small>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="list.length === 0" class="div-card div-empty">
      Tidak ada data Job Order di Divisi Lipat.
    </div>

    <!-- Job Order Cards for Lipat -->
    <div v-else class="div-list">
      <article v-for="item in list" :key="item.id" class="div-card">
        <!-- Header -->
        <header class="div-head">
          <div class="div-head-main">
            <div class="div-pills">
              <span class="div-no">{{ item.no_pp }}</span>
              <span class="div-tag tag-lpt">Lipat &amp; Lem (Pcs)</span>
              <span class="div-pill" :class="getStatusTone(item.lipat.status)">
                {{ getStatusLabel(item.lipat.status) }}
              </span>
              <span v-if="item.lipat.urutan === 'setelah_sortir'" class="div-pill-note">
                Dikerjakan Setelah Sortir Flat Sheet
              </span>
            </div>
            <h3>{{ item.produk_nama }}</h3>
            <p>Update Terakhir: <b>{{ item.last_update }}</b> &bull; Alur: <i>{{ item.alur_label }}</i></p>
          </div>

          <div class="div-actions">
            <router-link
              :to="`/lkh?no_pp=${encodeURIComponent(item.no_pp)}`"
              class="div-btn div-btn-dark div-btn-sm"
              title="Lihat riwayat LKH PP ini"
            >
              📋 Riwayat LKH
            </router-link>
          </div>
        </header>

        <!-- Lipat Production Focus Box (Normal or Skipped) -->
        <div v-if="item.lipat.status !== 'lewati'" class="div-stage-box">
          <div class="stage-top">
            <div class="stage-info">
              <span class="stage-badge">📦 Mesin Gluer: <b>{{ item.lipat.mesin }}</b></span>
              <span class="stage-op">Operator: <b>{{ item.lipat.operator }}</b></span>
              <span v-if="item.lipat.opsi" class="stage-opt">Opsi: <i>{{ item.lipat.opsi }}</i></span>
            </div>
            <div class="stage-pct" :class="item.lipat.progress >= 100 ? 'txt-ok' : 'txt-brand'">
              {{ item.lipat.progress.toFixed(1) }}%
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="div-track">
            <div
              class="div-fill"
              :class="item.lipat.progress >= 100 ? 'bg-ok' : (item.lipat.progress > 0 ? 'bg-brand' : 'bg-antri')"
              :style="{ width: Math.min(item.lipat.progress, 100) + '%' }"
            ></div>
          </div>

          <!-- Production Numbers -->
          <div class="stage-metrics">
            <div class="metric-item">
              <span class="div-eyebrow">Input Diterima</span>
              <b>{{ formatNumber(item.lipat.plan) }} <small>pcs</small></b>
            </div>
            <div class="metric-item">
              <span class="div-eyebrow">Realisasi Lipat</span>
              <b>{{ formatNumber(item.lipat.output) }} <small>pcs</small></b>
            </div>
            <div class="metric-item">
              <span class="div-eyebrow">Box Good (Hasil Baik)</span>
              <b class="txt-ok">{{ formatNumber(item.lipat.good) }} <small>pcs</small></b>
            </div>
            <div class="metric-item">
              <span class="div-eyebrow">Afval / Reject Lipat</span>
              <b class="txt-bad">{{ formatNumber(item.lipat.reject) }} <small>pcs</small></b>
            </div>
            <div class="metric-item">
              <span class="div-eyebrow">Sisa Belum Dilipat</span>
              <b>{{ formatNumber(Math.max(0, item.lipat.plan - item.lipat.output)) }} <small>pcs</small></b>
            </div>
          </div>
        </div>

        <!-- Banner jika tahap lipat dilewati -->
        <div v-else class="div-skip-box">
          <b>ℹ️ Proses Lipat Dilewati untuk Job Order Ini</b>
          <p>{{ item.lipat.opsi }}</p>
        </div>

        <!-- Alur Terhubung ke Divisi Berikutnya (Pipeline Flow) -->
        <div class="div-pipeline">
          <div class="pipeline-title">
            <span>🔗 Status Alur Antar Divisi:</span>
            <small v-if="item.lipat.good > 0" class="pipeline-note">
              Box Good Lipat ({{ formatNumber(item.lipat.good) }} pcs) siap diteruskan ke tahap berikutnya
            </small>
            <small v-else-if="item.lipat.status === 'lewati'" class="pipeline-note">
              Produk lembaran tidak memerlukan proses pengeleman
            </small>
          </div>
          <div class="pipeline-steps">
            <div class="pipe-step" :class="{ done: item.cetak.status === 'selesai', active: item.cetak.status === 'proses' }">
              <span class="step-num">1</span>
              <span class="step-name">Cetak (Plano)</span>
              <span class="step-status">{{ getStatusLabel(item.cetak.status) }}</span>
            </div>
            <div class="pipe-arrow">➜</div>
            <div class="pipe-step" :class="{ done: item.completing.status === 'selesai', active: item.completing.status === 'proses' }">
              <span class="step-num">2</span>
              <span class="step-name">Completing (Plano)</span>
              <span class="step-status">{{ getStatusLabel(item.completing.status) }}</span>
            </div>
            <div class="pipe-arrow">➜</div>
            <div class="pipe-step" :class="{ done: item.cabut.status === 'selesai', active: item.cabut.status === 'proses' }">
              <span class="step-num">3</span>
              <span class="step-name">Cabut (Plano &rarr; Pcs)</span>
              <span class="step-status">{{ getStatusLabel(item.cabut.status) }}</span>
            </div>
            <div class="pipe-arrow">➜</div>
            <div
              class="pipe-step"
              :class="{
                current: item.lipat.status !== 'lewati',
                skip: item.lipat.status === 'lewati'
              }"
            >
              <span class="step-num">4</span>
              <span class="step-name">Lipat (Pcs)</span>
              <span class="step-status">{{ item.lipat.status === 'lewati' ? 'Dilewati' : getStatusLabel(item.lipat.status) }}</span>
            </div>
            <div class="pipe-arrow">➜</div>
            <div
              class="pipe-step"
              :class="{
                done: item.sortir.status === 'selesai',
                active: item.sortir.status === 'proses',
                skip: item.sortir.status === 'lewati'
              }"
            >
              <span class="step-num">5</span>
              <span class="step-name">Sortir (Pcs)</span>
              <span class="step-status">{{ item.sortir.status === 'lewati' ? 'Dilewati' : getStatusLabel(item.sortir.status) }}</span>
            </div>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { jobOrdersState, formatNumber, getStatusTone, getStatusLabel } from '../../data/progressPP'

const props = defineProps({
  items: {
    type: Array,
    default: null
  },
  showHeader: {
    type: Boolean,
    default: true
  }
})

const list = computed(() => {
  return props.items !== null ? props.items : jobOrdersState.value
})

const kpiSelesaiCount = computed(() => list.value.filter(x => x.lipat.status === 'selesai').length)
const kpiProsesCount = computed(() => list.value.filter(x => x.lipat.status === 'proses').length)
const kpiAntriCount = computed(() => list.value.filter(x => x.lipat.status === 'antri').length)
const kpiSkipCount = computed(() => list.value.filter(x => x.lipat.status === 'lewati').length)
const totalGoodSheets = computed(() => list.value.reduce((acc, x) => acc + (x.lipat.good || 0), 0))
</script>

<style scoped>
.div-pv {
  --c-brand: #4f46e5;
  --c-ok: #10b981;
  --c-warn: #d97706;
  --c-bad: #e11d48;
  --c-ink: #0f172a;
  --c-text: #334155;
  --c-muted: #64748b;
  --c-line: #e2e8f0;
  color: var(--c-text);
}

.div-eyebrow { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; color: var(--c-muted); letter-spacing: 0.04em; }
.txt-ok { color: var(--c-ok); }
.txt-brand { color: var(--c-brand); }
.txt-warn { color: var(--c-warn); }
.txt-bad { color: var(--c-bad); }
.bg-ok { background: var(--c-ok); }
.bg-brand { background: linear-gradient(90deg, #6366f1, var(--c-brand)); }
.bg-antri { background: #cbd5e1; }

.div-stats {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  background: #fff;
  border: 1px solid var(--c-line);
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
  margin-bottom: 1rem;
}
.div-stat { padding: 0.8rem 1rem; display: flex; flex-wrap: wrap; align-items: baseline; gap: 0 6px; }
.div-stat + .div-stat { border-left: 1px solid var(--c-line); }
.div-stat .div-eyebrow { width: 100%; margin-bottom: 2px; }
.div-stat b { font-size: 1.45rem; font-weight: 800; color: var(--c-ink); }
.div-stat small { font-size: 0.74rem; color: var(--c-muted); }

.div-list { display: flex; flex-direction: column; gap: 1rem; }
.div-card {
  background: #fff;
  border: 1px solid var(--c-line);
  border-radius: 12px;
  padding: 1.1rem;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  transition: box-shadow 0.15s;
}
.div-card:hover { box-shadow: 0 6px 20px rgba(15, 23, 42, 0.09); }
.div-empty { text-align: center; padding: 2.5rem; color: var(--c-muted); }

.div-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; flex-wrap: wrap; }
.div-pills { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.div-no {
  font-size: 0.95rem; font-weight: 800; color: var(--c-brand);
  background: #eef2ff; border: 1px solid #c7d2fe; padding: 2px 10px; border-radius: 8px;
}
.div-tag { font-size: 0.72rem; font-weight: 800; padding: 3px 9px; border-radius: 99px; }
.tag-lpt { background: #dcfce7; color: #15803d; }
.div-pill { font-size: 0.72rem; font-weight: 700; padding: 3px 9px; border-radius: 99px; }
.div-pill-note { font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: #fdf4ff; color: #a21caf; border: 1px solid #f0abfc; }
.tone-ok { background: #d1fae5; color: #065f46; }
.tone-brand { background: #e0e7ff; color: #3730a3; }
.tone-warn { background: #fef3c7; color: #92400e; }
.tone-muted { background: #f1f5f9; color: #475569; }

.div-head h3 { margin: 0.45rem 0 0; font-size: 1.08rem; font-weight: 700; color: var(--c-ink); }
.div-head p { margin: 2px 0 0; font-size: 0.78rem; color: var(--c-muted); }

.div-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  height: 32px; padding: 0 0.8rem; font-size: 0.78rem; font-weight: 600; text-decoration: none;
  border-radius: 8px; cursor: pointer; transition: background 0.15s;
}
.div-btn-dark { background: #1e293b; color: #fff; }
.div-btn-dark:hover { background: #0f172a; }

/* Stage Box */
.div-stage-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}
.stage-top { display: flex; justify-content: space-between; align-items: center; }
.stage-info { display: flex; gap: 0.8rem; align-items: center; flex-wrap: wrap; font-size: 0.82rem; }
.stage-badge { background: #fff; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 6px; }
.stage-opt { color: #0284c7; }
.stage-pct { font-size: 1.3rem; font-weight: 800; letter-spacing: -0.02em; }

.div-track { position: relative; height: 10px; background: #e2e8f0; border-radius: 99px; overflow: hidden; }
.div-fill { height: 100%; border-radius: 99px; transition: width 0.4s ease; }

.stage-metrics {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.5rem;
  background: #fff;
  border: 1px solid var(--c-line);
  border-radius: 8px;
  padding: 0.6rem 0.8rem;
}
.metric-item { display: flex; flex-direction: column; gap: 2px; }
.metric-item b { font-size: 0.95rem; font-weight: 800; color: var(--c-ink); }
.metric-item small { font-size: 0.7rem; font-weight: 500; color: var(--c-muted); }

.div-skip-box {
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  padding: 1rem;
  color: #64748b;
  font-size: 0.85rem;
}
.div-skip-box b { color: #334155; }
.div-skip-box p { margin: 4px 0 0; }

/* Pipeline Strip */
.div-pipeline {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.65rem 0.85rem;
}
.pipeline-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.76rem;
  font-weight: 700;
  color: #334155;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
  gap: 0.4rem;
}
.pipeline-note { color: #0284c7; font-weight: 600; font-size: 0.74rem; }
.pipeline-steps { display: flex; align-items: center; gap: 0.4rem; overflow-x: auto; padding-bottom: 2px; }
.pipe-step {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #fff;
  border: 1px solid #cbd5e1;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.74rem;
  white-space: nowrap;
}
.pipe-step.current {
  background: #dcfce7;
  border-color: #4ade80;
  color: #166534;
  font-weight: 700;
  box-shadow: 0 1px 4px rgba(22, 163, 74, 0.2);
}
.pipe-step.done {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #065f46;
}
.pipe-step.active {
  background: #fef3c7;
  border-color: #fde68a;
  color: #92400e;
}
.pipe-step.skip {
  background: #f8fafc;
  border-style: dashed;
  color: #94a3b8;
}
.step-num {
  width: 16px;
  height: 16px;
  border-radius: 99px;
  background: rgba(0, 0, 0, 0.08);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
  font-weight: 800;
}
.step-name { font-weight: 700; }
.step-status { font-size: 0.68rem; opacity: 0.85; margin-left: 2px; }
.pipe-arrow { color: #94a3b8; font-size: 0.8rem; }

@media (max-width: 900px) {
  .div-stats { grid-template-columns: repeat(2, 1fr); }
  .div-stat:nth-child(3) { border-left: none; }
  .div-stat:nth-child(n+3) { border-top: 1px solid var(--c-line); }
  .stage-metrics { grid-template-columns: repeat(2, 1fr); }
}
</style>
