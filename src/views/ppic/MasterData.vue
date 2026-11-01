<template>
  <div class="div-master">
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>⚙️</span> Master Data - PPIC (Standar Kapasitas Pabrik)
          </h2>
          <small class="text-muted">
            Standar kecepatan mesin (JPH), kapasitas harian 16 jam kerja, allowance insheet, dan parameter penjadwalan terpadu
          </small>
        </div>
      </div>
    </div>

    <!-- Filter Tab Kategori Mesin -->
    <div class="card mb-1 p-08">
      <div class="d-flex gap-05 align-center flex-wrap">
        <button
          type="button"
          class="btn btn-sm"
          :class="activeCat === 'all' ? 'btn-primary' : 'btn-secondary'"
          @click="activeCat = 'all'"
        >
          Semua Mesin ({{ allMachines.length }})
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="activeCat === 'cetak' ? 'btn-primary' : 'btn-secondary'"
          @click="activeCat = 'cetak'"
        >
          🖨️ Cetak Offset
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="activeCat === 'completing' ? 'btn-primary' : 'btn-secondary'"
          @click="activeCat = 'completing'"
        >
          ✨ Completing
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="activeCat === 'cabut' ? 'btn-primary' : 'btn-secondary'"
          @click="activeCat = 'cabut'"
        >
          ✂️ Cabut / Pond
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="activeCat === 'sortir' ? 'btn-primary' : 'btn-secondary'"
          @click="activeCat = 'sortir'"
        >
          🔍 Sortir
        </button>
        <button
          type="button"
          class="btn btn-sm"
          :class="activeCat === 'lipat' ? 'btn-primary' : 'btn-secondary'"
          @click="activeCat = 'lipat'"
        >
          📦 Lipat Gluer
        </button>
      </div>
    </div>

    <!-- 1. Master Mesin Pabrik Terpadu -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center mb-08">
        <h3 class="card-title" style="margin: 0; border: none; padding: 0;">🏭 Standar Kapasitas Mesin PT Pura Barutama</h3>
        <span class="badge badge-info">Basis Standar: 16 Jam Operasi Efektif / Hari</span>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Kode</th>
              <th>Divisi</th>
              <th>Nama Mesin / Lini</th>
              <th>Satuan Ukur</th>
              <th>Speed Standar (JPH)</th>
              <th>Kapasitas Harian (16 Jam)</th>
              <th>Status Mesin</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in filteredMachines" :key="m.id">
              <td><b>{{ m.id.toUpperCase() }}</b></td>
              <td>
                <span class="badge" :class="getBadgeClass(m.divisi)" style="text-transform: uppercase;">
                  {{ m.divisi }}
                </span>
              </td>
              <td><b>{{ m.nama }}</b></td>
              <td>
                <span class="badge" :style="m.satuan === 'Plano' ? 'background: #eff6ff; color: #1e40af;' : 'background: #fdf2f8; color: #9d174d;'">
                  {{ m.satuan }}
                </span>
              </td>
              <td><b>{{ m.speed_jph.toLocaleString('id-ID') }}</b> <small class="text-muted">{{ m.satuan.toLowerCase() }}/jam</small></td>
              <td><b>{{ (m.speed_jph * 16).toLocaleString('id-ID') }}</b> <small class="text-muted">{{ m.satuan.toLowerCase() }}/hari</small></td>
              <td><span class="badge badge-success">Operasional</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 2. Parameter Penjadwalan & Staf PPIC -->
    <div class="grid grid-2 gap-1">
      <div class="card">
        <h3 class="card-title mb-08" style="margin: 0; border: none; padding: 0;">📐 Standar Allowance Insheet PPIC</h3>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Proses Produksi</th>
                <th>Toleransi Standard</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><b>Cetak Offset 4-6 Warna</b></td>
                <td><span class="badge badge-warning">2.0% - 2.5%</span></td>
                <td><small class="text-muted">Untuk insheet pas register &amp; color matching</small></td>
              </tr>
              <tr>
                <td><b>Hot Stamping Foil &amp; UV</b></td>
                <td><span class="badge badge-warning">1.0% - 1.5%</span></td>
                <td><small class="text-muted">Untuk trial panas klise &amp; tensioning foil</small></td>
              </tr>
              <tr>
                <td><b>Die-Cutting Cabut (Pond)</b></td>
                <td><span class="badge badge-warning">1.0% - 1.5%</span></td>
                <td><small class="text-muted">Untuk pas pisau pond, register &amp; penarikan lembar</small></td>
              </tr>
              <tr>
                <td><b>Sortir Manual / Mesin</b></td>
                <td><span class="badge badge-warning">0.5% - 1.0%</span></td>
                <td><small class="text-muted">Inspeksi optical reject &amp; handling sample</small></td>
              </tr>
              <tr>
                <td><b>Folder Gluer (Lipat Lem)</b></td>
                <td><span class="badge badge-warning">0.5% - 1.0%</span></td>
                <td><small class="text-muted">Untuk seting nozzle lem &amp; penyesuaian guide</small></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h3 class="card-title mb-08" style="margin: 0; border: none; padding: 0;">👥 Staf PPIC &amp; Tim Perencanaan</h3>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Username</th>
                <th>Nama Lengkap</th>
                <th>Peran</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><b class="text-primary">admin</b></td>
                <td><b>Admin PPIC &amp; Master Perencana</b></td>
                <td><span class="badge badge-primary">Staff PPIC</span></td>
                <td><span class="badge badge-success">Aktif</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const activeCat = ref('all')

