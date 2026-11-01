<template>
  <div class="div-pv">
    <!-- Division KPI Stats Strip -->
    <div v-if="showHeader" class="div-stats">
      <div class="div-stat">
        <span class="div-eyebrow">Total PP Sortir</span>
        <b>{{ list.length }}</b>
        <small>Job Order</small>
      </div>
      <div class="div-stat">
        <span class="div-eyebrow">Sortir Selesai</span>
        <b class="txt-ok">{{ kpiSelesaiCount }}</b>
        <small>PP rampung</small>
      </div>
      <div class="div-stat">
        <span class="div-eyebrow">Sedang Berjalan</span>
        <b class="txt-brand">{{ kpiProsesCount }}</b>
        <small>di mesin sortir</small>
      </div>
      <div class="div-stat">
        <span class="div-eyebrow">Sortir Ulang (S2/S3)</span>
        <b class="txt-warn">{{ kpiSortirUlangCount }}</b>
        <small>PP ada S2/S3</small>
      </div>
      <div class="div-stat">
        <span class="div-eyebrow">Total Barang Good</span>
        <b class="txt-ok">{{ formatNumber(totalGoodSheets) }}</b>
        <small>pcs siap gudang</small>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="list.length === 0" class="div-card div-empty">
      Tidak ada data Job Order di Divisi Sortir.
    </div>

    <!-- Job Order Cards for Sortir -->
    <div v-else class="div-list">
      <article v-for="item in list" :key="item.id" class="div-card">
        <!-- Header -->
        <header class="div-head">
          <div class="div-head-main">
            <div class="div-pills">
              <span class="div-no">{{ item.no_pp }}</span>
              <span class="div-tag tag-srt">Sortir &amp; QC (Pcs)</span>
              <span class="div-pill" :class="getStatusTone(item.sortir.status)">
                {{ getStatusLabel(item.sortir.status) }}
              </span>
              <span v-if="item.sortir.s2 && item.sortir.s2.output > 0" class="div-pill tone-s2">Ada S2</span>
              <span v-if="item.sortir.s3 && item.sortir.s3.output > 0" class="div-pill tone-s3">Ada S3</span>
              <span v-if="item.sortir.urutan === 'sebelum_lipat'" class="div-pill-note">
                Disortir Sebelum Lipat (Flat Sheet)
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

        <!-- Sortir Stage Box with S1, S2, S3 Tiers (Normal or Skipped) -->
        <div v-if="item.sortir.status !== 'lewati'" class="div-stage-box">
          <div class="stage-top">
            <div class="stage-info">
              <span class="stage-badge">🔍 Mesin: <b>{{ item.sortir.mesin }}</b></span>
              <span class="stage-op">Operator: <b>{{ item.sortir.operator }}</b></span>
              <span v-if="item.sortir.opsi" class="stage-opt">Opsi: <i>{{ item.sortir.opsi }}</i></span>
            </div>
            <div class="stage-pct" :class="item.sortir.progress >= 100 ? 'txt-ok' : 'txt-brand'">
              {{ item.sortir.progress.toFixed(1) }}%
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="div-track">
            <div
              class="div-fill"
              :class="item.sortir.progress >= 100 ? 'bg-ok' : (item.sortir.progress > 0 ? 'bg-brand' : 'bg-antri')"
              :style="{ width: Math.min(item.sortir.progress, 100) + '%' }"
            ></div>
          </div>

          <!-- Total Metrics -->
          <div class="stage-metrics">
            <div class="metric-item">
              <span class="div-eyebrow">Input Diterima</span>
              <b>{{ formatNumber(item.sortir.plan) }} <small>pcs</small></b>
            </div>
            <div class="metric-item">
              <span class="div-eyebrow">Total Disortir</span>
              <b>{{ formatNumber(item.sortir.total_output) }} <small>pcs</small></b>
            </div>
            <div class="metric-item">
              <span class="div-eyebrow">Total Good (Siap Gudang)</span>
              <b class="txt-ok">{{ formatNumber(item.sortir.total_good) }} <small>pcs</small></b>
            </div>
            <div class="metric-item">
              <span class="div-eyebrow">Total Reject Sortir</span>
              <b class="txt-bad">{{ formatNumber(item.sortir.total_reject) }} <small>pcs</small></b>
            </div>
            <div class="metric-item">
              <span class="div-eyebrow">Sisa Belum Disortir</span>
              <b>{{ formatNumber(Math.max(0, item.sortir.plan - item.sortir.total_output)) }} <small>pcs</small></b>
            </div>
          </div>

          <!-- Bertingkat Sortir: S1, S2, S3 Sub-Tiers -->
          <div class="sortir-tiers">
            <!-- S1 -->
            <div class="st-card">
              <div class="st-head">
                <span class="tag-s1">S1</span>
                <b>Sortir Normal</b>
                <span class="st-pct">{{ getPct(item.sortir.s1.output, item.sortir.plan).toFixed(1) }}%</span>
              </div>
              <div class="st-body">
                <div>Output: <b>{{ formatNumber(item.sortir.s1.output) }}</b> pcs</div>
                <div class="st-sub">
                  <span>Good: <b class="txt-ok">{{ formatNumber(item.sortir.s1.good) }}</b></span>
                  <span>Reject: <b class="txt-bad">{{ formatNumber(item.sortir.s1.reject) }}</b></span>
                </div>
              </div>
            </div>

            <!-- S2 -->
            <div class="st-card" :class="{ 'is-empty': !item.sortir.s2 || item.sortir.s2.output === 0 }">
              <div class="st-head">
                <span class="tag-s2">S2</span>
                <b>Sortir Ulang 2</b>
                <span v-if="item.sortir.s2 && item.sortir.s2.output > 0" class="st-pct txt-warn">
                  {{ formatNumber(item.sortir.s2.output) }} pcs
                </span>
                <span v-else class="st-pct muted">-</span>
              </div>
              <div v-if="item.sortir.s2 && item.sortir.s2.output > 0" class="st-body">
                <div>Output: <b>{{ formatNumber(item.sortir.s2.output) }}</b> pcs</div>
                <div class="st-sub">
                  <span>Good: <b class="txt-ok">{{ formatNumber(item.sortir.s2.good) }}</b></span>
                  <span>Reject: <b class="txt-bad">{{ formatNumber(item.sortir.s2.reject) }}</b></span>
                </div>
              </div>
              <div v-else class="st-empty-msg">Belum ada sortir ulang S2</div>
            </div>

            <!-- S3 -->
            <div class="st-card" :class="{ 'is-empty': !item.sortir.s3 || item.sortir.s3.output === 0 }">
              <div class="st-head">
                <span class="tag-s3">S3</span>
                <b>Sortir Ulang 3</b>
                <span v-if="item.sortir.s3 && item.sortir.s3.output > 0" class="st-pct txt-s3">
                  {{ formatNumber(item.sortir.s3.output) }} pcs
                </span>
                <span v-else class="st-pct muted">-</span>
              </div>
              <div v-if="item.sortir.s3 && item.sortir.s3.output > 0" class="st-body">
                <div>Output: <b>{{ formatNumber(item.sortir.s3.output) }}</b> pcs</div>
                <div class="st-sub">
                  <span>Good: <b class="txt-ok">{{ formatNumber(item.sortir.s3.good) }}</b></span>
                  <span>Reject: <b class="txt-bad">{{ formatNumber(item.sortir.s3.reject) }}</b></span>
                </div>
              </div>
              <div v-else class="st-empty-msg">Belum ada sortir ulang S3</div>
            </div>
          </div>
        </div>

        <!-- Banner jika tahap sortir dilewati -->
        <div v-else class="div-skip-box">
          <b>ℹ️ Proses Sortir Dilewati untuk Job Order Ini</b>
          <p>{{ item.sortir.opsi }}</p>
        </div>

        <!-- Alur Terhubung ke Divisi Berikutnya (Pipeline Flow) -->
        <div class="div-pipeline">
          <div class="pipeline-title">
            <span>🔗 Status Alur Antar Divisi:</span>
            <small v-if="item.sortir.total_good > 0" class="pipeline-note">
              Produk Good Sortir ({{ formatNumber(item.sortir.total_good) }} pcs) siap diteruskan ke Lipat / Pengiriman
            </small>
            <small v-else-if="item.sortir.status === 'lewati'" class="pipeline-note">
              QC dilakukan secara inline &bull; Langsung diteruskan ke proses berikutnya
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
                current: item.sortir.status !== 'lewati',
                skip: item.sortir.status === 'lewati'
              }"
            >
              <span class="step-num">4</span>
              <span class="step-name">Sortir (Pcs)</span>
              <span class="step-status">{{ item.sortir.status === 'lewati' ? 'Dilewati' : getStatusLabel(item.sortir.status) }}</span>
            </div>
            <div class="pipe-arrow">➜</div>
            <div
              class="pipe-step"
              :class="{
                done: item.lipat.status === 'selesai',
                active: item.lipat.status === 'proses',
                skip: item.lipat.status === 'lewati'
              }"
            >
              <span class="step-num">5</span>
              <span class="step-name">Lipat (Pcs)</span>
              <span class="step-status">{{ item.lipat.status === 'lewati' ? 'Dilewati' : getStatusLabel(item.lipat.status) }}</span>
            </div>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { jobOrdersState, formatNumber, getPct, getStatusTone, getStatusLabel } from '../../data/progressPP'

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

