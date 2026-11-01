<template>
  <div class="div-master">
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>⚙️</span> Master Data - Divisi Cabut (Die-Cutting)
          </h2>
          <small class="text-muted">
            Konfigurasi master mesin die-cut Bobst, standar kecepatan potong (JPH), jenis cacat pond, dan staf operator cabut
          </small>
        </div>
      </div>
    </div>

    <!-- 1. Master Mesin Cabut / Pond -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center mb-08">
        <h3 class="card-title" style="margin: 0; border: none; padding: 0;">✂️ Master Mesin Pond / Die-Cutting Bobst</h3>
        <span class="badge" style="background: #ffedd5; color: #9a3412;">Satuan Input: Plano &bull; Output: Pcs</span>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Kode Mesin</th>
              <th>Nama Mesin Pond</th>
              <th>Tipe Mesin</th>
              <th>Speed Standar (JPH)</th>
              <th>Kapasitas Harian (16 Jam)</th>
              <th>Status Operasional</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in masterMesinCabut" :key="m.id">
              <td><b>{{ m.id.toUpperCase() }}</b></td>
              <td><b>{{ m.nama }}</b></td>
              <td><span class="badge badge-info">{{ m.tipe }}</span></td>
              <td><b>{{ m.speed_jph.toLocaleString('id-ID') }}</b> <small class="text-muted">plano/jam</small></td>
              <td><b>{{ (m.speed_jph * 16).toLocaleString('id-ID') }}</b> <small class="text-muted">plano/hari</small></td>
              <td><span class="badge badge-success">Siap Beroperasi</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 2. Master Cacat Pond & Master Staf Cabut -->
    <div class="grid grid-2 gap-1">
      <!-- Cacat Cabut -->
      <div class="card">
        <h3 class="card-title mb-08" style="margin: 0; border: none; padding: 0;">⚠️ Standar Jenis Cacat Potong Pond</h3>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Kode</th>
                <th>Nama Cacat</th>
                <th>Penyebab Utama</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in cacatCabutList" :key="c.kode">
                <td><span class="badge badge-warning">{{ c.kode }}</span></td>
                <td><b>{{ c.nama }}</b></td>
                <td><small class="text-muted">{{ c.penyebab }}</small></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Staf & Operator Cabut -->
      <div class="card">
        <h3 class="card-title mb-08" style="margin: 0; border: none; padding: 0;">👥 Daftar Staf Divisi Cabut</h3>
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
              <tr v-for="op in operatorCabutList" :key="op.user">
                <td><b class="text-primary">{{ op.user }}</b></td>
                <td><b>{{ op.nama }}</b></td>
                <td><span class="badge badge-primary">{{ op.role }}</span></td>
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
import { ref, onMounted } from 'vue'
import { apiFetch } from '../../utils/api'

const masterMesinCabut = ref([
  { id: 'ms1', nama: 'Bobst MS 1 (Die Plat 4 Up)', tipe: 'Automatic Die Plat 4 Up', speed_jph: 6000 },
  { id: 'ms2', nama: 'Bobst MS 2 Presisi', tipe: 'Automatic Presisi', speed_jph: 6500 },
  { id: 'ms3', nama: 'Bobst MS 3 Double Crease', tipe: 'Double Crease Platen', speed_jph: 5500 },
  { id: 'ms4', nama: 'Bobst MS 4 High Speed', tipe: 'High Speed Pond', speed_jph: 5500 }
])

const cacatCabutList = ref([
  { kode: 'PND-01', nama: 'Pisau Tumpul / Potongan Berserabut', penyebab: 'Jumlah pukulan pisau pond telah melewati limit ketajaman' },
  { kode: 'PND-02', nama: 'Creasing / Garis Rel Pecah', penyebab: 'Matrik rel terlalu sempit atau tekanan platen terlalu tinggi' },
  { kode: 'PND-03', nama: 'Nicks Terlalu Lebar / Kasar', penyebab: 'Gerinda coakan nicks terlalu dalam sehingga box sulit lepas' },
  { kode: 'PND-04', nama: 'Misregister Pisau ke Hasil Cetak', penyebab: 'Setelan register front lays dan side guide bergeser' },
  { kode: 'PND-05', nama: 'Stripping Rusak / Nyangkut', penyebab: 'Pin stripping atas/bawah tidak presisi dengan lembar buangan' }
])

const operatorCabutList = ref([
  { user: 'Haryanto', nama: 'Haryanto Subagyo', role: 'Staff Cabut' },
  { user: 'Kardi', nama: 'Kardi Sukoco', role: 'Staff Cabut' },
  { user: 'Supriyadi', nama: 'Supriyadi', role: 'Operator Lead Bobst' }
])

const loadMaster = async () => {
  try {
    const res = await apiFetch('master.php?action=mesin&divisi=cabut')
    if (res && res.length > 0) {
      masterMesinCabut.value = res.map(m => ({
        id: String(m.id),
        nama: m.nama,
        tipe: 'Pond & Die-Cutting',
        speed_jph: Number(m.speed_jph || 6000)
      }))
    }
  } catch (e) {
    console.error('Failed to load Master Cabut from API:', e)
  }
}

onMounted(() => {
  loadMaster()
})
</script>