const allMachines = ref([
  // Cetak
  { id: 'sm-23', divisi: 'cetak', nama: 'SM 23 (Heidelberg)', satuan: 'Plano', speed_jph: 6000 },
  { id: 'sm-25', divisi: 'cetak', nama: 'SM 25 (Heidelberg)', satuan: 'Plano', speed_jph: 6000 },
  { id: 'sm-27', divisi: 'cetak', nama: 'SM 27 (Heidelberg)', satuan: 'Plano', speed_jph: 6500 },
  { id: 'kba-106', divisi: 'cetak', nama: 'KBA Rapida 106', satuan: 'Plano', speed_jph: 8000 },
  { id: 'sm-10', divisi: 'cetak', nama: 'SM 10 (Heidelberg 2 Warna)', satuan: 'Plano', speed_jph: 4500 },
  // Completing
  { id: 'hsf-01', divisi: 'completing', nama: 'Hot Stamping Foil 1 & UV Gloss', satuan: 'Plano', speed_jph: 3200 },
  { id: 'hsf-02', divisi: 'completing', nama: 'Hot Stamping Foil 2', satuan: 'Plano', speed_jph: 2800 },
  { id: 'uv-01', divisi: 'completing', nama: 'Varnish UV Gloss Obat', satuan: 'Plano', speed_jph: 4000 },
  { id: 'flute-01', divisi: 'completing', nama: 'Laminasi Flute E/F', satuan: 'Plano', speed_jph: 2500 },
  // Cabut
  { id: 'ms-01', divisi: 'cabut', nama: 'Bobst Die Cutter MS 1', satuan: 'Plano', speed_jph: 5500 },
  { id: 'ms-02', divisi: 'cabut', nama: 'Bobst Die Cutter MS 2', satuan: 'Plano', speed_jph: 5000 },
  { id: 'ms-03', divisi: 'cabut', nama: 'Bobst Die Cutter MS 3', satuan: 'Plano', speed_jph: 4800 },
  { id: 'ms-04', divisi: 'cabut', nama: 'Bobst Die Cutter MS 4', satuan: 'Plano', speed_jph: 4500 },
  // Sortir
  { id: 'foc-01', divisi: 'sortir', nama: 'Focusight Optical Inspection', satuan: 'Pcs', speed_jph: 25000 },
  { id: 'srt-m1', divisi: 'sortir', nama: 'Meja Sortir Manual 1', satuan: 'Pcs', speed_jph: 8000 },
  { id: 'srt-m2', divisi: 'sortir', nama: 'Meja Sortir Manual 2', satuan: 'Pcs', speed_jph: 8000 },
  // Lipat
  { id: 'gluer-01', divisi: 'lipat', nama: 'Herui Gluer 1 (High Speed)', satuan: 'Pcs', speed_jph: 18000 },
  { id: 'gluer-02', divisi: 'lipat', nama: 'Herui Gluer 2', satuan: 'Pcs', speed_jph: 15000 },
  { id: 'gluer-03', divisi: 'lipat', nama: 'Crash Lock Bottom Gluer', satuan: 'Pcs', speed_jph: 12000 }
])

const filteredMachines = computed(() => {
  if (activeCat.value === 'all') return allMachines.value
  return allMachines.value.filter(m => m.divisi === activeCat.value)
})

const getBadgeClass = (div) => {
  if (div === 'cetak') return 'badge-primary'
  if (div === 'completing') return 'badge-warning'
  if (div === 'cabut') return 'badge-info'
  if (div === 'sortir') return 'badge-success'
  if (div === 'lipat') return 'badge-secondary'
  return 'badge-primary'
}
</script>
