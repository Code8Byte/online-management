<template>
  <div class="ppic-form-card">
    <div class="form-head">
      <div class="head-title">
        <h3>✍️ Form Input Job Order &amp; Penjadwalan Mesin (PPIC)</h3>
        <p>Kalkulator otomatis kebutuhan plano dari target oplaag dan penjadwalan kapasitas mesin</p>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="form-body">
      <!-- Section 1: Informasi Dasar PP -->
      <div class="form-section">
        <h4 class="sec-title">1. Data Pesanan &amp; Job Order</h4>
        <div class="form-grid-3">
          <div class="form-group">
            <label>Nomor Job Order (PP) <span class="req">*</span></label>
            <input
              type="text"
              v-model="form.no_pp"
              class="form-input"
              required
              placeholder="Contoh: PP-2026-0840"
            />
          </div>
          <div class="form-group span-2">
            <label>Nama Produk <span class="req">*</span></label>
            <input
              type="text"
              v-model="form.produk_nama"
              class="form-input"
              required
              placeholder="Contoh: Kemasan Rokok Sampoerna 16 / Box Paracetamol"
            />
          </div>
        </div>
      </div>

      <!-- Section 2: Kalkulator Kapasitas & Kebutuhan Plano -->
      <div class="form-section">
        <h4 class="sec-title">2. Kalkulator Oplaag &amp; Kebutuhan Plano</h4>
        <div class="form-grid-4">
          <div class="form-group">
            <label>Target Oplaag (Pcs) <span class="req">*</span></label>
            <input
              type="number"
              v-model.number="form.oplaag_pcs"
              class="form-input"
              required
              min="1"
              step="1000"
              placeholder="Contoh: 2000000"
            />
            <small class="hint">Jumlah box / pcs pesanan</small>
          </div>

          <div class="form-group">
            <label>Up per Plano (Isi per Lembar) <span class="req">*</span></label>
            <input
              type="number"
              v-model.number="form.up_per_plano"
              class="form-input"
              required
              min="1"
              max="200"
              placeholder="Contoh: 4 atau 40"
            />
            <small class="hint">1 plano menghasilkan berapa pcs</small>
          </div>

          <div class="form-group">
            <label>Insheet / Waste Cetak (%)</label>
            <input
              type="number"
              v-model.number="form.insheet_percent"
              class="form-input"
              min="0"
              max="20"
              step="0.5"
            />
            <small class="hint">Standar insheet druk (default 2%)</small>
          </div>

          <div class="form-group">
            <label>Tanggal Mulai Masuk Mesin <span class="req">*</span></label>
            <input
              type="date"
              v-model="form.start_date"
              class="form-input font-bold"
              required
              style="cursor: pointer; background: #fff;"
              @click="$event.target.showPicker && $event.target.showPicker()"
            />
            <small class="hint">Mulai cetak pertama</small>
          </div>
        </div>

        <!-- Live Calculation Result Card -->
        <div class="calc-result-box">
          <div class="calc-badge">HASIL PERHITUNGAN PLANO OTOMATIS</div>
          <div class="calc-grid">
            <div class="calc-item">
              <span>Netto Plano:</span>
              <b>{{ formatNumber(calcResult.nettoPlano) }} <small>plano</small></b>
              <i>({{ formatNumber(form.oplaag_pcs) }} pcs &divide; {{ form.up_per_plano }} up)</i>
            </div>
            <div class="calc-item">
              <span>Cadangan Insheet ({{ form.insheet_percent }}%):</span>
              <b>+{{ formatNumber(calcResult.insheetPlano) }} <small>plano</small></b>
              <i>Afval persiapan / stel warna</i>
            </div>
            <div class="calc-item calc-total">
              <span>TOTAL KEBUTUHAN PLANO:</span>
              <b class="txt-brand">{{ formatNumber(calcResult.totalPlano) }} <small>PLANO</small></b>
              <i>Wajib disiapkan dari gudang kertas</i>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Alur Tahapan & Pemilihan Mesin -->
      <div class="form-section">
        <h4 class="sec-title">3. Penjadwalan Kapasitas &amp; Pemilihan Mesin per Tahap</h4>
        <p class="sec-desc">
          Centang tahapan proses yang dilalui PP ini. Durasi jadwal akan otomatis dikalkulasi berdasarkan kapasitas mesin yang dipilih.
        </p>

        <div class="stages-config-list">
          <!-- Tahap 1: Cetak (Wajib) -->
          <div class="stage-config-card" :class="{ disabled: !form.stages[0].enabled }">
            <div class="stage-toggle">
              <label class="check-box-label">
                <input type="checkbox" v-model="form.stages[0].enabled" disabled />
                <span class="stage-name-tag tag-ctk">1. CETAK (Plano)</span>
              </label>
              <span class="badge-mandatory">Wajib Masuk</span>
            </div>
            <div class="stage-selector">
              <label>Pilih Mesin Cetak:</label>
              <select v-model="form.stages[0].mesin_id" class="form-select" required>
                <option v-for="m in machineOptionsByStage('cetak')" :key="m.id" :value="m.id">
                  {{ m.nama }} (Speed: {{ formatNumber(m.speed_jph) }} plano/jam &bull; Kapasitas: {{ formatNumber(m.speed_jph * m.jam_hari) }} plano/hari)
                </option>
              </select>
            </div>
            <div class="stage-est">
              <span>Estimasi Durasi: <b>{{ estimateDaysForStage(0) }} Hari</b></span>
              <small>Memproses {{ formatNumber(calcResult.totalPlano) }} plano</small>
            </div>
          </div>

          <!-- Tahap 2: Completing (Foil / Varnish / Laminasi) -->
          <div class="stage-config-card" :class="{ disabled: !form.stages[1].enabled }">
            <div class="stage-toggle">
              <label class="check-box-label">
                <input type="checkbox" v-model="form.stages[1].enabled" />
                <span class="stage-name-tag tag-cmp">2. COMPLETING (Plano)</span>
              </label>
              <span v-if="!form.stages[1].enabled" class="badge-skip">Dilewati</span>
            </div>
            <div v-if="form.stages[1].enabled" class="stage-selector">
              <label>Pilih Mesin Finishing:</label>
              <select v-model="form.stages[1].mesin_id" class="form-select">
                <option v-for="m in machineOptionsByStage('completing')" :key="m.id" :value="m.id">
                  {{ m.nama }} (Speed: {{ formatNumber(m.speed_jph) }} plano/jam &bull; Kapasitas: {{ formatNumber(m.speed_jph * m.jam_hari) }} plano/hari)
                </option>
              </select>
            </div>
            <div v-if="form.stages[1].enabled" class="stage-est">
              <span>Estimasi Durasi: <b>{{ estimateDaysForStage(1) }} Hari</b></span>
              <small>Memproses {{ formatNumber(calcResult.totalPlano) }} plano</small>
            </div>
          </div>

          <!-- Tahap 3: Cabut (Pond / Die-Cutting) -->
          <div class="stage-config-card" :class="{ disabled: !form.stages[2].enabled }">
            <div class="stage-toggle">
              <label class="check-box-label">
                <input type="checkbox" v-model="form.stages[2].enabled" />
                <span class="stage-name-tag tag-cbt">3. CABUT (Pond &bull; Plano ➜ Pcs)</span>
              </label>
              <span v-if="!form.stages[2].enabled" class="badge-skip">Dilewati</span>
            </div>
            <div v-if="form.stages[2].enabled" class="stage-selector">
              <label>Pilih Mesin Pond:</label>
              <select v-model="form.stages[2].mesin_id" class="form-select">
                <option v-for="m in machineOptionsByStage('cabut')" :key="m.id" :value="m.id">
                  {{ m.nama }} (Speed: {{ formatNumber(m.speed_jph) }} plano/jam &bull; Kapasitas: {{ formatNumber(m.speed_jph * m.jam_hari) }} plano/hari)
                </option>
              </select>
            </div>
            <div v-if="form.stages[2].enabled" class="stage-est">
              <span>Estimasi Durasi: <b>{{ estimateDaysForStage(2) }} Hari</b></span>
              <small>Memotong {{ formatNumber(calcResult.totalPlano) }} plano &rarr; {{ formatNumber(form.oplaag_pcs) }} pcs</small>
            </div>
          </div>

          <!-- Tahap 4: Sortir (QC & Optical Inspection) -->
          <div class="stage-config-card" :class="{ disabled: !form.stages[3].enabled }">
            <div class="stage-toggle">
              <label class="check-box-label">
                <input type="checkbox" v-model="form.stages[3].enabled" />
                <span class="stage-name-tag tag-srt">4. SORTIR &amp; QC (Pcs)</span>
              </label>
              <span v-if="!form.stages[3].enabled" class="badge-skip">Dilewati (QC Inline)</span>
            </div>
            <div v-if="form.stages[3].enabled" class="stage-selector">
              <label>Pilih Mesin / Meja Sortir:</label>
              <select v-model="form.stages[3].mesin_id" class="form-select">
                <option v-for="m in machineOptionsByStage('sortir')" :key="m.id" :value="m.id">
                  {{ m.nama }} (Speed: {{ formatNumber(m.speed_jph) }} pcs/jam &bull; Kapasitas: {{ formatNumber(m.speed_jph * m.jam_hari) }} pcs/hari)
                </option>
              </select>
            </div>
            <div v-if="form.stages[3].enabled" class="stage-est">
              <span>Estimasi Durasi: <b>{{ estimateDaysForStage(3) }} Hari</b></span>
              <small>Memeriksa {{ formatNumber(form.oplaag_pcs) }} pcs sheet dari Cabut</small>
            </div>
          </div>

          <!-- Tahap 5: Lipat (Folder Gluer) -->
          <div class="stage-config-card" :class="{ disabled: !form.stages[4].enabled }">
            <div class="stage-toggle">
              <label class="check-box-label">
                <input type="checkbox" v-model="form.stages[4].enabled" />
                <span class="stage-name-tag tag-lpt">5. LIPAT &amp; LEM (Pcs)</span>
              </label>
              <span v-if="!form.stages[4].enabled" class="badge-skip">Dilewati (Produk Lembaran)</span>
            </div>
            <div v-if="form.stages[4].enabled" class="stage-selector">
              <label>Pilih Mesin Gluer:</label>
              <select v-model="form.stages[4].mesin_id" class="form-select">
                <option v-for="m in machineOptionsByStage('lipat')" :key="m.id" :value="m.id">
                  {{ m.nama }} (Speed: {{ formatNumber(m.speed_jph) }} pcs/jam &bull; Kapasitas: {{ formatNumber(m.speed_jph * m.jam_hari) }} pcs/hari)
                </option>
              </select>
            </div>
            <div v-if="form.stages[4].enabled" class="stage-est">
              <span>Estimasi Durasi: <b>{{ estimateDaysForStage(4) }} Hari</b></span>
              <small>Mengelem {{ formatNumber(form.oplaag_pcs) }} pcs lolos sortir</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="form-foot">
        <button type="submit" class="btn-submit-ppic" :disabled="saving">
          {{ saving ? 'Menyimpan &amp; Membuat Gantt Chart...' : '🚀 Simpan Jadwal &amp; Generate Gantt Chart Otomatis' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  masterMesinPPIC,
  calculatePlanoFromOplaag,
  calculateEstimatedDays,
  addPpicJobOrderSchedule,
  CURRENT_SYSTEM_DATE
} from '../../data/schedulePPIC'
import { formatNumber } from '../../data/progressPP'
import { showSuccess, showWarning } from '../../view/modal/modalService'

const emit = defineEmits(['schedule-created'])

const saving = ref(false)

const form = ref({
  no_pp: 'PP-2026-0840',
  produk_nama: 'Box Rokok Marlboro Filter Black 16',
  oplaag_pcs: 2000000,
  up_per_plano: 4,
  insheet_percent: 2,
  start_date: CURRENT_SYSTEM_DATE,
  stages: [
    { stage: 'cetak', enabled: true, mesin_id: 'sm23' },
    { stage: 'completing', enabled: true, mesin_id: 'hsf1' },
    { stage: 'cabut', enabled: true, mesin_id: 'ms1' },
    { stage: 'sortir', enabled: true, mesin_id: 'sortir_fs500' },
    { stage: 'lipat', enabled: true, mesin_id: 'gluer_herui' }
  ]
})

const calcResult = computed(() => {
  return calculatePlanoFromOplaag(form.value.oplaag_pcs, form.value.up_per_plano, form.value.insheet_percent)
})

const machineOptionsByStage = (stage) => {
  return masterMesinPPIC.filter(m => m.stage === stage)
}

const estimateDaysForStage = (stageIndex) => {
  const st = form.value.stages[stageIndex]
  if (!st || !st.enabled) return 0
  const machine = masterMesinPPIC.find(m => m.id === st.mesin_id)
  if (!machine) return 0

  const isPlano = machine.unit === 'plano'
  const qty = isPlano ? calcResult.value.totalPlano : form.value.oplaag_pcs
  const dur = calculateEstimatedDays(qty, machine.speed_jph, machine.jam_hari)
  return dur.days
}

const handleSubmit = async () => {
  if (!form.value.no_pp.trim() || !form.value.produk_nama.trim()) {
    showWarning('Nomor PP dan Nama Produk wajib diisi!')
    return
  }

  if (form.value.oplaag_pcs <= 0 || form.value.up_per_plano <= 0) {
    showWarning('Target Oplaag dan Up per Plano harus lebih dari 0!')
    return
  }

  saving.value = true
  try {
    const tasks = addPpicJobOrderSchedule({
      no_pp: form.value.no_pp.trim(),
      produk_nama: form.value.produk_nama.trim(),
      oplaag_pcs: form.value.oplaag_pcs,
      up_per_plano: form.value.up_per_plano,
      insheet_percent: form.value.insheet_percent,
      start_date: form.value.start_date,
      stages: form.value.stages
    })

    await showSuccess(
      `Jadwal Job Order '${form.value.no_pp}' berhasil dibuat!\n\n` +
      `Kebutuhan Plano: ${formatNumber(calcResult.value.totalPlano)} plano\n` +
      `Total Tahapan Terjadwal: ${tasks.length} proses mesin\n\n` +
      `Gantt Chart telah diperbarui secara otomatis.`,
      { title: 'Jadwal PPIC Berhasil Dibuat' }
    )

    emit('schedule-created')
  } catch (err) {
    showWarning('Gagal membuat jadwal: ' + err.message)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.ppic-form-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
  overflow: hidden;
}

.form-head {
  padding: 1.25rem 1.5rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.head-title h3 {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
}
.head-title p {
  margin: 4px 0 0;
  font-size: 0.8rem;
  color: #64748b;
}

.form-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}
.sec-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 800;
  color: #1e293b;
  border-left: 3px solid #4f46e5;
  padding-left: 8px;
}
.sec-desc {
  margin: 0;
  font-size: 0.78rem;
  color: #64748b;
}

.form-grid-3 {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 1rem;
}
.form-grid-4 {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.form-group label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #334155;
}
.form-group .req {
  color: #ef4444;
}
.form-group .hint {
  font-size: 0.7rem;
  color: #94a3b8;
}

.form-input, .form-select {
  height: 38px;
  padding: 0 0.75rem;
  font-size: 0.85rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background: #ffffff;
  color: #0f172a;
  outline: none;
  transition: border-color 0.15s;
}
.form-input:focus, .form-select:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15);
}
.font-bold {
  font-weight: 700;
}

