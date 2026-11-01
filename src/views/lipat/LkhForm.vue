<template>
  <div class="div-lkh-form">
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>✍️</span> Form Input LKH - Divisi Lipat (Folder Gluer)
          </h2>
          <small class="text-muted">
            Pencatatan realisasi pengeleman dan pelipatan box kemasan karton • Satuan: <b>Pieces (Pcs)</b>
          </small>
        </div>
        <router-link to="/lipat/lkh" class="btn btn-secondary btn-sm">
          <span>📋</span> Lihat Data LKH Lipat
        </router-link>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="card">
      <!-- Section 1: Identitas Shift & Mesin -->
      <h4 class="form-sec-title">1. Identitas Shift &amp; Mesin Folder Gluer</h4>
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
          <label>Pilih Mesin Lipat Gluer <span class="text-danger">*</span></label>
          <select v-model="form.mesin" class="form-control" required>
            <option value="Herui Gluer 1 (High Speed)">Herui Gluer 1 (High Speed)</option>
            <option value="Herui Gluer 2">Herui Gluer 2</option>
            <option value="Crash Lock Bottom Gluer">Crash Lock Bottom Gluer</option>
            <option value="Folder Gluer Standard">Folder Gluer Standard</option>
          </select>
        </div>
      </div>

      <div class="grid grid-2 gap-1 mb-1">
        <div class="form-group">
          <label>Operator Utama (Staff Lipat) <span class="text-danger">*</span></label>
          <input type="text" v-model="form.operator" class="form-control" required placeholder="Contoh: Jevon / Sugeng" />
        </div>
        <div class="form-group">
          <label>Helper / Asisten Gluer</label>
          <input type="text" v-model="form.helper" class="form-control" placeholder="Nama helper mesin lipat..." />
        </div>
      </div>

      <!-- Section 2: Job Order & Tipe Kemasan -->
      <h4 class="form-sec-title">2. Job Order &amp; Tipe Pelipatan Box</h4>
      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Nomor Job Order (PP) <span class="text-danger">*</span></label>
          <input type="text" v-model="form.no_pp" class="form-control" required placeholder="Contoh: PP-2026-0840" />
        </div>
        <div class="form-group">
          <label>Nama Produk <span class="text-danger">*</span></label>
          <input type="text" v-model="form.produk_nama" class="form-control" required placeholder="Nama box kemasan..." />
        </div>
        <div class="form-group">
          <label>Tipe Model Box <span class="text-danger">*</span></label>
          <select v-model="form.tipe_box" class="form-control" required>
            <option value="Straight Line Box">Straight Line Box (Standar Lurus)</option>
            <option value="Crash Lock Auto Bottom">Crash Lock Auto Bottom (Kunci Bawah)</option>
            <option value="Side Seam Box">Side Seam Box</option>
            <option value="4-Corner / 6-Corner Box">4-Corner / 6-Corner Tray Box</option>
          </select>
        </div>
      </div>

      <!-- Section 3: Realisasi Pelipatan (Satuan: Pcs) -->
      <h4 class="form-sec-title">3. Realisasi Pelipatan Lem (Satuan: Pcs)</h4>
      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Target Masuk (Pcs dari Sortir/Cabut) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.target_pcs" class="form-control" required min="1" placeholder="Contoh: 96000" />
        </div>
        <div class="form-group">
          <label>Realisasi Proses (Pcs Dikerjakan) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.realisasi_pcs" class="form-control" required min="0" placeholder="Contoh: 95500" />
        </div>
        <div class="form-group">
          <label>Hasil Baik (Pcs Good Box Siap Packing) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.hasil_baik_pcs" class="form-control" required min="0" placeholder="Contoh: 95100" />
        </div>
      </div>

      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Reject / Afval Lipat (Pcs)</label>
          <input type="number" :value="rejectPcs" class="form-control" readonly style="background: #f1f5f9; font-weight: 700; color: #dc2626;" />
        </div>
        <div class="form-group">
          <label>Tipe Perekat / Lem</label>
          <select v-model="form.tipe_lem" class="form-control">
            <option value="Cold Waterbased PVA">Cold Waterbased PVA</option>
            <option value="Hotmelt Adhesive">Hotmelt Adhesive</option>
            <option value="Hybrid (Cold + Hotmelt)">Hybrid (Cold + Hotmelt)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Kecepatan Mesin (pcs/menit)</label>
          <input type="number" v-model.number="form.speed_cpm" class="form-control" placeholder="Contoh: 250" />
        </div>
      </div>

      <!-- Section 4: Klasifikasi Cacat Khas Lipat Lem -->
      <h4 class="form-sec-title">4. Klasifikasi Cacat Pelipatan Lem (Pcs)</h4>
      <div class="grid grid-4 gap-08 mb-1">
        <div class="form-group">
          <label>Fishtailing (Lipatan Miring)</label>
          <input type="number" v-model.number="form.cacat.fishtailing" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Lem Meluber (Squeezing)</label>
          <input type="number" v-model.number="form.cacat.lem_meleber" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Lem Lepas / Unglued</label>
          <input type="number" v-model.number="form.cacat.unglued" class="form-control" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label>Baret Roller / Tergores</label>
          <input type="number" v-model.number="form.cacat.baret" class="form-control" min="0" placeholder="0" />
        </div>
      </div>

      <div class="form-group mb-1">
        <label>Catatan Operasional Mesin Gluer</label>
        <textarea v-model="form.catatan" class="form-control" rows="2" placeholder="Tuliskan kendala nozzle semprot lem, tekanan belt konveyor penekan, atau setting lipatan..."></textarea>
      </div>

      <!-- Tombol Simpan -->
      <div class="d-flex justify-between align-center mt-1 pt-1" style="border-top: 1px solid #e2e8f0;">
        <router-link to="/lipat/lkh" class="btn btn-secondary">
          Batal
        </router-link>
        <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.8rem; font-weight: 700;">
          💾 Simpan LKH Lipat
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
  mesin: 'Herui Gluer 1 (High Speed)',
  operator: 'Jevon',
  helper: '',
  no_pp: 'PP-2026-0821',
  produk_nama: 'Etiket Rokok Djarum 12 Super',
  tipe_box: 'Straight Line Box',
  target_pcs: 100000,
  realisasi_pcs: 95000,
  hasil_baik_pcs: 94000,
  tipe_lem: 'Cold Waterbased PVA',
  speed_cpm: 250,
  cacat: {
    fishtailing: 400,
    lem_meleber: 300,
    unglued: 200,
    baret: 100
  },
  catatan: 'Folder gluer lancar, press belt optimal'
})

