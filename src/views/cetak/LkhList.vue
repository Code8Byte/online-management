<template>
  <div class="div-lkh-list">
    <!-- Header -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>📋</span> Data LKH - Divisi Cetak
          </h2>
          <small class="text-muted">
            Rekap Laporan Kerja Harian Divisi Cetak Offset • Satuan: <b>Lembar Plano</b>
          </small>
        </div>
        <div class="d-flex gap-05 align-center">
          <router-link to="/cetak/lkh/baru" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <span>✍️</span> + Isi LKH Cetak Baru
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
          <div style="min-width: 200px;">
            <select v-model="filterMesin" class="form-control">
              <option value="">Semua Mesin Cetak</option>
              <option value="SM 23 (Heidelberg)">SM 23 (Heidelberg Speedmaster)</option>
              <option value="SM 25 (Heidelberg)">SM 25 (Heidelberg CD 102)</option>
              <option value="SM 27 (Heidelberg)">SM 27 (Heidelberg SM)</option>
              <option value="KBA Rapida 106">KBA Rapida 106 High Speed</option>
              <option value="SM 10 (Heidelberg)">SM 10 (Heidelberg 2 Warna)</option>
            </select>
          </div>
          <div style="position: relative; flex: 1; min-width: 200px;">
            <input type="text" v-model="searchQuery" class="form-control" placeholder="🔍 Cari No PP atau Operator Cetak..." />
          </div>
        </div>
      </div>
    </div>

    <!-- Table Rekap LKH Cetak -->
    <div class="card p-0">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th width="40" class="text-center">No</th>
              <th>Tanggal &amp; Shift</th>
              <th>Mesin &amp; Operator</th>
              <th>Job Order (PP) &amp; Produk</th>
              <th>Target (Plano)</th>
              <th>Realisasi Druk (Plano)</th>
              <th>Hasil Baik (Plano)</th>
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
                <span class="badge badge-primary" style="font-size: 0.75rem;">{{ item.mesin }}</span>
                <div style="font-size: 0.82rem; font-weight: 600; margin-top: 2px;">{{ item.operator }}</div>
              </td>
              <td>
                <b class="text-primary">{{ item.no_pp }}</b>
                <div style="font-size: 0.8rem; color: #475569;">{{ item.produk_nama }}</div>
              </td>
              <td><b>{{ formatNumber(item.target_plano) }}</b> <small class="text-muted">plano</small></td>
              <td><b>{{ formatNumber(item.realisasi_druk) }}</b> <small class="text-muted">plano</small></td>
              <td><b class="text-success">{{ formatNumber(item.hasil_baik) }}</b> <small class="text-muted">plano</small></td>
              <td><b class="text-danger">{{ formatNumber(item.afval_plano) }}</b> <small class="text-muted">plano</small></td>
              <td>
                <span class="badge" :class="item.status === 'selesai' ? 'badge-success' : 'badge-warning'">
                  {{ item.status === 'selesai' ? 'Selesai Druk' : 'Running' }}
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
                Tidak ada data LKH Cetak yang sesuai filter.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { formatNumber } from '../../data/progressPP'
import { apiFetch } from '../../utils/api'
import { showAlert } from '../modal/modalService'

const filterShift = ref('')
const filterMesin = ref('')
const searchQuery = ref('')

const lkhCetakList = ref([])
const loading = ref(false)

const loadLkh = async () => {
  loading.value = true
  try {
    const res = await apiFetch('lkh.php?action=list&divisi=cetak')
    if (res && Array.isArray(res.data)) {
      lkhCetakList.value = res.data.map(item => ({
        id: item.id,
        tanggal: item.tanggal,
        shift: String(item.shift),
        mesin: item.mesin_nama || 'SM 23 (Heidelberg)',
        operator: item.operator || 'Tito & Slamet',
        no_pp: item.no_pp || 'PP-2026-0821',
        produk_nama: item.produk_nama || 'Etiket Rokok Djarum 12 Super',
        target_plano: Number(item.plan_shift || 50000),
        realisasi_druk: Number(item.total_proses || item.good || 0),
        hasil_baik: Number(item.good || 0),
        afval_plano: Number(item.reject || 0),
        cacat_dominan: item.problem || 'Scumming & Register Warna',
        downtime_mnt: Number(item.total_dt_menit || 45),
        status: 'selesai'
      }))
    }
  } catch (e) {
    console.error('Failed to load LKH Cetak from API:', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadLkh()
})

const filteredList = computed(() => {
  return lkhCetakList.value.filter(item => {
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
    `Detail LKH Cetak:\nNomor PP: ${item.no_pp}\nProduk: ${item.produk_nama}\nMesin: ${item.mesin}\nOperator: ${item.operator}\nDruk: ${formatNumber(item.realisasi_druk)} Plano\nHasil Baik: ${formatNumber(item.hasil_baik)} Plano\nAfval: ${formatNumber(item.afval_plano)} Plano (${item.cacat_dominan})\nDowntime: ${item.downtime_mnt} menit`,
    { title: 'Rincian LKH Cetak' }
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