/* Calculation Result Box */
.calc-result-box {
  background: linear-gradient(135deg, #eef2ff, #f8fafc);
  border: 1.5px solid #c7d2fe;
  border-radius: 10px;
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}
.calc-badge {
  font-size: 0.7rem;
  font-weight: 800;
  color: #3730a3;
  letter-spacing: 0.05em;
}
.calc-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1.5fr;
  gap: 1rem;
}
.calc-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.calc-item span {
  font-size: 0.75rem;
  color: #475569;
}
.calc-item b {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
}
.calc-item i {
  font-size: 0.7rem;
  color: #64748b;
  font-style: normal;
}
.calc-total b {
  font-size: 1.45rem;
  color: #4f46e5;
}

/* Stages Config List */
.stages-config-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.stage-config-card {
  display: grid;
  grid-template-columns: 240px 1fr 180px;
  align-items: center;
  gap: 1rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0.8rem 1rem;
  transition: all 0.15s;
}
.stage-config-card.disabled {
  background: #f8fafc;
  opacity: 0.65;
  border-style: dashed;
}
.stage-toggle {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
.check-box-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
.stage-name-tag {
  font-size: 0.75rem;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 6px;
}
.tag-ctk { background: #e0e7ff; color: #4338ca; }
.tag-cmp { background: #fef3c7; color: #b45309; }
.tag-cbt { background: #e0f2fe; color: #0369a1; }
.tag-lpt { background: #dcfce7; color: #15803d; }
.tag-srt { background: #ede9fe; color: #6d28d9; }

.badge-mandatory {
  font-size: 0.68rem;
  font-weight: 700;
  background: #f1f5f9;
  color: #475569;
  padding: 2px 6px;
  border-radius: 4px;
}
.badge-skip {
  font-size: 0.68rem;
  font-weight: 700;
  background: #fee2e2;
  color: #dc2626;
  padding: 2px 6px;
  border-radius: 4px;
}

.stage-selector {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.stage-selector label {
  font-size: 0.72rem;
  font-weight: 700;
  color: #64748b;
}

.stage-est {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
  text-align: right;
  font-size: 0.78rem;
}
.stage-est b {
  font-size: 1.05rem;
  color: #0f172a;
}
.stage-est small {
  font-size: 0.7rem;
  color: #64748b;
}

/* Footer Submit */
.form-foot {
  display: flex;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}
.btn-submit-ppic {
  height: 44px;
  padding: 0 1.5rem;
  background: linear-gradient(135deg, #4f46e5, #4338ca);
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 0.92rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
  transition: all 0.15s;
}
.btn-submit-ppic:hover:not(:disabled) {
  background: linear-gradient(135deg, #4338ca, #3730a3);
  box-shadow: 0 6px 16px rgba(79, 70, 229, 0.4);
}
.btn-submit-ppic:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .form-grid-3, .form-grid-4, .calc-grid {
    grid-template-columns: 1fr;
  }
  .stage-config-card {
    grid-template-columns: 1fr;
    gap: 0.6rem;
  }
  .stage-est {
    align-items: flex-start;
    text-align: left;
  }
}
</style>
