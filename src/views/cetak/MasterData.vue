<template>
  <div class="div-master">
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>⚙️</span> Master Data - Divisi Cetak
          </h2>
          <small class="text-muted">
            Konfigurasi master mesin cetak offset, standar kecepatan (JPH), jenis cacat cetak, dan daftar staf cetak
          </small>
        </div>
      </div>
    </div>

    <!-- 1. Master Mesin Cetak -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center mb-08">
        <h3 class="card-title" style="margin: 0; border: none; padding: 0;">🖨️ Master Mesin Cetak Offset</h3>
        <span class="badge badge-primary">Satuan: Lembar Plano</span>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Kode Mesin</th>
              <th>Nama Mesin Cetak</th>
              <th>Speed Standar (JPH)</th>
              <th>Kapasitas Harian (16 Jam)</th>
              <th>Status Operasional</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in masterMesinCetak" :key="m.id">
              <td><b>{{ m.id.toUpperCase() }}</b></td>
              <td><b>{{ m.nama }}</b></td>
              <td><span class="badge badge-info">{{ m.speed_jph.toLocaleString('id-ID') }} plano/jam</span></td>
              <td><b>{{ (m.speed_jph * 16).toLocaleString('id-ID') }}</b> plano/hari</td>
              <td><span class="badge badge-success">Siap Beroperasi</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 2. Master Cacat Cetak & Master Staf Cetak -->
    <div class="grid grid-2 gap-1">
      <!-- Cacat Cetak -->
      <div class="card">
        <h3 class="card-title mb-08" style="margin: 0; border: none; padding: 0;">⚠️ Standar Jenis Cacat Cetak Offset</h3>
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
              <tr v-for="c in cacatCetakList" :key="c.kode">
                <td><span class="badge badge-warning">{{ c.kode }}</span></td>
                <td><b>{{ c.nama }}</b></td>
                <td><small class="text-muted">{{ c.penyebab }}</small></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Staf & Operator Cetak -->
      <div class="card">
        <h3 class="card-title mb-08" style="margin: 0; border: none; padding: 0;">👥 Daftar Staf &amp; Operator Divisi Cetak</h3>
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
              <tr v-for="op in operatorCetakList" :key="op.user">
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

const masterMesinCetak = ref([
  { id: 'sm23', nama: 'SM 23 (Heidelberg Speedmaster 102)', speed_jph: 8500 },
  { id: 'sm25', nama: 'SM 25 (Heidelberg CD 102)', speed_jph: 9000 },
  { id: 'sm27', nama: 'SM 27 (Heidelberg SM)', speed_jph: 7500 },
  { id: 'kba106', nama: 'KBA Rapida 106 High Speed', speed_jph: 10000 },
  { id: 'sm10', nama: 'SM 10 (Heidelberg 2 Warna)', speed_jph: 6500 }
])

const cacatCetakList = ref([
  { kode: 'CTK-01', nama: 'Scumming (Tinta Bleber)', penyebab: 'Keseimbangan air dampening dan tinta tidak pas' },
  { kode: 'CTK-02', nama: 'Hickey (Bintik Tinta)', penyebab: 'Debu kertas atau kotoran pada blanket / plat' },
  { kode: 'CTK-03', nama: 'Misregister Warna', penyebab: 'Setelan gripper feeder atau register plat meleset' },
  { kode: 'CTK-04', nama: 'Set-off (Tinta Nempel Belakang)', penyebab: 'Anti-setoff powder kurang atau tumpukan terlalu berat' },
  { kode: 'CTK-05', nama: 'Varnish Belang', penyebab: 'Viskositas varnish atau tekanan roll anilox' }
])

const operatorCetakList = ref([
  { user: 'Tito', nama: 'Tito Pratama', role: 'Staff Cetak (SM 23)' },
  { user: 'Yoel', nama: 'Yoel Wijaya', role: 'Staff Cetak (SM 25)' },
  { user: 'Calvin', nama: 'Calvin Santoso', role: 'Staff Cetak (KBA Rapida)' },
  { user: 'Slamet', nama: 'Slamet Riyadi', role: 'Operator Cetak Senior' }
])

const loadMaster = async () => {
  try {
    const [mesinRes, cacatRes] = await Promise.all([
      apiFetch('master.php?action=mesin&divisi=cetak'),
      apiFetch('master.php?action=cacat')
    ])
    if (mesinRes && mesinRes.length > 0) {
      masterMesinCetak.value = mesinRes.map(m => ({
        id: String(m.id),
        nama: m.nama,
        speed_jph: Number(m.speed_jph || 8500)
      }))
    }
  } catch (e) {
    console.error('Failed to load Master Cetak from API:', e)
  }
}

onMounted(() => {
  loadMaster()
})
</script>

<style scoped>
.div-master {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
</style>
