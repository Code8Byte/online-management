<template>
  <div class="div-lkh-list">
    <!-- Header -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>📋</span> Data LKH - PPIC (Evaluasi Produksi Lintas Divisi)
          </h2>
          <small class="text-muted">
            Monitoring rekonsiliasi Laporan Kerja Harian seluruh lini produksi: Cetak &bull; Completing &bull; Cabut &bull; Sortir &bull; Lipat
          </small>
        </div>
        <div class="d-flex gap-05 align-center">
          <router-link to="/ppic/lkh/baru" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <span>✍️</span> + Rilis Target Harian LKH
          </router-link>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-1 p-08">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div class="d-flex gap-08 align-center flex-wrap" style="flex: 1;">
          <div style="min-width: 160px;">
            <select v-model="filterDivisi" class="form-control">
              <option value="">Semua Divisi</option>
              <option value="cetak">Divisi Cetak</option>
              <option value="completing">Divisi Completing</option>
              <option value="cabut">Divisi Cabut</option>
              <option value="sortir">Divisi Sortir</option>
              <option value="lipat">Divisi Lipat</option>
            </select>
          </div>
          <div style="min-width: 140px;">
            <select v-model="filterShift" class="form-control">
              <option value="">Semua Shift</option>
              <option value="1">Shift 1</option>
              <option value="2">Shift 2</option>
              <option value="3">Shift 3</option>
            </select>
          </div>
          <div style="position: relative; flex: 1; min-width: 220px;">
            <input type="text" v-model="searchQuery" class="form-control" placeholder="🔍 Cari No PP, Mesin, atau Operator..." />
          </div>
        </div>
      </div>
    </div>

    <!-- Table Rekap LKH PPIC -->
    <div class="card p-0">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th width="40" class="text-center">No</th>
              <th>Tanggal &amp; Shift</th>
              <th>Divisi &amp; Mesin</th>
              <th>Job Order &amp; Produk</th>
              <th>Satuan</th>
              <th>Target PPIC</th>
              <th>Realisasi</th>
              <th>Hasil Baik</th>
              <th>Afval / Reject</th>
              <th>Pencapaian</th>
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
                <span class="badge" :class="getDivisiBadgeClass(item.divisi)" style="font-size: 0.72rem; text-transform: uppercase;">
                  {{ item.divisi }}
                </span>
                <div style="font-size: 0.82rem; font-weight: 600; margin-top: 2px;">{{ item.mesin }}</div>
                <small class="text-muted">Op: {{ item.operator }}</small>
              </td>
              <td>
                <b class="text-primary">{{ item.no_pp }}</b>
                <div style="font-size: 0.8rem; color: #475569;">{{ item.produk_nama }}</div>
              </td>
              <td>
                <span class="badge" :style="item.satuan === 'Plano' ? 'background: #eff6ff; color: #1e40af;' : 'background: #fdf2f8; color: #9d174d;'">
                  {{ item.satuan }}
                </span>
              </td>
              <td><b>{{ formatNumber(item.target) }}</b></td>
              <td><b>{{ formatNumber(item.realisasi) }}</b></td>
              <td><b class="text-success">{{ formatNumber(item.hasil_baik) }}</b></td>
              <td><b class="text-danger">{{ formatNumber(item.afval) }}</b></td>
              <td>
                <span class="badge" :class="item.hasil_baik >= item.target ? 'badge-success' : 'badge-warning'" style="font-weight: 700;">
                  {{ ((item.hasil_baik / item.target) * 100).toFixed(1) }}%
                </span>
              </td>
              <td class="text-center text-nowrap">
                <button class="btn btn-secondary btn-sm" @click="viewDetail(item)" style="padding: 0.25rem 0.6rem;">
                  👁️ Detail
                </button>
              </td>
            </tr>
            <tr v-if="filteredList.length === 0">
              <td colspan="11" class="text-center text-muted" style="padding: 2rem;">
                Tidak ada data LKH yang cocok dengan filter pencarian.
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

const filterDivisi = ref('')
const filterShift = ref('')
const searchQuery = ref('')

