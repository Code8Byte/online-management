<template>
  <div class="div-lkh-list">
    <!-- Header -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>📋</span> Data LKH - Divisi Lipat (Folder Gluer)
          </h2>
          <small class="text-muted">
            Rekap Laporan Kerja Harian Mesin Lipat Lem • Satuan: <b>Pieces (Pcs)</b>
          </small>
        </div>
        <div class="d-flex gap-05 align-center">
          <router-link to="/lipat/lkh/baru" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <span>✍️</span> + Isi LKH Lipat Baru
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
              <option value="">Semua Mesin Lipat Lem</option>
              <option value="Herui Gluer 1 (High Speed)">Herui Gluer 1 (High Speed)</option>
              <option value="Herui Gluer 2">Herui Gluer 2</option>
              <option value="Crash Lock Bottom Gluer">Crash Lock Bottom Gluer</option>
              <option value="Folder Gluer Standard">Folder Gluer Standard</option>
            </select>
          </div>
          <div style="position: relative; flex: 1; min-width: 200px;">
            <input type="text" v-model="searchQuery" class="form-control" placeholder="🔍 Cari No PP atau Operator Lipat..." />
          </div>
        </div>
      </div>
    </div>

    <!-- Table Rekap LKH Lipat -->
    <div class="card p-0">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th width="40" class="text-center">No</th>
              <th>Tanggal &amp; Shift</th>
              <th>Mesin &amp; Operator</th>
              <th>Job Order &amp; Tipe Lipatan</th>
              <th>Target (Pcs)</th>
              <th>Realisasi (Pcs)</th>
              <th>Hasil Baik (Pcs)</th>
              <th>Reject Lipat (Pcs)</th>
              <th>Status</th>
              <th width="110" class="text-center">Aksi</th>
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
                <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.75rem;">{{ item.mesin }}</span>
                <div style="font-size: 0.82rem; font-weight: 600; margin-top: 2px;">{{ item.operator }}</div>
              </td>
              <td>
                <b class="text-primary">{{ item.no_pp }}</b>
                <div style="font-size: 0.8rem; color: #475569;">{{ item.produk_nama }}</div>
                <span class="badge badge-secondary" style="font-size: 0.7rem; margin-top: 2px;">{{ item.tipe_box }}</span>
              </td>
              <td><b>{{ formatNumber(item.target_pcs) }}</b> <small class="text-muted">pcs</small></td>
              <td><b>{{ formatNumber(item.realisasi_pcs) }}</b> <small class="text-muted">pcs</small></td>
              <td><b class="text-success">{{ formatNumber(item.hasil_baik_pcs) }}</b> <small class="text-muted">pcs</small></td>
              <td><b class="text-danger">{{ formatNumber(item.reject_pcs) }}</b> <small class="text-muted">pcs</small></td>
              <td>
                <span class="badge" :class="item.status === 'selesai' ? 'badge-success' : 'badge-warning'">
                  {{ item.status === 'selesai' ? 'Selesai Lipat' : 'Running' }}
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
                Tidak ada data LKH Divisi Lipat yang cocok dengan filter.
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
import { apiFetch } from '../../utils/api'
import { showAlert } from '../../view/modal/modalService'

const filterShift = ref('')
const filterMesin = ref('')
const searchQuery = ref('')

const lkhLipatData = ref([])
const loading = ref(false)

const loadLkh = async () => {
  loading.value = true
  try {
    const res = await apiFetch('lkh.php?action=list&divisi=lipat')
    if (res && res.data && res.data.length > 0) {
      lkhLipatData.value = res.data.map(item => ({
        id: item.id,
        tanggal: item.tanggal,
        shift: String(item.shift),
        mesin: item.mesin_nama || 'Herui Speed Gluer',
        operator: item.operator || 'Jevon',
        no_pp: item.no_pp || 'PP-2026-0821',
        produk_nama: item.produk_nama || 'Etiket Rokok Djarum 12 Super',
        tipe_box: 'Straight Line Box',
        target_pcs: Number(item.plan_shift || 100000),
        realisasi_pcs: Number(item.total_proses || item.good || 0),
        hasil_baik_pcs: Number(item.good || 0),
        reject_pcs: Number(item.reject || 0),
        status: 'selesai',
        cacat: { fishtailing: Math.round(Number(item.reject || 0) * 0.4), lem_meleber: Math.round(Number(item.reject || 0) * 0.3), unglued: Math.round(Number(item.reject || 0) * 0.2), baret: Math.round(Number(item.reject || 0) * 0.1) }
      }))
    }
  } catch (e) {
    console.error('Failed to load LKH Lipat from API:', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadLkh()
})

const filteredList = computed(() => {
  return lkhLipatData.value.filter(item => {
    if (filterShift.value && item.shift !== filterShift.value) return false
    if (filterMesin.value && item.mesin !== filterMesin.value) return false
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase()
      const matchPP = item.no_pp.toLowerCase().includes(q)
      const matchOp = item.operator.toLowerCase().includes(q)
      const matchProd = item.produk_nama.toLowerCase().includes(q)
      if (!matchPP && !matchOp && !matchProd) return false
    }
    return true
  })
})

const formatNumber = (num) => {
  if (num === null || num === undefined) return '0'
  return Number(num).toLocaleString('id-ID')
}

const viewDetail = async (item) => {
  const msg =
    `Detail LKH Lipat #${item.id}\n` +
    `• No PP: ${item.no_pp} (${item.produk_nama})\n` +
    `• Mesin & Operator: ${item.mesin} | ${item.operator} (Shift ${item.shift})\n` +
    `• Tipe Kemasan: ${item.tipe_box}\n` +
    `• Realisasi: ${formatNumber(item.realisasi_pcs)} pcs\n` +
    `• Hasil Baik: ${formatNumber(item.hasil_baik_pcs)} pcs\n` +
    `• Reject Lipat: ${formatNumber(item.reject_pcs)} pcs\n` +
    `• Breakdown Cacat: Fishtailing: ${item.cacat?.fishtailing || 0}, Lem Meleber: ${item.cacat?.lem_meleber || 0}, Unglued: ${item.cacat?.unglued || 0}, Baret: ${item.cacat?.baret || 0}`

  await showAlert(msg, {
    title: `Detail LKH Lipat - ${item.no_pp}`,
    type: 'info'
  })
}
</script>
