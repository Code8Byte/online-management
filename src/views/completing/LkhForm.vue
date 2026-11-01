<template>
  <div class="div-lkh-form">
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>✍️</span> Form Input LKH - Divisi Completing
          </h2>
          <small class="text-muted">
            Pencatatan proses Hot Foil Stamping, Varnish UV Gloss, Laminasi Flute, dan efisiensi plano finishing
          </small>
        </div>
        <router-link to="/completing/lkh" class="btn btn-secondary btn-sm">
          <span>📋</span> Lihat Data LKH Completing
        </router-link>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="card">
      <!-- Section 1: Identitas Shift & Mesin Finishing -->
      <h4 class="form-sec-title">1. Identitas Shift &amp; Mesin Completing</h4>
      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Tanggal Produksi <span class="text-danger">*</span></label>
          <input type="date" v-model="form.tanggal" class="form-control" required />
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
          <label>Pilih Mesin Finishing <span class="text-danger">*</span></label>
          <select v-model="form.mesin" class="form-control" required>
            <option value="Hot Stamping Foil 1 & UV Gloss">Hot Stamping Foil 1 &amp; UV Gloss</option>
            <option value="Hot Stamping Foil 2">Hot Stamping Foil 2</option>
            <option value="Varnish UV Gloss Obat">Varnish UV Gloss Obat</option>
            <option value="Laminasi Flute E/F">Laminasi Flute E/F</option>
            <option value="Laminasi Doff & Spot UV">Laminasi Doff &amp; Spot UV</option>
          </select>
        </div>
      </div>

      <div class="grid grid-2 gap-1 mb-1">
        <div class="form-group">
          <label>Operator Utama (Staff Completing) <span class="text-danger">*</span></label>
          <input type="text" v-model="form.operator" class="form-control" required placeholder="Contoh: Nico / Sodok / Wahyudi" />
        </div>
        <div class="form-group">
          <label>Helper / Asisten Mesin</label>
          <input type="text" v-model="form.helper" class="form-control" placeholder="Nama helper completing..." />
        </div>
      </div>

      <!-- Section 2: Job Order & Realisasi Plano Finishing -->
      <h4 class="form-sec-title">2. Realisasi Finishing (Satuan: Lembar Plano)</h4>
      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Nomor Job Order (PP) <span class="text-danger">*</span></label>
          <input type="text" v-model="form.no_pp" class="form-control" required placeholder="Contoh: PP-2026-0840" />
        </div>
        <div class="form-group">
          <label>Nama Produk <span class="text-danger">*</span></label>
          <input type="text" v-model="form.produk_nama" class="form-control" required placeholder="Nama produk cetak..." />
        </div>
        <div class="form-group">
          <label>Plano Diterima dari Cetak <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.input_plano" class="form-control" required min="1" placeholder="Contoh: 48500" />
        </div>
      </div>

      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Realisasi Proses (Plano Selesai Diproses) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.realisasi" class="form-control" required min="0" placeholder="Contoh: 48500" />
        </div>
        <div class="form-group">
          <label>Hasil Baik (Good Plano Siap Lanjut) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.hasil_baik" class="form-control" required min="0" placeholder="Contoh: 47900" />
        </div>
        <div class="form-group">
          <label>Afval / Reject Completing (Plano)</label>
          <input type="number" :value="form.realisasi - form.hasil_baik" class="form-control" readonly style="background: #f1f5f9; font-weight: 700; color: #dc2626;" />
        </div>
      </div>

      <!-- Section 3: Parameter Teknis Finishing -->
      <h4 class="form-sec-title">3. Parameter Teknis &amp; Konsumsi Bahan</h4>
      <div class="grid grid-4 gap-08 mb-1">
        <div class="form-group">
          <label>Suhu Foil (°C)</label>
          <input type="number" v-model.number="form.parameter.suhu_foil" class="form-control" placeholder="110 - 130" />
        </div>
        <div class="form-group">
          <label>Tekanan Foil / Roll (bar)</label>
          <input type="number" step="0.1" v-model.number="form.parameter.tekanan_bar" class="form-control" placeholder="Contoh: 3.5" />
        </div>
        <div class="form-group">
          <label>Roll Foil Terpakai (meter)</label>
          <input type="number" v-model.number="form.parameter.foil_meter" class="form-control" placeholder="Meter" />
        </div>
        <div class="form-group">
          <label>Viskositas UV / Lem</label>
          <input type="text" v-model="form.parameter.viskositas" class="form-control" placeholder="Contoh: 22 sec DIN4" />
        </div>
      </div>

      <!-- Section 4: Klasifikasi Cacat Khas Completing -->
      <h4 class="form-sec-title">4. Klasifikasi Cacat Khas Completing (Plano)</h4>
      <div class="grid grid-4 gap-08 mb-1">
        <div class="form-group">
          <label>Foil Lepas / Mengelupas</label>
          <input type="number" v-model.number="form.cacat.foil_peel" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Register Foil Melenceng</label>
          <input type="number" v-model.number="form.cacat.foil_misreg" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>UV Bergaris / Bintik</label>
          <input type="number" v-model.number="form.cacat.uv_streaks" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Flute Delaminasi / Lepas</label>
          <input type="number" v-model.number="form.cacat.delaminasi" class="form-control" min="0" placeholder="0" />
        </div>
      </div>

      <div class="form-group mb-1">
        <label>Catatan Khusus / Hambatan Mesin Completing</label>
        <textarea v-model="form.catatan" class="form-control" rows="2" placeholder="Tuliskan kendala suhu klise, setting foil roll, atau kondisi lem flute..."></textarea>
      </div>

      <!-- Tombol Simpan -->
      <div class="d-flex justify-between align-center mt-1 pt-1" style="border-top: 1px solid #e2e8f0;">
        <router-link to="/completing/lkh" class="btn btn-secondary">
          Batal
        </router-link>
        <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.8rem; font-weight: 700;">
          💾 Simpan LKH Completing
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '../../utils/api'
import { showAlert } from '../../view/modal/modalService'

