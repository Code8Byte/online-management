<template>
  <div class="div-lkh-form">
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>✍️</span> Form Input LKH - Divisi Cabut (Die-Cutting)
          </h2>
          <small class="text-muted">
            Pencatatan realisasi potong plano mesin die cutter Bobst &amp; konversi otomatis hasil baik dan reject ke satuan <b>Pcs</b>
          </small>
        </div>
        <router-link to="/cabut/lkh" class="btn btn-secondary btn-sm">
          <span>📋</span> Lihat Data LKH Cabut
        </router-link>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="card">
      <!-- Section 1: Identitas Shift & Mesin -->
      <h4 class="form-sec-title">1. Identitas Shift &amp; Mesin Die-Cut</h4>
      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Tanggal Produksi <span class="text-danger">*</span></label>
          <input type="date" v-model="form.tanggal" class="form-control" required style="cursor: pointer; background: #fff;" @click="$event.target.showPicker && $event.target.showPicker()" />
        </div>
        <div class="form-group">
          <label>Shift Kerja <span class="text-danger">*</span></label>
          <select v-model="form.shift" class="form-control" required>
            <option value="1">Shift 1 (Pagi: 07.00 - 15.00)</option>
            <option value="2">Shift 2 (Siang: 15.00 - 23.00)</option>
            <option value="3">Shift 3 (Malam: 23.00 - 07.00)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Pilih Mesin Die-Cutter <span class="text-danger">*</span></label>
          <select v-model="form.mesin" class="form-control" required>
            <option value="Bobst Die Cutter MS 1">Bobst Die Cutter MS 1</option>
            <option value="Bobst Die Cutter MS 2">Bobst Die Cutter MS 2</option>
            <option value="Bobst Die Cutter MS 3">Bobst Die Cutter MS 3</option>
            <option value="Bobst Die Cutter MS 4">Bobst Die Cutter MS 4</option>
            <option value="Mesin Pond Manual">Mesin Pond Manual</option>
          </select>
        </div>
      </div>

      <div class="grid grid-2 gap-1 mb-1">
        <div class="form-group">
          <label>Operator Utama (Staff Cabut) <span class="text-danger">*</span></label>
          <input type="text" v-model="form.operator" class="form-control" required placeholder="Contoh: Haryanto / Kardi Sukoco" />
        </div>
        <div class="form-group">
          <label>Helper / Asisten Cabut Pond</label>
          <input type="text" v-model="form.helper" class="form-control" placeholder="Nama helper mesin pond..." />
        </div>
      </div>

      <!-- Section 2: Job Order & Konfigurasi Up per Plano -->
      <h4 class="form-sec-title">2. Job Order &amp; Konversi Plano ke Pcs</h4>
      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Nomor Job Order (PP) <span class="text-danger">*</span></label>
          <input type="text" v-model="form.no_pp" class="form-control" required placeholder="Contoh: PP-2026-0840" />
        </div>
        <div class="form-group">
          <label>Nama Produk <span class="text-danger">*</span></label>
          <input type="text" v-model="form.produk_nama" class="form-control" required placeholder="Nama kemasan / box..." />
        </div>
        <div class="form-group">
          <label>1 Plano Isi Berapa (Up per Plano) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.up_per_plano" class="form-control" required min="1" placeholder="Contoh: 4" />
          <small class="text-muted">Konfigurasi pisau pond (pcs per lembar plano)</small>
        </div>
      </div>

      <!-- Section 3: Realisasi Potong Plano & Hasil Konversi Pcs -->
      <h4 class="form-sec-title">3. Realisasi Potong Plano &amp; Hasil Konversi ke Pcs</h4>
      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Realisasi Potong (Lembar Plano) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.realisasi_potong_plano" class="form-control" required min="0" placeholder="Contoh: 24300" />
        </div>
        <div class="form-group">
          <label>Hasil Baik (Lembar Plano) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.hasil_baik_plano" class="form-control" required min="0" placeholder="Contoh: 24000" />
        </div>
        <div class="form-group">
          <label>Reject / Afval Potong (Lembar Plano)</label>
          <input type="number" :value="rejectPlano" class="form-control" readonly style="background: #f1f5f9; font-weight: 700; color: #dc2626;" />
        </div>
      </div>

      <!-- Live Calculation Card: Konversi Pcs -->
      <div class="card mb-1" style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 1rem;">
        <div class="d-flex justify-between align-center mb-05">
          <span style="font-weight: 700; font-size: 0.9rem; color: #334155;">
            ⚡ Konversi Otomatis ke Satuan Pcs (Up: {{ form.up_per_plano || 1 }} pcs/plano)
          </span>
          <span class="badge badge-primary">Satuan Lanjut: Pcs (Sortir / Lipat)</span>
        </div>
        <div class="grid grid-2 gap-1">
          <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 0.8rem;">
            <div class="text-muted" style="font-size: 0.8rem; font-weight: 600;">HASIL BAIK CABUT (PCS)</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #059669; margin-top: 2px;">
              {{ (convertedGoodPcs).toLocaleString('id-ID') }} <span style="font-size: 0.85rem; font-weight: 500;">pcs</span>
            </div>
            <small class="text-muted">{{ (form.hasil_baik_plano || 0).toLocaleString('id-ID') }} plano &times; {{ form.up_per_plano || 1 }} up</small>
          </div>
          <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.8rem;">
            <div class="text-muted" style="font-size: 0.8rem; font-weight: 600;">REJECT / AFVAL CABUT (PCS)</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #dc2626; margin-top: 2px;">
              {{ (convertedRejectPcs).toLocaleString('id-ID') }} <span style="font-size: 0.85rem; font-weight: 500;">pcs</span>
            </div>
            <small class="text-muted">{{ (rejectPlano || 0).toLocaleString('id-ID') }} plano &times; {{ form.up_per_plano || 1 }} up</small>
          </div>
        </div>
      </div>

      <!-- Section 4: Klasifikasi Cacat Khas Die-Cutting -->
      <h4 class="form-sec-title">4. Klasifikasi Cacat Potong / Pond (Lembar Plano)</h4>
      <div class="grid grid-4 gap-08 mb-1">
        <div class="form-group">
          <label>Pisau Tumpul / Sobek</label>
          <input type="number" v-model.number="form.cacat.pisau_tumpul" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Creasing / Rel Pecah</label>
          <input type="number" v-model.number="form.cacat.creasing_pecah" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Nicks Gompal / Gompal Pisau</label>
          <input type="number" v-model.number="form.cacat.nicks_gompal" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Stripping Rusak / Macet</label>
          <input type="number" v-model.number="form.cacat.stripping_rusak" class="form-control" min="0" placeholder="0" />
        </div>
      </div>

      <div class="form-group mb-1">
        <label>Catatan Operasional / Kondisi Pisau Pond</label>
        <textarea v-model="form.catatan" class="form-control" rows="2" placeholder="Catatan pergantian pisau pon, penyesuaian bantalan karet ejection, atau tekanan die..."></textarea>
      </div>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-between align-center mt-1 pt-1" style="border-top: 1px solid #e2e8f0;">
        <router-link to="/cabut/lkh" class="btn btn-secondary">
          Batal
        </router-link>
        <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.8rem; font-weight: 700;">
          💾 Simpan LKH Cabut
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '../../utils/api'
import { showAlert } from '../../view/modal/modalService'

