<template>
  <div class="div-lkh-form">
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>✍️</span> Rilis Target &amp; Work Order Harian - PPIC
          </h2>
          <small class="text-muted">
            Kalkulator otomatis kebutuhan lembar plano dari oplaag pcs, up per plano, serta penugasan target ke seluruh divisi
          </small>
        </div>
        <router-link to="/ppic/lkh" class="btn btn-secondary btn-sm">
          <span>📋</span> Lihat Data LKH PPIC
        </router-link>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="card">
      <!-- Section 1: Identitas Job Order & Target Oplaag -->
      <h4 class="form-sec-title">1. Data Pesanan &amp; Kalkulator Plano Cetak</h4>
      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Nomor Job Order (PP) <span class="text-danger">*</span></label>
          <input type="text" v-model="form.no_pp" class="form-control" required placeholder="Contoh: PP-2026-0850" />
        </div>
        <div class="form-group">
          <label>Nama Produk / Pelanggan <span class="text-danger">*</span></label>
          <input type="text" v-model="form.produk_nama" class="form-control" required placeholder="Contoh: Kemasan Paracetamol 500mg" />
        </div>
        <div class="form-group">
          <label>Target Oplaag Pesanan (Pcs) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.oplaag_pcs" class="form-control" required min="1" placeholder="Contoh: 2000000" />
        </div>
      </div>

      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>1 Plano Isi Berapa (Up per Plano) <span class="text-danger">*</span></label>
          <input type="number" v-model.number="form.up_per_plano" class="form-control" required min="1" placeholder="Contoh: 4" />
          <small class="text-muted">Jumlah box dalam 1 lembar plano</small>
        </div>
        <div class="form-group">
          <label>Toleransi Insheet / Afval (%)</label>
          <input type="number" step="0.5" v-model.number="form.insheet_percent" class="form-control" min="0" placeholder="Contoh: 2.5" />
          <small class="text-muted">Cadangan plano untuk insheet &amp; setup</small>
        </div>
        <div class="form-group">
          <label>Target Tanggal Selesai (Deadline) <span class="text-danger">*</span></label>
          <input type="date" v-model="form.deadline" class="form-control" required />
        </div>
      </div>

      <!-- Live Calculation Card: Kebutuhan Plano Cetak -->
      <div class="card mb-1" style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 1rem;">
        <div class="d-flex justify-between align-center mb-05">
          <span style="font-weight: 700; font-size: 0.9rem; color: #334155;">
            ⚡ Hasil Kalkulasi Plano (PPIC Engine)
          </span>
          <span class="badge badge-primary">Satuan Kebutuhan Kertas: Lembar Plano</span>
        </div>
        <div class="grid grid-3 gap-1">
          <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 0.8rem;">
            <div class="text-muted" style="font-size: 0.8rem; font-weight: 600;">PLANO NETTO</div>
            <div style="font-size: 1.3rem; font-weight: 800; color: #1e40af; margin-top: 2px;">
              {{ (calcPlanoNetto).toLocaleString('id-ID') }} <span style="font-size: 0.8rem; font-weight: 500;">plano</span>
            </div>
            <small class="text-muted">Oplaag Pcs &divide; Up per Plano</small>
          </div>
          <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 0.8rem;">
            <div class="text-muted" style="font-size: 0.8rem; font-weight: 600;">INSHEET / CADANGAN</div>
            <div style="font-size: 1.3rem; font-weight: 800; color: #b45309; margin-top: 2px;">
              {{ (calcInsheetPlano).toLocaleString('id-ID') }} <span style="font-size: 0.8rem; font-weight: 500;">plano</span>
            </div>
            <small class="text-muted">+{{ form.insheet_percent || 0 }}% Toleransi Afval</small>
          </div>
          <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 0.8rem;">
            <div class="text-muted" style="font-size: 0.8rem; font-weight: 600;">TOTAL PLANO DICETAK (BRUTO)</div>
            <div style="font-size: 1.3rem; font-weight: 800; color: #059669; margin-top: 2px;">
              {{ (calcTotalPlanoCetak).toLocaleString('id-ID') }} <span style="font-size: 0.8rem; font-weight: 500;">plano</span>
            </div>
            <small class="text-muted">Lembar plano wajib dirilis ke gudang kertas</small>
          </div>
        </div>
      </div>

      <!-- Section 2: Alokasi Mesin & Alur Produksi -->
      <h4 class="form-sec-title">2. Alokasi Mesin &amp; Rute Divisi</h4>
      <div class="grid grid-2 gap-1 mb-1">
        <div class="form-group">
          <label>Alokasi Mesin Cetak <span class="text-danger">*</span></label>
          <select v-model="form.mesin_cetak" class="form-control" required>
            <option value="SM 23 (Heidelberg)">SM 23 (Heidelberg Speedmaster 102)</option>
            <option value="SM 25 (Heidelberg)">SM 25 (Heidelberg CD 102)</option>
            <option value="SM 27 (Heidelberg)">SM 27 (Heidelberg SM)</option>
            <option value="KBA Rapida 106">KBA Rapida 106 High Speed</option>
            <option value="SM 10 (Heidelberg)">SM 10 (Heidelberg 2 Warna)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Alokasi Mesin Cabut (Pond) <span class="text-danger">*</span></label>
          <select v-model="form.mesin_cabut" class="form-control" required>
            <option value="Bobst Die Cutter MS 1">Bobst Die Cutter MS 1</option>
            <option value="Bobst Die Cutter MS 2">Bobst Die Cutter MS 2</option>
            <option value="Bobst Die Cutter MS 3">Bobst Die Cutter MS 3</option>
            <option value="Bobst Die Cutter MS 4">Bobst Die Cutter MS 4</option>
            <option value="Mesin Pond Manual">Mesin Pond Manual</option>
          </select>
        </div>
      </div>

      <div class="form-group mb-1">
        <label>Rute Alur Proses Produksi:</label>
        <div class="d-flex gap-1 flex-wrap align-center mt-05">
          <label class="d-flex align-center gap-05">
            <input type="checkbox" v-model="form.alur.cetak" checked disabled />
            <span>🖨️ Cetak (Wajib)</span>
          </label>
          <label class="d-flex align-center gap-05">
            <input type="checkbox" v-model="form.alur.completing" />
            <span>✨ Completing (Hot Foil / UV)</span>
          </label>
          <label class="d-flex align-center gap-05">
            <input type="checkbox" v-model="form.alur.cabut" checked disabled />
            <span>✂️ Cabut (Die-Cut Plano &rarr; Pcs)</span>
          </label>
          <label class="d-flex align-center gap-05">
            <input type="checkbox" v-model="form.alur.sortir" />
            <span>🔍 Sortir (Pcs)</span>
          </label>
          <label class="d-flex align-center gap-05">
            <input type="checkbox" v-model="form.alur.lipat" />
            <span>📦 Lipat &amp; Lem (Pcs)</span>
          </label>
        </div>
        <small class="text-muted">Urutan alur operasional: Sortir selalu dilakukan sebelum Lipat jika keduanya dipilih.</small>
      </div>

      <div class="form-group mb-1">
        <label>Instruksi Khusus PPIC ke Operator</label>
        <textarea v-model="form.catatan" class="form-control" rows="2" placeholder="Catatan batas waktu kirim, prioritas pallet, atau spesifikasi kertas..."></textarea>
      </div>

      <!-- Tombol Aksi -->
      <div class="d-flex justify-between align-center mt-1 pt-1" style="border-top: 1px solid #e2e8f0;">
        <router-link to="/ppic/lkh" class="btn btn-secondary">
          Batal
        </router-link>
        <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.8rem; font-weight: 700;">
          💾 Rilis Work Order Target PPIC
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { showAlert } from '../../view/modal/modalService'

