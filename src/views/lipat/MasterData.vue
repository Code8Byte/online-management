<template>
  <div class="div-master">
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>⚙️</span> Master Data - Divisi Lipat (Folder Gluer)
          </h2>
          <small class="text-muted">
            Konfigurasi master mesin folder gluer, kecepatan standar lembar/jam (Pcs), jenis cacat pengeleman, dan staf lipat
          </small>
        </div>
      </div>
    </div>

    <!-- 1. Master Mesin Folder Gluer -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center mb-08">
        <h3 class="card-title" style="margin: 0; border: none; padding: 0;">📦 Master Mesin Lipat &amp; Gluer</h3>
        <span class="badge" style="background: #e0f2fe; color: #0369a1;">Satuan: Pieces (Pcs/Box)</span>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Kode Mesin</th>
              <th>Nama Mesin Gluer</th>
              <th>Tipe Kemasan</th>
              <th>Speed Standar (Pcs/Jam)</th>
              <th>Kapasitas Harian (16 Jam)</th>
              <th>Status Operasional</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in masterMesinLipat" :key="m.id">
              <td><b>{{ m.id.toUpperCase() }}</b></td>
              <td><b>{{ m.nama }}</b></td>
              <td><span class="badge badge-info">{{ m.tipe }}</span></td>
              <td><b>{{ m.speed_jph.toLocaleString('id-ID') }}</b> <small class="text-muted">pcs/jam</small></td>
              <td><b>{{ (m.speed_jph * 16).toLocaleString('id-ID') }}</b> <small class="text-muted">pcs/hari</small></td>
              <td><span class="badge badge-success">Siap Beroperasi</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 2. Master Cacat Lipat & Master Staf Lipat -->
    <div class="grid grid-2 gap-1">
      <!-- Cacat Lipat -->
      <div class="card">
        <h3 class="card-title mb-08" style="margin: 0; border: none; padding: 0;">⚠️ Standar Jenis Cacat Pelipatan Lem</h3>
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
              <tr v-for="c in cacatLipatList" :key="c.kode">
                <td><span class="badge badge-warning">{{ c.kode }}</span></td>
                <td><b>{{ c.nama }}</b></td>
                <td><small class="text-muted">{{ c.penyebab }}</small></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Staf & Operator Lipat -->
      <div class="card">
        <h3 class="card-title mb-08" style="margin: 0; border: none; padding: 0;">👥 Daftar Staf Divisi Lipat</h3>
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
              <tr v-for="op in operatorLipatList" :key="op.user">
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
import { ref } from 'vue'

const masterMesinLipat = ref([
  { id: 'gluer-01', nama: 'Herui Gluer 1 (High Speed)', tipe: 'Straight Line & Auto Bottom', speed_jph: 18000 },
  { id: 'gluer-02', nama: 'Herui Gluer 2', tipe: 'Straight Line Box', speed_jph: 15000 },
  { id: 'gluer-03', nama: 'Crash Lock Bottom Gluer', tipe: 'Lock Bottom Carton', speed_jph: 12000 },
  { id: 'gluer-04', nama: 'Folder Gluer Standard', tipe: 'Side Seam & Small Flap', speed_jph: 10000 }
])

const cacatLipatList = ref([
  { kode: 'LPT-01', nama: 'Fishtailing (Lipatan Miring)', penyebab: 'Tekanan belt penarik kiri-kanan tidak seimbang atau feeding miring' },
  { kode: 'LPT-02', nama: 'Lem Meluber (Squeezing)', penyebab: 'Volume semprotan glue gun berlebih atau tekanan press belt terlalu tinggi' },
  { kode: 'LPT-03', nama: 'Lem Terbuka / Unglued', penyebab: 'Viskositas lem kurang encer atau lembar laminasi tidak tergores plasma' },
  { kode: 'LPT-04', nama: 'Baret Belt / Goresan Roller', penyebab: 'Roller penekan kotor terkena kerak lem kering' },
  { kode: 'LPT-05', nama: 'Flap Melipat Terbalik', penyebab: 'Sword guide penekuk flap patah atau posisi guide tidak pas' }
])

const operatorLipatList = ref([
  { user: 'Jevon', nama: 'Jevon Kristianto', role: 'Staff Lipat' },
  { user: 'Sugeng', nama: 'Sugeng Riyadi', role: 'Staff Lipat' },
  { user: 'Bambang', nama: 'Bambang Hariyadi', role: 'Operator Folder Gluer' }
])
</script>
