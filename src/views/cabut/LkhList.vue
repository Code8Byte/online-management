<template>
  <div class="div-lkh-list">
    <!-- Header -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>📋</span> Data LKH - Divisi Cabut (Die-Cutting)
          </h2>
          <small class="text-muted">
            Rekap Pemotongan Pond / Die-Cut • <b>Konversi Otomatis Plano ke Pcs berdasarkan Up per Plano</b>
          </small>
        </div>
        <div class="d-flex gap-05 align-center">
          <router-link to="/cabut/lkh/baru" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <span>✍️</span> + Isi LKH Cabut Baru
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
              <option value="">Semua Mesin Pond/Cabut</option>
              <option value="Bobst Die Cutter MS 1">Bobst Die Cutter MS 1</option>
              <option value="Bobst Die Cutter MS 2">Bobst Die Cutter MS 2</option>
              <option value="Bobst Die Cutter MS 3">Bobst Die Cutter MS 3</option>
              <option value="Bobst Die Cutter MS 4">Bobst Die Cutter MS 4</option>
              <option value="Mesin Pond Manual">Mesin Pond Manual</option>
            </select>
          </div>
          <div style="position: relative; flex: 1; min-width: 200px;">
            <input type="text" v-model="searchQuery" class="form-control" placeholder="🔍 Cari No PP atau Operator Cabut..." />
          </div>
        </div>
      </div>
    </div>

    <!-- Table Rekap LKH Cabut -->
    <div class="card p-0">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th width="40" class="text-center">No</th>
              <th>Tanggal &amp; Shift</th>
              <th>Mesin &amp; Operator</th>
              <th>Job Order &amp; Up/Plano</th>
              <th>Realisasi Potong (Plano)</th>
              <th>Hasil Baik (Plano &rarr; Pcs)</th>
              <th>Reject / Afval (Plano &rarr; Pcs)</th>
              <th>Efisiensi Pcs</th>
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
                <span class="badge" style="background: #ffedd5; color: #9a3412; font-size: 0.75rem;">{{ item.mesin }}</span>
                <div style="font-size: 0.82rem; font-weight: 600; margin-top: 2px;">{{ item.operator }}</div>
              </td>
              <td>
                <b class="text-primary">{{ item.no_pp }}</b>
                <div style="font-size: 0.8rem; color: #475569;">{{ item.produk_nama }}</div>
                <span class="badge badge-info" style="font-size: 0.7rem; margin-top: 2px;">
                  Up: <b>{{ item.up_per_plano }} pcs/plano</b>
                </span>
              </td>
              <td>
                <b>{{ formatNumber(item.realisasi_potong_plano) }}</b>
                <div class="text-muted" style="font-size: 0.75rem;">lembar plano</div>
              </td>
              <td>
                <b class="text-success" style="font-size: 0.95rem;">
                  {{ formatNumber(item.hasil_baik_plano * item.up_per_plano) }} pcs
                </b>
                <div class="text-muted" style="font-size: 0.75rem;">
                  ({{ formatNumber(item.hasil_baik_plano) }} plano)
                </div>
              </td>
              <td>
                <b class="text-danger">
                  {{ formatNumber(item.reject_plano * item.up_per_plano) }} pcs
                </b>
                <div class="text-muted" style="font-size: 0.75rem;">
                  ({{ formatNumber(item.reject_plano) }} plano)
                </div>
              </td>
              <td>
                <span class="badge badge-success" style="font-weight: 700;">
                  {{ ((item.hasil_baik_plano / item.realisasi_potong_plano) * 100).toFixed(1) }}%
                </span>
              </td>
              <td>
                <span class="badge" :class="item.status === 'selesai' ? 'badge-success' : 'badge-warning'">
                  {{ item.status === 'selesai' ? 'Selesai Cabut' : 'Running' }}
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
                Tidak ada data LKH Divisi Cabut yang cocok dengan filter.
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
import { showAlert } from '../../view/modal/modalService'