const kpiSelesaiCount = computed(() => list.value.filter(x => x.sortir.status === 'selesai').length)
const kpiProsesCount = computed(() => list.value.filter(x => x.sortir.status === 'proses').length)
const kpiSortirUlangCount = computed(() => list.value.filter(x => (x.sortir.s2?.output > 0) || (x.sortir.s3?.output > 0)).length)
const totalGoodSheets = computed(() => list.value.reduce((acc, x) => acc + (x.sortir.total_good || 0), 0))
</script>

<style scoped>
.div-pv {
  --c-brand: #4f46e5;
  --c-ok: #10b981;
  --c-warn: #d97706;
  --c-bad: #e11d48;
  --c-s3: #7c3aed;
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
.txt-s3 { color: var(--c-s3); }
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
.tag-srt { background: #ede9fe; color: #6d28d9; }
.div-pill { font-size: 0.72rem; font-weight: 700; padding: 3px 9px; border-radius: 99px; }
.div-pill-note { font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 99px; background: #fdf4ff; color: #a21caf; border: 1px solid #f0abfc; }
.tone-ok { background: #d1fae5; color: #065f46; }
.tone-brand { background: #e0e7ff; color: #3730a3; }
.tone-warn { background: #fef3c7; color: #92400e; }
.tone-muted { background: #f1f5f9; color: #475569; }
.tone-s2 { background: #fff7ed; color: #b45309; border: 1px solid #fed7aa; }
.tone-s3 { background: #ede9fe; color: #5b21b6; }

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

/* Sortir Sub-Tiers */
.sortir-tiers {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.6rem;
  margin-top: 0.3rem;
}
.st-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.st-card.is-empty { background: #f8fafc; border-style: dashed; }
.st-head { display: flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; }
.st-head b { flex: 1; color: var(--c-ink); }
.st-pct { font-weight: 700; font-size: 0.82rem; }
.st-pct.muted { color: var(--c-muted); }
.tag-s1 { background: #e0e7ff; color: #4338ca; font-size: 0.68rem; font-weight: 800; padding: 1px 6px; border-radius: 4px; }
.tag-s2 { background: #fef3c7; color: #b45309; font-size: 0.68rem; font-weight: 800; padding: 1px 6px; border-radius: 4px; }
.tag-s3 { background: #ede9fe; color: #6d28d9; font-size: 0.68rem; font-weight: 800; padding: 1px 6px; border-radius: 4px; }
.st-body { font-size: 0.76rem; display: flex; flex-direction: column; gap: 2px; }
.st-sub { display: flex; gap: 0.8rem; font-size: 0.72rem; }
.st-empty-msg { font-size: 0.72rem; color: var(--c-muted); font-style: italic; }

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
.pipeline-note { color: #059669; font-weight: 700; font-size: 0.74rem; }
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
  background: #ede9fe;
  border-color: #a78bfa;
  color: #5b21b6;
  font-weight: 700;
  box-shadow: 0 1px 4px rgba(139, 92, 246, 0.2);
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
  .sortir-tiers { grid-template-columns: 1fr; }
}
</style>