const rejectPcs = computed(() => {
  if (form.value.realisasi_pcs === null || form.value.hasil_baik_pcs === null) return 0
  return Math.max(0, form.value.realisasi_pcs - form.value.hasil_baik_pcs)
})

const handleSubmit = async () => {
  if (form.value.hasil_baik_pcs > form.value.realisasi_pcs) {
    await showAlert('Jumlah hasil baik tidak boleh lebih besar dari realisasi proses!', {
      title: 'Validasi Gagal',
      type: 'warning'
    })
    return
  }

  try {
    const payload = {
      divisi: 'lipat',
      tanggal: form.value.tanggal,
      shift: form.value.shift,
      mesin_id: 19,
      operator: form.value.operator + (form.value.helper ? ` & ${form.value.helper}` : ''),
      start: `${form.value.tanggal} 08:00:00`,
      finish: `${form.value.tanggal} 16:00:00`,
      setting_menit: 20,
      '5s_menit': 15,
      lembur_menit: 0,
      keterangan_lembur: '',
      problem: form.value.catatan || Object.entries(form.value.cacat).filter(([_, v]) => v > 0).map(([k, v]) => `${k}: ${v}`).join(', '),
      pp_items: [{
        no_pp: form.value.no_pp,
        produk_nama: form.value.produk_nama,
        tahap_sortir: 'LPT',
        good: form.value.hasil_baik_pcs,
        reject: rejectPcs.value,
        setup_reject: 100,
        total_layar: form.value.realisasi_pcs,
        total_proses: form.value.realisasi_pcs,
        plan_shift: form.value.target_pcs,
        waktu_prod_jam: 8,
        setting_menit: 20,
        '5s_menit': 15,
        total_dt_menit: 35,
        jam_jalan: 7.42,
        downtime: [],
        cacat: []
      }]
    }

    await apiFetch('lkh.php?action=create', {
      method: 'POST',
      body: JSON.stringify(payload)
    })

    const msg =
      `Laporan Kerja Harian Divisi Lipat berhasil disimpan ke database.\n` +
      `• No PP: ${form.value.no_pp || '-'}\n` +
      `• Mesin: ${form.value.mesin} (${form.value.tipe_box})\n` +
      `• Realisasi: ${form.value.realisasi_pcs?.toLocaleString('id-ID')} pcs\n` +
      `• Hasil Baik: ${form.value.hasil_baik_pcs?.toLocaleString('id-ID')} pcs\n` +
      `• Reject Lipat: ${rejectPcs.value.toLocaleString('id-ID')} pcs`

    await showAlert(msg, {
      title: 'LKH Lipat Berhasil Disimpan',
      type: 'success'
    })

    router.push('/lipat/lkh')
  } catch (e) {
    showAlert('Gagal menyimpan LKH Lipat: ' + (e.message || e))
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