const filterShift = ref('')
const filterMesin = ref('')
const searchQuery = ref('')

const lkhCabutData = ref([
  {
    id: 1,
    tanggal: '2026-11-01',
    shift: '1',
    mesin: 'Bobst Die Cutter MS 1',
    operator: 'Haryanto',
    no_pp: 'PP-2026-0840',
    produk_nama: 'Kemasan Herbal 150ml (Box Luar)',
    up_per_plano: 4,
    realisasi_potong_plano: 24300,
    hasil_baik_plano: 24000,
    reject_plano: 300,
    status: 'selesai',
    cacat: { pisau_tumpul: 120, creasing_pecah: 80, stripping_rusak: 100 }
  },
  {
    id: 2,
    tanggal: '2026-11-01',
    shift: '1',
    mesin: 'Bobst Die Cutter MS 2',
    operator: 'Kardi Sukoco',
    no_pp: 'PP-2026-0841',
    produk_nama: 'Box Ampul Farmasi 10ml',
    up_per_plano: 6,
    realisasi_potong_plano: 16800,
    hasil_baik_plano: 16650,
    reject_plano: 150,
    status: 'selesai',
    cacat: { pisau_tumpul: 50, creasing_pecah: 60, stripping_rusak: 40 }
  },
  {
    id: 3,
    tanggal: '2026-10-31',
    shift: '2',
    mesin: 'Bobst Die Cutter MS 3',
    operator: 'Haryanto',
    no_pp: 'PP-2026-0842',
    produk_nama: 'Kotak Makanan Ringan Ekspor',
    up_per_plano: 2,
    realisasi_potong_plano: 30000,
    hasil_baik_plano: 29700,
    reject_plano: 300,
    status: 'selesai',
    cacat: { pisau_tumpul: 140, creasing_pecah: 110, stripping_rusak: 50 }
  },
  {
    id: 4,
    tanggal: '2026-10-31',
    shift: '3',
    mesin: 'Bobst Die Cutter MS 1',
    operator: 'Kardi Sukoco',
    no_pp: 'PP-2026-0843',
    produk_nama: 'Karton Teh Premium 25s',
    up_per_plano: 4,
    realisasi_potong_plano: 21500,
    hasil_baik_plano: 21200,
    reject_plano: 300,
    status: 'selesai',
    cacat: { pisau_tumpul: 150, creasing_pecah: 90, stripping_rusak: 60 }
  }
])

const filteredList = computed(() => {
  return lkhCabutData.value.filter(item => {
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
  const goodPcs = item.hasil_baik_plano * item.up_per_plano
  const rejectPcs = item.reject_plano * item.up_per_plano
  const msg =
    `Detail LKH Cabut #${item.id}\n` +
    `• No PP: ${item.no_pp} (${item.produk_nama})\n` +
    `• Mesin & Operator: ${item.mesin} | ${item.operator} (Shift ${item.shift})\n` +
    `• Konfigurasi: 1 Plano = ${item.up_per_plano} Pcs\n` +
    `• Realisasi Potong: ${formatNumber(item.realisasi_potong_plano)} plano\n` +
    `• Hasil Baik: ${formatNumber(item.hasil_baik_plano)} plano ➔ ${formatNumber(goodPcs)} PCS\n` +
    `• Reject / Afval: ${formatNumber(item.reject_plano)} plano ➔ ${formatNumber(rejectPcs)} PCS\n` +
    `• Breakdown Cacat: Pisau Tumpul: ${item.cacat?.pisau_tumpul || 0}, Creasing Pecah: ${item.cacat?.creasing_pecah || 0}, Stripping Macet: ${item.cacat?.stripping_rusak || 0}`

  await showAlert(msg, {
    title: `Detail LKH Cabut - ${item.no_pp}`,
    type: 'info'
  })
}
</script>
