<template>
  <div class="div-lkh-list">
    <!-- Header -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>📋</span> Data LKH - Divisi Completing
          </h2>
          <small class="text-muted">
            Rekap Laporan Kerja Harian Finishing (Hot Foil, UV Coating, Laminasi Flute) • Satuan: <b>Lembar Plano</b>
          </small>
        </div>
        <div class="d-flex gap-05 align-center">
          <router-link to="/completing/lkh/baru" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <span>✍️</span> + Isi LKH Completing Baru
          </router-link>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-1 p-08">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div class="d-flex gap-08 align-center flex-wrap" style="flex: 1;">
          <div style="min-width: 140px;">
            <select v-model="filterShift" class="form-control">
              <option value="">Semua Shift</option>
              <option value="1">Shift 1</option>
              <option value="2">Shift 2</option>
              <option value="3">Shift 3</option>
            </select>
          </div>
          <div style="min-width: 220px;">
            <select v-model="filterMesin" class="form-control">
              <option value="">Semua Mesin Finishing</option>
              <option value="Hot Stamping Foil 1 & UV Gloss">Hot Stamping Foil 1 &amp; UV Gloss</option>
              <option value="Hot Stamping Foil 2">Hot Stamping Foil 2</option>
              <option value="Varnish UV Gloss Obat">Varnish UV Gloss Obat</option>
              <option value="Laminasi Flute E/F">Laminasi Flute E/F</option>
              <option value="Laminasi Doff & Spot UV">Laminasi Doff &amp; Spot UV</option>
            </select>
          </div>
          <div style="position: relative; flex: 1; min-width: 200px;">
            <input type="text" v-model="searchQuery" class="form-control" placeholder="🔍 Cari No PP atau Operator..." />
          </div>
        </div>
      </div>
    </div>

    <!-- Table Rekap LKH Completing -->
    <div class="card p-0">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th width="40" class="text-center">No</th>
              <th>Tanggal &amp; Shift</th>
              <th>Mesin &amp; Operator</th>
              <th>Job Order (PP) &amp; Produk</th>
              <th>Input dari Cetak</th>
              <th>Realisasi Finishing</th>
              <th>Hasil Baik (Good)</th>
              <th>Afval / Reject</th>
              <th>Status</th>
              <th width="120" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in filteredList" :key="item.id">
              <td class="text-center text-muted">{{ idx + 1 }}</td>
              <td>
                <b>{{ item.tanggal }}</b>
                <div class="text-muted" style="font-size: 0.78rem;">Shift {{ item.shift }}</div>
              </td>
              <td>
                <span class="badge" style="background: #fef3c7; color: #b45309; font-size: 0.75rem;">{{ item.mesin }}</span>
                <div style="font-size: 0.82rem; font-weight: 600; margin-top: 2px;">{{ item.operator }}</div>
              </td>
              <td>
                <b class="text-primary">{{ item.no_pp }}</b>
                <div style="font-size: 0.8rem; color: #475569;">{{ item.produk_nama }}</div>
              </td>
              <td><b>{{ formatNumber(item.input_plano) }}</b> <small class="text-muted">plano</small></td>
              <td><b>{{ formatNumber(item.realisasi) }}</b> <small class="text-muted">plano</small></td>
              <td><b class="text-success">{{ formatNumber(item.hasil_baik) }}</b> <small class="text-muted">plano</small></td>
              <td><b class="text-danger">{{ formatNumber(item.afval_plano) }}</b> <small class="text-muted">plano</small></td>
              <td>
                <span class="badge" :class="item.status === 'selesai' ? 'badge-success' : 'badge-warning'">
                  {{ item.status === 'selesai' ? 'Selesai Finishing' : 'Running' }}
                </span>
              </td>
              <td class="text-center text-nowrap">
                <button class="btn btn-secondary btn-sm" @click="viewDetail(item)" style="padding: 0.25rem 0.6rem;">
                  👁️ Detail
                </button>
              </td>
            </tr>
            <tr v-if="filteredList.length === 0">
              <td colspan="10" class="text-center text-muted" style="padding: 2rem;">
                Tidak ada data LKH Completing yang sesuai filter.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { formatNumber } from '../../data/progressPP'
import { showAlert } from '../modal/modalService'

const filterShift = ref('')
const filterMesin = ref('')
const searchQuery = ref('')

const lkhCompletingList = ref([
  {
    id: 1,
    tanggal: '2026-10-31',
    shift: '1',
    mesin: 'Hot Stamping Foil 1 & UV Gloss',
    operator: 'Nico & Wahyudi',
    no_pp: 'PP-2026-0821',
    produk_nama: 'Etiket Rokok Djarum 12 Super',
    input_plano: 48500,
    realisasi: 45000,
    hasil_baik: 44200,
    afval_plano: 800,
    cacat_dominan: 'Foil Rontok & Pinhole UV',
    status: 'running'
  },
  {
    id: 2,
    tanggal: '2026-10-31',
    shift: '2',
    mesin: 'Varnish UV Gloss Obat',
    operator: 'Sodok & Yanto',
    no_pp: 'PP-2026-0824',
    produk_nama: 'Box Farmasi Kalbe Farma 500mg',
    input_plano: 39500,
    realisasi: 39200,
    hasil_baik: 38800,
    afval_plano: 400,
    cacat_dominan: 'Coating Varnish Tebal',
    status: 'selesai'
  },
  {
    id: 3,
    tanggal: '2026-10-29',
    shift: '1',
    mesin: 'Laminasi Doff & Spot UV',
    operator: 'Nico & Wahyudi',
    no_pp: 'PP-2026-0815',
    produk_nama: 'Brosur Promosi Ekspor Quality',
    input_plano: 24700,
    realisasi: 24600,
    hasil_baik: 24300,
    afval_plano: 300,
    cacat_dominan: 'Gelembung Doff Halus',
    status: 'selesai'
  }
])

const filteredList = computed(() => {
  return lkhCompletingList.value.filter(item => {
    if (filterShift.value && item.shift !== filterShift.value) return false
    if (filterMesin.value && item.mesin !== filterMesin.value) return false
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase().trim()
      const matchNo = item.no_pp.toLowerCase().includes(q)
      const matchOp = item.operator.toLowerCase().includes(q)
      const matchProd = item.produk_nama.toLowerCase().includes(q)
      if (!matchNo && !matchOp && !matchProd) return false
    }
    return true
  })
})

const viewDetail = (item) => {
  showAlert(
    `Detail LKH Completing:\nNomor PP: ${item.no_pp}\nProduk: ${item.produk_nama}\nMesin: ${item.mesin}\nOperator: ${item.operator}\nInput dari Cetak: ${formatNumber(item.input_plano)} Plano\nRealisasi: ${formatNumber(item.realisasi)} Plano\nHasil Baik: ${formatNumber(item.hasil_baik)} Plano\nAfval: ${formatNumber(item.afval_plano)} Plano (${item.cacat_dominan})`,
    { title: 'Rincian LKH Completing' }
  )
}
</script>

<style scoped>
.div-lkh-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
</style>