const router = useRouter()
const todayStr = new Date().toISOString().split('T')[0]

const form = ref({
  tanggal: todayStr,
  shift: '1',
  mesin: 'Bobst Die Cutter MS 1',
  operator: 'Haryanto',
  helper: '',
  no_pp: 'PP-2026-0821',
  produk_nama: 'Etiket Rokok Djarum 12 Super',
  up_per_plano: 4,
  realisasi_potong_plano: 40000,
  hasil_baik_plano: 39250,
  cacat: {
    pisau_tumpul: 300,
    creasing_pecah: 200,
    nicks_gompal: 150,
    stripping_rusak: 100
  },
  catatan: 'Pisau pond tajam, stripping mulus'
})

const rejectPlano = computed(() => {
  if (form.value.realisasi_potong_plano === null || form.value.hasil_baik_plano === null) return 0
  return Math.max(0, form.value.realisasi_potong_plano - form.value.hasil_baik_plano)
})

const convertedGoodPcs = computed(() => {
  const up = form.value.up_per_plano || 1
  return (form.value.hasil_baik_plano || 0) * up
})

const convertedRejectPcs = computed(() => {
  const up = form.value.up_per_plano || 1
  return rejectPlano.value * up
})

const handleSubmit = async () => {
  if (form.value.hasil_baik_plano > form.value.realisasi_potong_plano) {
    await showAlert('Jumlah hasil baik plano tidak boleh lebih besar dari realisasi potong!', {
      title: 'Validasi Gagal',
      type: 'warning'
    })
    return
  }

  try {
    const payload = {
      divisi: 'cabut',
      tanggal: form.value.tanggal,
      shift: form.value.shift,
      mesin_id: 11,
      operator: form.value.operator + (form.value.helper ? ` & ${form.value.helper}` : ''),
      start: `${form.value.tanggal} 08:00:00`,
      finish: `${form.value.tanggal} 16:00:00`,
      setting_menit: 30,
      '5s_menit': 15,
      lembur_menit: 0,
      keterangan_lembur: '',
      problem: form.value.catatan || Object.entries(form.value.cacat).filter(([_, v]) => v > 0).map(([k, v]) => `${k}: ${v}`).join(', '),
      pp_items: [{
        no_pp: form.value.no_pp,
        produk_nama: form.value.produk_nama,
        tahap_sortir: 'CBT',
        up_per_plano: form.value.up_per_plano,
        good: form.value.hasil_baik_plano,
        reject: rejectPlano.value,
        setup_reject: 100,
        total_layar: form.value.realisasi_potong_plano,
        total_proses: form.value.realisasi_potong_plano,
        plan_shift: 44200,
        waktu_prod_jam: 8,
        setting_menit: 30,
        '5s_menit': 15,
        total_dt_menit: 45,
        jam_jalan: 7.25,
        downtime: [],
        cacat: []
      }]
    }

    await apiFetch('lkh.php?action=create', {
      method: 'POST',
      body: JSON.stringify(payload)
    })

    const msg =
      `LKH Cabut untuk No PP ${form.value.no_pp || '-'} berhasil disimpan ke database.\n` +
      `• Potong Plano: ${form.value.realisasi_potong_plano?.toLocaleString('id-ID')} plano\n` +
      `• Up per Plano: ${form.value.up_per_plano} pcs/plano\n` +
      `• Hasil Baik Terkonversi: ${convertedGoodPcs.value.toLocaleString('id-ID')} PCS\n` +
      `• Reject Terkonversi: ${convertedRejectPcs.value.toLocaleString('id-ID')} PCS`

    await showAlert(msg, {
      title: 'LKH Cabut Berhasil Disimpan',
      type: 'success'
    })

    router.push('/cabut/lkh')
  } catch (e) {
    showAlert('Gagal menyimpan LKH Cabut: ' + (e.message || e))
  }
}
</script>

<style scoped>
.form-sec-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  margin: 1rem 0 0.6rem;
  padding-bottom: 4px;
  border-bottom: 2px solid #e2e8f0;
}
.form-sec-title:first-of-type {
  margin-top: 0;
}
</style>