const router = useRouter()

const todayStr = new Date().toISOString().split('T')[0]

const form = ref({
  tanggal: todayStr,
  shift: '1',
  mesin: 'Hot Stamping Foil 1 & UV Gloss',
  operator: 'Nico',
  helper: '',
  no_pp: 'PP-2026-0821',
  produk_nama: 'Etiket Rokok Djarum 12 Super',
  input_plano: 48500,
  realisasi: 45000,
  hasil_baik: 44200,
  parameter: {
    suhu_foil: 120,
    tekanan_bar: 3.5,
    foil_meter: 1500,
    viskositas: '22s'
  },
  cacat: {
    foil_peel: 300,
    foil_misreg: 200,
    uv_streaks: 150,
    delaminasi: 150
  },
  catatan: 'Spot UV lancar, pemanas foil stabil'
})

const handleSubmit = async () => {
  if (form.value.hasil_baik > form.value.realisasi) {
    await showAlert('Jumlah hasil baik tidak boleh lebih besar dari realisasi proses!', {
      title: 'Validasi Gagal',
      type: 'warning'
    })
    return
  }

  const afval = form.value.realisasi - form.value.hasil_baik

  try {
    const payload = {
      divisi: 'completing',
      tanggal: form.value.tanggal,
      shift: form.value.shift,
      mesin_id: 6,
      operator: form.value.operator + (form.value.helper ? ` & ${form.value.helper}` : ''),
      start: `${form.value.tanggal} 08:00:00`,
      finish: `${form.value.tanggal} 16:00:00`,
      setting_menit: 25,
      '5s_menit': 15,
      lembur_menit: 0,
      keterangan_lembur: '',
      problem: form.value.catatan || Object.entries(form.value.cacat).filter(([_, v]) => v > 0).map(([k, v]) => `${k}: ${v}`).join(', '),
      pp_items: [{
        no_pp: form.value.no_pp,
        produk_nama: form.value.produk_nama,
        tahap_sortir: 'CMP',
        good: form.value.hasil_baik,
        reject: afval,
        setup_reject: 150,
        total_layar: form.value.realisasi,
        total_proses: form.value.realisasi,
        plan_shift: form.value.input_plano,
        waktu_prod_jam: 8,
        setting_menit: 25,
        '5s_menit': 15,
        total_dt_menit: 40,
        jam_jalan: 7.33,
        downtime: [],
        cacat: []
      }]
    }

    await apiFetch('lkh.php?action=create', {
      method: 'POST',
      body: JSON.stringify(payload)
    })

    await showAlert(
      `Laporan Kerja Harian Completing untuk No PP ${form.value.no_pp || '-'} berhasil disimpan ke database.\n` +
      `• Realisasi: ${form.value.realisasi?.toLocaleString('id-ID')} plano\n` +
      `• Hasil Baik: ${form.value.hasil_baik?.toLocaleString('id-ID')} plano\n` +
      `• Afval Completing: ${afval.toLocaleString('id-ID')} plano`,
      {
        title: 'LKH Completing Berhasil Disimpan',
        type: 'success'
      }
    )

    router.push('/completing/lkh')
  } catch (e) {
    showAlert('Gagal menyimpan LKH Completing: ' + (e.message || e))
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
