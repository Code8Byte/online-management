<template>
  <div class="div-master">
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>⚙️</span> Master Data - Divisi Completing
          </h2>
          <small class="text-muted">
            Konfigurasi master mesin finishing (Hot Stamping, Varnish UV, Flute Laminating), standar kecepatan (JPH), dan staf completing
          </small>
        </div>
      </div>
    </div>

    <!-- 1. Master Mesin Completing -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center mb-08">
        <h3 class="card-title" style="margin: 0; border: none; padding: 0;">✨ Master Mesin Finishing &amp; Completing</h3>
        <span class="badge" style="background: #fef3c7; color: #b45309;">Satuan: Lembar Plano</span>
      </div>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Kode Mesin</th>
              <th>Nama Mesin Finishing</th>
              <th>Proses Utama</th>
              <th>Speed Standar (JPH)</th>
              <th>Kapasitas Harian (16 Jam)</th>
              <th>Status Operasional</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in masterMesinCompleting" :key="m.id">
              <td><b>{{ m.id.toUpperCase() }}</b></td>
              <td><b>{{ m.nama }}</b></td>
              <td><span class="badge badge-info">{{ m.proses }}</span></td>
              <td><b>{{ m.speed_jph.toLocaleString('id-ID') }}</b> <small class="text-muted">plano/jam</small></td>
              <td><b>{{ (m.speed_jph * 16).toLocaleString('id-ID') }}</b> <small class="text-muted">plano/hari</small></td>
              <td><span class="badge badge-success">Siap Beroperasi</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 2. Master Cacat Finishing & Master Staf Completing -->
    <div class="grid grid-2 gap-1">
      <!-- Cacat Finishing -->
      <div class="card">
        <h3 class="card-title mb-08" style="margin: 0; border: none; padding: 0;">⚠️ Standar Jenis Cacat Finishing</h3>
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
              <tr v-for="c in cacatCompletingList" :key="c.kode">
                <td><span class="badge badge-warning">{{ c.kode }}</span></td>
                <td><b>{{ c.nama }}</b></td>
                <td><small class="text-muted">{{ c.penyebab }}</small></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Staf & Operator Completing -->
      <div class="card">
        <h3 class="card-title mb-08" style="margin: 0; border: none; padding: 0;">👥 Daftar Staf Divisi Completing</h3>
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
              <tr v-for="op in operatorCompletingList" :key="op.user">
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

const masterMesinCompleting = ref([
  { id: 'hsf-01', nama: 'Hot Stamping Foil 1 & UV Gloss', proses: 'Hot Foil & Gloss', speed_jph: 3200 },
  { id: 'hsf-02', nama: 'Hot Stamping Foil 2', proses: 'Hot Stamping Foil', speed_jph: 2800 },
  { id: 'uv-01', nama: 'Varnish UV Gloss Obat', proses: 'Varnish UV Full/Coating', speed_jph: 4000 },
  { id: 'flute-01', nama: 'Laminasi Flute E/F', proses: 'Flute Laminating Box', speed_jph: 2500 },
  { id: 'doff-01', nama: 'Laminasi Doff & Spot UV', proses: 'Doff Film & Spot UV', speed_jph: 3000 }
])

const cacatCompletingList = ref([
  { kode: 'CPL-01', nama: 'Foil Peeling (Daya Rekat Rendah)', penyebab: 'Suhu pemanas klise kurang atau tekanan roll stamping rendah' },
  { kode: 'CPL-02', nama: 'Misregister Foil Stamping', penyebab: 'Setelan feeding miring atau klise tembaga bergeser' },
  { kode: 'CPL-03', nama: 'UV Pinholes / Bintik Melenting', penyebab: 'Kontaminasi debu atau viskositas cairan varnish terlalu pekat' },
  { kode: 'CPL-04', nama: 'Orange Peel Tekstur UV', penyebab: 'Penyinaran lampu UV berlebih atau rol aplikator kotor' },
  { kode: 'CPL-05', nama: 'Delaminasi Flute E/F', penyebab: 'Lem kanji/lem waterbased kurang rata atau drying time singkat' },
  { kode: 'CPL-06', nama: 'Warping / Lembar Melengkung', penyebab: 'Kadar air lem terlalu tinggi pada kertas medium gelombang' }
])

const operatorCompletingList = ref([
  { user: 'Nico', nama: 'Nico Prasetyo', role: 'Staff Completing' },
  { user: 'Sodok', nama: 'Sodok Sudarmanto', role: 'Staff Completing' },
  { user: 'Wahyudi', nama: 'Wahyudi Kusuma', role: 'Operator Finishing Lead' }
])
</script>