const lkhPpicData = ref([
  {
    id: 1,
    divisi: 'cetak',
    tanggal: '2026-11-01',
    shift: '1',
    mesin: 'SM 23 (Heidelberg)',
    operator: 'Tito',
    no_pp: 'PP-2026-0840',
    produk_nama: 'Kemasan Herbal 150ml',
    satuan: 'Plano',
    target: 50000,
    realisasi: 49500,
    hasil_baik: 48500,
    afval: 1000,
    status: 'selesai'
  },
  {
    id: 2,
    divisi: 'completing',
    tanggal: '2026-11-01',
    shift: '1',
    mesin: 'Hot Stamping Foil 1',
    operator: 'Nico',
    no_pp: 'PP-2026-0840',
    produk_nama: 'Kemasan Herbal 150ml',
    satuan: 'Plano',
    target: 48500,
    realisasi: 48500,
    hasil_baik: 47900,
    afval: 600,
    status: 'selesai'
  },
  {
    id: 3,
    divisi: 'cabut',
    tanggal: '2026-11-01',
    shift: '1',
    mesin: 'Bobst Die Cutter MS 1',
    operator: 'Haryanto',
    no_pp: 'PP-2026-0840',
    produk_nama: 'Kemasan Herbal 150ml',
    satuan: 'Plano',
    target: 24300,
    realisasi: 24300,
    hasil_baik: 24000,
    afval: 300,
    status: 'selesai'
  },
  {
    id: 4,
    divisi: 'sortir',
    tanggal: '2026-11-01',
    shift: '1',
    mesin: 'Meja Manual 1',
    operator: 'Reno',
    no_pp: 'PP-2026-0840',
    produk_nama: 'Kemasan Herbal 150ml',
    satuan: 'Pcs',
    target: 96000,
    realisasi: 96000,
    hasil_baik: 95400,
    afval: 600,
    status: 'selesai'
  },
  {
    id: 5,
    divisi: 'lipat',
    tanggal: '2026-11-01',
    shift: '1',
    mesin: 'Herui Gluer 1',
    operator: 'Jevon',
    no_pp: 'PP-2026-0840',
    produk_nama: 'Kemasan Herbal 150ml',
    satuan: 'Pcs',
    target: 95400,
    realisasi: 95400,
    hasil_baik: 95100,
    afval: 300,
    status: 'selesai'
  }
])

const filteredList = computed(() => {
  return lkhPpicData.value.filter(item => {
    if (filterDivisi.value && item.divisi !== filterDivisi.value) return false
    if (filterShift.value && item.shift !== filterShift.value) return false
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase()
      const matchPP = item.no_pp.toLowerCase().includes(q)
      const matchOp = item.operator.toLowerCase().includes(q)
      const matchMesin = item.mesin.toLowerCase().includes(q)
      const matchProd = item.produk_nama.toLowerCase().includes(q)
      if (!matchPP && !matchOp && !matchMesin && !matchProd) return false
    }
    return true
  })
})

const getDivisiBadgeClass = (div) => {
  if (div === 'cetak') return 'badge-primary'
  if (div === 'completing') return 'badge-warning'
  if (div === 'cabut') return 'badge-info'
  if (div === 'sortir') return 'badge-success'
  if (div === 'lipat') return 'badge-secondary'
  return 'badge-primary'
}

const formatNumber = (num) => {
  if (num === null || num === undefined) return '0'
  return Number(num).toLocaleString('id-ID')
}

const viewDetail = async (item) => {
  const msg =
    `Evaluasi LKH PPIC #${item.id}\n` +
    `• Divisi: ${item.divisi.toUpperCase()} | Mesin: ${item.mesin}\n` +
    `• Job Order: ${item.no_pp} (${item.produk_nama})\n` +
    `• Operator: ${item.operator} (Shift ${item.shift}, ${item.tanggal})\n` +
    `• Satuan Ukur: ${item.satuan}\n` +
    `• Target PPIC: ${formatNumber(item.target)} ${item.satuan}\n` +
    `• Realisasi: ${formatNumber(item.realisasi)} ${item.satuan}\n` +
    `• Hasil Baik: ${formatNumber(item.hasil_baik)} ${item.satuan} (${((item.hasil_baik/item.target)*100).toFixed(1)}%)\n` +
    `• Afval / Reject: ${formatNumber(item.afval)} ${item.satuan}`

  await showAlert(msg, {
    title: `Evaluasi LKH PPIC - ${item.no_pp}`,
    type: 'info'
  })
}
</script>