const router = useRouter()
const todayStr = new Date().toISOString().split('T')[0]

const form = ref({
  no_pp: '',
  produk_nama: '',
  oplaag_pcs: 2000000,
  up_per_plano: 4,
  insheet_percent: 2.5,
  deadline: todayStr,
  mesin_cetak: 'SM 23 (Heidelberg)',
  mesin_cabut: 'Bobst Die Cutter MS 1',
  alur: {
    cetak: true,
    completing: false,
    cabut: true,
    sortir: true,
    lipat: true
  },
  catatan: ''
})

const calcPlanoNetto = computed(() => {
  const oplaag = form.value.oplaag_pcs || 0
  const up = form.value.up_per_plano || 1
  return Math.ceil(oplaag / up)
})

const calcInsheetPlano = computed(() => {
  const netto = calcPlanoNetto.value
  const pct = (form.value.insheet_percent || 0) / 100
  return Math.ceil(netto * pct)
})

const calcTotalPlanoCetak = computed(() => {
  return calcPlanoNetto.value + calcInsheetPlano.value
})

const handleSubmit = async () => {
  const msg =
    `Work Order PPIC untuk No PP ${form.value.no_pp} berhasil dirilis!\n` +
    `• Oplaag Pesanan: ${form.value.oplaag_pcs?.toLocaleString('id-ID')} pcs\n` +
    `• 1 Plano Isi: ${form.value.up_per_plano} pcs\n` +
    `• Target Plano Dicetak: ${calcTotalPlanoCetak.value.toLocaleString('id-ID')} plano\n` +
    `• Alokasi Mesin: ${form.value.mesin_cetak} & ${form.value.mesin_cabut}\n` +
    `• Jadwal otomatis ditambahkan ke Gantt Chart!`

  await showAlert(msg, {
    title: 'Work Order PPIC Berhasil Dirilis',
    type: 'success'
  })

  router.push('/ppic/lkh')
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
