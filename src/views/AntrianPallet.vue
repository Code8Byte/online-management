<template>
  <div class="card">
    <h2 class="card-title">Antrian Pallet</h2>
    
    <div class="card" style="background: var(--bg);">
      <h3>Tambah Antrian Pallet</h3>
      <form @submit.prevent="saveAntrian">
        <div class="grid grid-1 gap-1">
          <div class="form-group">
            <label>Pilih Job Order (PP)</label>
            <select v-model="form.pp_id" class="form-control" required>
              <option value="">-- Pilih Job Order --</option>
              <option v-for="p in master.pp.filter(x => x.status === 'aktif')" :key="p.id" :value="p.id">{{ p.no_pp }} ({{ p.produk_nama }})</option>
            </select>
          </div>
          
        </div>

        <div class="card" style="margin-top: 1rem;">
          <h4>Daftar Pallet</h4>
          <div v-for="(p, i) in form.pallets" :key="i" class="grid grid-3 gap-1 align-center mb-1">
            <div class="form-group mb-0">
              <label>Nomor Pallet</label>
              <input type="text" v-model="p.nomor" class="form-control" required />
            </div>
            <div class="form-group mb-0">
              <label>Jumlah Pcs</label>
              <input type="number" v-model.number="p.qty" class="form-control" required min="1" />
            </div>
            <div style="padding-top: 1.5rem;">
              <button type="button" class="btn btn-danger" @click="form.pallets.splice(i,1)" v-if="form.pallets.length > 1">Hapus</button>
            </div>
          </div>
          <button type="button" class="btn btn-primary" @click="form.pallets.push({nomor:'', qty:0})">+ Tambah Pallet</button>
        </div>

        <div class="mt-2 text-right">
          <button type="submit" class="btn btn-success" :disabled="saving">Simpan ke Antrian</button>
        </div>
      </form>
    </div>

    <div class="card" style="margin-top: 1.5rem;">
      <h3 class="card-title">Daftar Antrian</h3>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Job Order (PP)</th>
              <th>Nomor Pallet</th>
              <th>Qty (Pcs)</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in antrian" :key="a.id">
              <td>{{ a.no_pp }} ({{ a.produk_nama }})</td>
              <td>{{ a.nomor_pallet }}</td>
              <td>{{ a.qty }}</td>
              <td>
                <button class="btn btn-danger btn-sm" @click="hapus(a.id)">Hapus</button>
              </td>
            </tr>
            <tr v-if="!antrian.length"><td colspan="4" class="text-center text-muted" style="padding: 1.5rem;">Belum ada antrian.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { apiFetch } from '../utils/api'

const pad = (n) => n.toString().padStart(2, '0')
const getLocalDate = () => {
  const d = new Date()
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

const master = ref({ pp: [], shift: [] })
const antrian = ref([])
const saving = ref(false)

const form = ref({
  pp_id: '',
  
  pallets: [{ nomor: '1', qty: 0 }]
})

const loadData = async () => {
  master.value.pp = await apiFetch('master.php?action=pp')
  master.value.shift = await apiFetch('master.php?action=shift_jadwal')
  antrian.value = await apiFetch('antrian.php?action=list')
}

onMounted(loadData)

const saveAntrian = async () => {
  saving.value = true
  try {
    await apiFetch('antrian.php?action=save', {
      method: 'POST',
      body: JSON.stringify(form.value)
    })
    alert('Berhasil ditambahkan ke antrian!')
    form.value.pallets = [{ nomor: '1', qty: 0 }]
    await loadData()
  } catch (e) {
    alert('Gagal: ' + e.message)
  }
  saving.value = false
}

const hapus = async (id) => {
  if (confirm('Hapus pallet ini dari antrian?')) {
    try {
      await apiFetch(`antrian.php?action=delete&id=${id}`, { method: 'DELETE' })
      await loadData()
    } catch (e) {
      alert('Gagal: ' + e.message)
    }
  }
}
</script>


