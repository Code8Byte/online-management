<template>
  <div class="div-lkh-form">
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>✍️</span> Form Input LKH - Divisi Cetak
          </h2>
          <small class="text-muted">
            Pencatatan realisasi druk mesin cetak offset, afval plano, dan parameter kualitas cetak
          </small>
        </div>
        <router-link to="/cetak/lkh" class="btn btn-secondary btn-sm">
          <span>📋</span> Lihat Data LKH Cetak
        </router-link>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="card">
      <!-- Section 1: Identitas Shift & Mesin -->
      <h4 class="form-sec-title">1. Identitas Shift &amp; Mesin Cetak</h4>
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
          <label>Pilih Mesin Cetak <span class="text-danger">*</span></label>
          <select v-model="form.mesin" class="form-control" required>
            <option value="SM 23 (Heidelberg)">SM 23 (Heidelberg Speedmaster 102)</option>
            <option value="SM 25 (Heidelberg)">SM 25 (Heidelberg CD 102)</option>
            <option value="SM 27 (Heidelberg)">SM 27 (Heidelberg SM)</option>
            <option value="KBA Rapida 106">KBA Rapida 106 High Speed</option>
            <option value="SM 10 (Heidelberg)">SM 10 (Heidelberg 2 Warna)</option>
          </select>
        </div>
      </div>

      <div class="grid grid-2 gap-1 mb-1">
        <div class="form-group">
          <label>Operator Utama (Staff Cetak) <span class="text-danger">*</span></label>
          <input type="text" v-model="form.operator" class="form-control" required placeholder="Contoh: Tito / Yoel / Calvin" />
        </div>
        <div class="form-group">
          <label>Helper / Asisten Druk</label>
          <input type="text" v-model="form.helper" class="form-control" placeholder="Nama helper mesin cetak..." />
        </div>
      </div>

      <!-- Section 2: Job Order & Realisasi Plano -->
      <h4 class="form-sec-title">2. Realisasi Druk Cetak (Satuan: Plano)</h4>
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
          <label>Target Plano Diberikan (PPIC) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.target_plano" class="form-control" required min="1" placeholder="Contoh: 50000" />
        </div>
      </div>

      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Realisasi Druk (Total Lembar Dicetak) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.realisasi_druk" class="form-control" required min="0" placeholder="Contoh: 49500" />
        </div>
        <div class="form-group">
          <label>Hasil Baik (Good Plano) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.hasil_baik" class="form-control" required min="0" placeholder="Contoh: 48500" />
        </div>
        <div class="form-group">
          <label>Afval / Reject Cetak (Plano)</label>
          <input type="number" :value="form.realisasi_druk - form.hasil_baik" class="form-control" readonly style="background: #f1f5f9; font-weight: 700; color: #dc2626;" />
        </div>
      </div>

      <!-- Section 3: Klasifikasi Cacat Khas Cetak Offset -->
      <h4 class="form-sec-title">3. Analisis Cacat Khas Cetak Offset</h4>
      <div class="grid grid-4 gap-08 mb-1">
        <div class="form-group">
          <label>Scumming (Plano)</label>
          <input type="number" v-model.number="form.cacat.scumming" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Hickey / Bintik (Plano)</label>
          <input type="number" v-model.number="form.cacat.hickey" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Misregister Warna (Plano)</label>
          <input type="number" v-model.number="form.cacat.misregister" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Set-off / Varnish (Plano)</label>
          <input type="number" v-model.number="form.cacat.setoff" class="form-control" min="0" placeholder="0" />
        </div>
      </div>

      <!-- Submit -->
      <div class="d-flex justify-between align-center pt-1 border-top">
        <span class="text-muted" style="font-size: 0.82rem;">* Data LKH Cetak akan terhubung otomatis ke Progress PP dan diteruskan ke Divisi Completing</span>
        <button type="submit" class="btn btn-primary" :disabled="submitting">
          {{ submitting ? 'Menyimpan...' : '💾 Simpan LKH Cetak' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '../../utils/api'
import { showAlert, showSuccess } from '../modal/modalService'

const router = useRouter()
const submitting = ref(false)

const form = ref({
  tanggal: new Date().toISOString().split('T')[0],
  shift: '1',
  mesin: 'SM 23 (Heidelberg)',
  operator: 'Tito',
  helper: 'Slamet',
  no_pp: 'PP-2026-0821',
  produk_nama: 'Etiket Rokok Djarum 12 Super',
  target_plano: 50000,
  realisasi_druk: 49500,
  hasil_baik: 48500,
  cacat: {
    scumming: 300,
    hickey: 200,
    misregister: 300,
    setoff: 200
  }
})

const handleSubmit = async () => {
  submitting.value = true
  try {
    const payload = {
      divisi: 'cetak',
      tanggal: form.value.tanggal,
      shift: form.value.shift,
      mesin_id: 1,
      operator: form.value.operator + (form.value.helper ? ` & ${form.value.helper}` : ''),
      start: `${form.value.tanggal} 08:00:00`,
      finish: `${form.value.tanggal} 16:00:00`,
      setting_menit: 30,
      '5s_menit': 15,
      lembur_menit: 0,
      keterangan_lembur: '',
      problem: Object.entries(form.value.cacat).filter(([_, v]) => v > 0).map(([k, v]) => `${k}: ${v}`).join(', '),
      pp_items: [{
        no_pp: form.value.no_pp,
        produk_nama: form.value.produk_nama,
        tahap_sortir: 'CTK',
        good: form.value.hasil_baik,
        reject: form.value.realisasi_druk - form.value.hasil_baik,
        setup_reject: 100,
        total_layar: form.value.realisasi_druk,
        total_proses: form.value.realisasi_druk,
        plan_shift: form.value.target_plano,
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

    showSuccess(
      `LKH Divisi Cetak untuk Job Order ${form.value.no_pp} berhasil disimpan ke database!\nHasil Baik: ${form.value.hasil_baik.toLocaleString('id-ID')} plano siap diteruskan ke Completing.`,
      { title: 'LKH Cetak Tersimpan' }
    )
    router.push('/cetak/lkh')
  } catch (e) {
    showAlert('Gagal menyimpan LKH Cetak: ' + (e.message || e))
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.div-lkh-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.form-sec-title {
  margin: 0.5rem 0 0.8rem;
  font-size: 0.95rem;
  font-weight: 800;
  color: #1e293b;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 0.35rem;
}
</style>
