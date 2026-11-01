<template>
  <div class="div-input-pp">
    <!-- Header Page -->
    <div class="card mb-1">
      <div class="d-flex justify-between align-center flex-wrap gap-1">
        <div>
          <h2 class="card-title" style="margin: 0; border: none; padding: 0; display: flex; align-items: center; gap: 8px;">
            <span>✍️</span> Form Input PP Baru &amp; Penjadwalan Mesin
          </h2>
          <small class="text-muted">
            Perencanaan Job Order baru: kalkulasi kebutuhan plano dari oplaag, alokasi jam &amp; menit masuk mesin, dan evaluasi detail batas muatan pengiriman
          </small>
        </div>
        <div class="d-flex gap-05 align-center">
          <router-link to="/ppic/jadwal" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
            <span>📊</span> Buka Jadwal &amp; Gantt Chart
          </router-link>
        </div>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="card">
      <!-- Section 1: Data Pesanan & Job Order -->
      <h4 class="form-sec-title">1. Data Pesanan &amp; Kalkulator Plano Cetak</h4>
      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Nomor Job Order (PP) <span class="text-danger">*</span></label>
          <input
            type="text"
            v-model="form.no_pp"
            class="form-control"
            required
            placeholder="Contoh: PP-2026-0850"
          />
        </div>
        <div class="form-group span-2">
          <label>Nama Produk / Pelanggan <span class="text-danger">*</span></label>
          <input
            type="text"
            v-model="form.produk_nama"
            class="form-control"
            required
            placeholder="Contoh: Kemasan Obat Herbal 150ml (Box Luar)"
          />
        </div>
      </div>

      <div class="grid grid-3 gap-1 mb-1">
        <div class="form-group">
          <label>Target Oplaag Pesanan (Pcs) <span class="text-danger">*</span></label>
          <input
            type="number"
            v-model.number="form.oplaag_pcs"
            class="form-control"
            required
            min="100"
            step="1000"
            placeholder="Contoh: 100000"
          />
          <small class="text-muted">Total box pesanan pelanggan</small>
        </div>
        <div class="form-group">
          <label>1 Plano Isi Berapa (Up per Plano) <span class="text-danger">*</span></label>
          <input
            type="number"
            v-model.number="form.up_per_plano"
            class="form-control"
            required
            min="1"
            max="200"
            placeholder="Contoh: 4"
          />
          <small class="text-muted">Jumlah box dalam 1 lembar cetak</small>
        </div>
        <div class="form-group">
          <label>Insheet / Cadangan Waste Cetak (%)</label>
          <input
            type="number"
            step="0.5"
            v-model.number="form.insheet_percent"
            class="form-control"
            min="0"
            max="15"
            placeholder="Contoh: 2.5"
          />
          <small class="text-muted">Toleransi afval &amp; pas warna druk</small>
        </div>
      </div>

      <!-- Live Calculation Card: Kebutuhan Plano Cetak -->
      <div class="card mb-1-5 p-1" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
        <div class="d-flex justify-between align-center mb-05">
          <span style="font-weight: 700; font-size: 0.88rem; color: #334155;">
            ⚡ Hasil Kalkulasi Lembar Plano (PPIC Engine)
          </span>
          <span class="badge badge-primary">Satuan Kertas: Plano</span>
        </div>
        <div class="grid grid-3 gap-1">
          <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 0.75rem;">
            <div class="text-muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">PLANO NETTO</div>
            <div style="font-size: 1.35rem; font-weight: 800; color: #1e40af; margin-top: 2px;">
              {{ formatNumber(calcPlano.nettoPlano) }} <span style="font-size: 0.8rem; font-weight: 500;">plano</span>
            </div>
            <small class="text-muted">{{ formatNumber(form.oplaag_pcs) }} pcs &divide; {{ form.up_per_plano }} up</small>
          </div>
          <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 0.75rem;">
            <div class="text-muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">INSHEET ({{ form.insheet_percent }}%)</div>
            <div style="font-size: 1.35rem; font-weight: 800; color: #b45309; margin-top: 2px;">
              +{{ formatNumber(calcPlano.insheetPlano) }} <span style="font-size: 0.8rem; font-weight: 500;">plano</span>
            </div>
            <small class="text-muted">Cadangan seting mesin offset</small>
          </div>
          <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 0.75rem;">
            <div class="text-muted" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">TOTAL PLANO DICETAK (BRUTO)</div>
            <div style="font-size: 1.35rem; font-weight: 800; color: #059669; margin-top: 2px;">
              {{ formatNumber(calcPlano.totalPlano) }} <span style="font-size: 0.8rem; font-weight: 500;">plano</span>
            </div>
            <small class="text-muted">Wajib disiapkan dari gudang kertas</small>
          </div>
        </div>
      </div>

      <!-- Section 2: Jadwal Masuk Mesin & Perhitungan Jam-Menit -->
      <h4 class="form-sec-title">2. Alokasi Masuk Mesin &amp; Perhitungan Durasi (Presisi Jam &amp; Menit)</h4>
      <div class="grid grid-2 gap-1 mb-1">
        <div class="form-group">
          <label>Tanggal Mulai Masuk Mesin Cetak <span class="text-danger">*</span></label>
          <input type="date" v-model="form.start_date" class="form-control" required />
        </div>
        <div class="form-group">
          <label>Jam &amp; Menit Masuk Mesin (Shift Mulai) <span class="text-danger">*</span></label>
          <input type="time" v-model="form.start_time" class="form-control font-bold" required />
          <small class="text-muted">Waktu mulai cetak di mesin (WIB)</small>
        </div>
      </div>

      <!-- Alokasi Mesin per Tahapan Produksi -->
      <div class="stages-box mb-1-5">
        <div class="stage-item-card" :class="{ disabled: !form.stages[0].enabled }">
          <div class="d-flex justify-between align-center mb-05">
            <div class="d-flex align-center gap-05">
              <span class="badge badge-primary">1. CETAK OFFSET (PLANO)</span>
              <span class="badge badge-success">Wajib Masuk</span>
            </div>
            <span class="text-muted" style="font-size: 0.8rem;">
              Est. Durasi: <b class="text-primary">{{ stageEstimations[0]?.durationDisplay }}</b>
            </span>
          </div>
          <div class="grid grid-2 gap-1 align-center">
            <select v-model="form.stages[0].mesin_id" class="form-control" required>
              <option v-for="m in getMachinesByStage('cetak')" :key="m.id" :value="m.id">
                {{ m.nama }} (Speed: {{ formatNumber(m.speed_jph) }} plano/jam)
              </option>
            </select>
            <div style="font-size: 0.82rem; background: #fff; padding: 0.4rem 0.8rem; border-radius: 6px; border: 1px solid #e2e8f0;">
              Jadwal: <b>{{ stageEstimations[0]?.start }}</b> &rarr; <b>{{ stageEstimations[0]?.end }}</b>
            </div>
          </div>
        </div>

        <div class="stage-item-card" :class="{ disabled: !form.stages[1].enabled }">
          <div class="d-flex justify-between align-center mb-05">
            <label class="d-flex align-center gap-05" style="cursor: pointer; margin: 0;">
              <input type="checkbox" v-model="form.stages[1].enabled" />
              <span class="badge" :class="form.stages[1].enabled ? 'badge-warning' : 'badge-secondary'">2. COMPLETING (HOT FOIL / UV)</span>
            </label>
            <span v-if="form.stages[1].enabled" class="text-muted" style="font-size: 0.8rem;">
              Est. Durasi: <b class="text-warning">{{ stageEstimations[1]?.durationDisplay }}</b>
            </span>
            <span v-else class="text-muted" style="font-size: 0.78rem;">(Tahap Dilewati)</span>
          </div>
          <div v-if="form.stages[1].enabled" class="grid grid-2 gap-1 align-center">
            <select v-model="form.stages[1].mesin_id" class="form-control">
              <option v-for="m in getMachinesByStage('completing')" :key="m.id" :value="m.id">
                {{ m.nama }} (Speed: {{ formatNumber(m.speed_jph) }} plano/jam)
              </option>
            </select>
            <div style="font-size: 0.82rem; background: #fff; padding: 0.4rem 0.8rem; border-radius: 6px; border: 1px solid #e2e8f0;">
              Jadwal: <b>{{ stageEstimations[1]?.start }}</b> &rarr; <b>{{ stageEstimations[1]?.end }}</b>
            </div>
          </div>
        </div>

        <div class="stage-item-card" :class="{ disabled: !form.stages[2].enabled }">
          <div class="d-flex justify-between align-center mb-05">
            <label class="d-flex align-center gap-05" style="cursor: pointer; margin: 0;">
              <input type="checkbox" v-model="form.stages[2].enabled" />
              <span class="badge" :class="form.stages[2].enabled ? 'badge-info' : 'badge-secondary'">3. CABUT (DIE-CUTTING POND)</span>
            </label>
            <span v-if="form.stages[2].enabled" class="text-muted" style="font-size: 0.8rem;">
              Est. Durasi: <b class="text-info">{{ stageEstimations[2]?.durationDisplay }}</b>
            </span>
            <span v-else class="text-muted" style="font-size: 0.78rem;">(Tahap Dilewati)</span>
          </div>
          <div v-if="form.stages[2].enabled" class="grid grid-2 gap-1 align-center">
            <select v-model="form.stages[2].mesin_id" class="form-control">
              <option v-for="m in getMachinesByStage('cabut')" :key="m.id" :value="m.id">
                {{ m.nama }} (Speed: {{ formatNumber(m.speed_jph) }} plano/jam)
              </option>
            </select>
            <div style="font-size: 0.82rem; background: #fff; padding: 0.4rem 0.8rem; border-radius: 6px; border: 1px solid #e2e8f0;">
              Jadwal: <b>{{ stageEstimations[2]?.start }}</b> &rarr; <b>{{ stageEstimations[2]?.end }}</b>
            </div>
          </div>
        </div>

        <div class="stage-item-card" :class="{ disabled: !form.stages[3].enabled }">
          <div class="d-flex justify-between align-center mb-05">
            <label class="d-flex align-center gap-05" style="cursor: pointer; margin: 0;">
              <input type="checkbox" v-model="form.stages[3].enabled" />
              <span class="badge" :class="form.stages[3].enabled ? 'badge-success' : 'badge-secondary'">4. SORTIR (QC &amp; OPTICAL PCS)</span>
            </label>
            <span v-if="form.stages[3].enabled" class="text-muted" style="font-size: 0.8rem;">
              Est. Durasi: <b class="text-success">{{ stageEstimations[3]?.durationDisplay }}</b>
            </span>
            <span v-else class="text-muted" style="font-size: 0.78rem;">(Tahap Dilewati)</span>
          </div>
          <div v-if="form.stages[3].enabled" class="grid grid-2 gap-1 align-center">
            <select v-model="form.stages[3].mesin_id" class="form-control">
              <option v-for="m in getMachinesByStage('sortir')" :key="m.id" :value="m.id">
                {{ m.nama }} (Speed: {{ formatNumber(m.speed_jph) }} pcs/jam)
              </option>
            </select>
            <div style="font-size: 0.82rem; background: #fff; padding: 0.4rem 0.8rem; border-radius: 6px; border: 1px solid #e2e8f0;">
              Jadwal: <b>{{ stageEstimations[3]?.start }}</b> &rarr; <b>{{ stageEstimations[3]?.end }}</b>
            </div>
          </div>
        </div>

        <div class="stage-item-card" :class="{ disabled: !form.stages[4].enabled }">
          <div class="d-flex justify-between align-center mb-05">
            <label class="d-flex align-center gap-05" style="cursor: pointer; margin: 0;">
              <input type="checkbox" v-model="form.stages[4].enabled" />
              <span class="badge" :class="form.stages[4].enabled ? 'badge-primary' : 'badge-secondary'">5. LIPAT (FOLDER GLUER PCS)</span>
            </label>
            <span v-if="form.stages[4].enabled" class="text-muted" style="font-size: 0.8rem;">
              Est. Durasi: <b class="text-primary">{{ stageEstimations[4]?.durationDisplay }}</b>
            </span>
            <span v-else class="text-muted" style="font-size: 0.78rem;">(Tahap Dilewati)</span>
          </div>
          <div v-if="form.stages[4].enabled" class="grid grid-2 gap-1 align-center">
            <select v-model="form.stages[4].mesin_id" class="form-control">
              <option v-for="m in getMachinesByStage('lipat')" :key="m.id" :value="m.id">
                {{ m.nama }} (Speed: {{ formatNumber(m.speed_jph) }} pcs/jam)
              </option>
            </select>
            <div style="font-size: 0.82rem; background: #fff; padding: 0.4rem 0.8rem; border-radius: 6px; border: 1px solid #e2e8f0;">
              Jadwal: <b>{{ stageEstimations[4]?.start }}</b> &rarr; <b>{{ stageEstimations[4]?.end }}</b>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Batas Pengiriman & Muatan Kirim -->
      <h4 class="form-sec-title">3. Batas Pengiriman (Deadline) &amp; Rincian Muatan Logistik</h4>
      <div class="grid grid-2 gap-1 mb-1">
        <div class="form-group">
          <label>Batas Hari / Tanggal Pengiriman <span class="text-danger">*</span></label>
          <input type="date" v-model="form.deadline_date" class="form-control" required />
          <small class="text-muted">Hari pengiriman yang diminta pelanggan</small>
        </div>
        <div class="form-group">
          <label>Jam Maksimal Pengiriman (Deadline Hour) <span class="text-danger">*</span></label>
          <input type="time" v-model="form.deadline_time" class="form-control font-bold" required />
          <small class="text-muted">Waktu maksimal tiba / muat di ekspedisi (WIB)</small>
        </div>
      </div>

      <div class="grid grid-4 gap-08 mb-1">
        <div class="form-group">
          <label>Isi per Koli (Box Karton)</label>
          <input type="number" v-model.number="form.pcs_per_koli" class="form-control" min="1" placeholder="500" />
          <small class="text-muted">Pcs dalam 1 koli dus</small>
        </div>
        <div class="form-group">
          <label>Muatan Koli per Pallet</label>
          <input type="number" v-model.number="form.koli_per_pallet" class="form-control" min="1" placeholder="20" />
          <small class="text-muted">Jumlah koli di 1 pallet</small>
        </div>
        <div class="form-group">
          <label>Berat per Box (Gram)</label>
          <input type="number" step="0.5" v-model.number="form.berat_pcs_gram" class="form-control" min="1" placeholder="25" />
          <small class="text-muted">Gramatur box jadi</small>
        </div>
        <div class="form-group">
          <label>Pilihan Armada Pengiriman</label>
          <select v-model="form.armada_id" class="form-control">
            <option v-for="a in masterArmadaLogistik" :key="a.id" :value="a.id">
              {{ a.nama }} (Kapasitas: {{ a.kapasitas_pallet }} Pallet)
            </option>
          </select>
        </div>
      </div>

      <!-- Live Logistics Evaluation Card -->
      <div class="card mb-1 p-1" :style="deliveryEvaluation.isSafe ? 'background: #f0fdf4; border: 1px solid #86efac;' : 'background: #fef2f2; border: 1px solid #fca5a5;'">
        <div class="d-flex justify-between align-center flex-wrap gap-05 mb-08">
          <span style="font-weight: 800; font-size: 0.95rem;" :class="deliveryEvaluation.isSafe ? 'text-success' : 'text-danger'">
            {{ deliveryEvaluation.isSafe ? '🟢 EVALUASI PENGIRIMAN: TEPAT WAKTU & AMAN' : '🚨 EVALUASI PENGIRIMAN: POTENSI TERLAMBAT!' }}
          </span>
          <span class="badge" :class="deliveryEvaluation.isSafe ? 'badge-success' : 'badge-danger'">
            {{ deliveryEvaluation.marginText }}
          </span>
        </div>

        <div class="grid grid-4 gap-08" style="background: #ffffff; padding: 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0;">
          <div>
            <div class="text-muted" style="font-size: 0.75rem; font-weight: 600;">TOTAL MUATAN</div>
            <div style="font-size: 1.1rem; font-weight: 800; color: #1e293b;">
              {{ formatNumber(form.oplaag_pcs) }} <small>pcs</small>
            </div>
            <small class="text-muted"><b>{{ formatNumber(logisticsCalc.totalKoli) }} Koli</b> ({{ formatNumber(logisticsCalc.totalPallet) }} Pallet)</small>
          </div>

          <div>
            <div class="text-muted" style="font-size: 0.75rem; font-weight: 600;">EST. BERAT TOTAL</div>
            <div style="font-size: 1.1rem; font-weight: 800; color: #1e293b;">
              {{ (logisticsCalc.totalBeratKg / 1000).toFixed(2) }} <small>Ton</small>
            </div>
            <small class="text-muted">({{ formatNumber(logisticsCalc.totalBeratKg) }} kg)</small>
          </div>

          <div>
            <div class="text-muted" style="font-size: 0.75rem; font-weight: 600;">ARMADA &amp; RITASE</div>
            <div style="font-size: 1.1rem; font-weight: 800; color: #4338ca;">
              {{ logisticsCalc.ritase }} Ritase
            </div>
            <small class="text-muted">{{ logisticsCalc.armada.nama }}</small>
          </div>

          <div>
            <div class="text-muted" style="font-size: 0.75rem; font-weight: 600;">WAKTU TIBA VS DEADLINE</div>
            <div style="font-size: 0.95rem; font-weight: 800;" :class="deliveryEvaluation.isSafe ? 'text-success' : 'text-danger'">
              Tiba: {{ deliveryEvaluation.estArrival }} WIB
            </div>
            <small class="text-muted">Deadline: <b>{{ form.deadline_date }} {{ form.deadline_time }} WIB</b></small>
          </div>
        </div>
      </div>

      <div class="form-group mb-1">
        <label>Catatan Khusus PPIC &amp; Instruksi Khusus ke Divisi</label>
        <textarea v-model="form.catatan" class="form-control" rows="2" placeholder="Tuliskan prioritas pallet kirim, alamat penerima, spesifikasi karton atau ketentuan khusus..."></textarea>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex justify-between align-center mt-1 pt-1" style="border-top: 1px solid #e2e8f0;">
        <router-link to="/ppic/jadwal" class="btn btn-secondary">
          Batal
        </router-link>
        <button type="submit" class="btn btn-primary" style="padding: 0.65rem 2rem; font-weight: 800;">
          🚀 Simpan Job Order &amp; Rilis Jadwal Mesin
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  masterMesinPPIC,
  masterArmadaLogistik,
  calculatePlanoFromOplaag,
  calculateJobLogistics,
  calculateEstimatedMinutes,
  addMinutesToDateTime,
  formatMinutesToHuman,
  diffMinutes,
  addPpicJobOrderSchedule,
  CURRENT_SYSTEM_DATE
} from '../../data/schedulePPIC'
import { showAlert } from '../../view/modal/modalService'

const router = useRouter()

const form = ref({
  no_pp: '',
  produk_nama: '',
  oplaag_pcs: 100000,
  up_per_plano: 4,
  insheet_percent: 2.0,
  start_date: CURRENT_SYSTEM_DATE,
  start_time: '08:00',
  deadline_date: '2026-11-03',
  deadline_time: '16:00',
  pcs_per_koli: 500,
  koli_per_pallet: 20,
  berat_pcs_gram: 25,
  armada_id: 'cdd',
  lead_time_kirim_jam: 3,
  catatan: '',
  stages: [
    { stage: 'cetak', mesin_id: 'sm23', enabled: true },
    { stage: 'completing', mesin_id: 'hsf1', enabled: true },
    { stage: 'cabut', mesin_id: 'ms1', enabled: true },
    { stage: 'sortir', mesin_id: 'sortir_fs500', enabled: true },
    { stage: 'lipat', mesin_id: 'gluer_herui', enabled: true }
  ]
})

const getMachinesByStage = (stage) => {
  return masterMesinPPIC.filter(m => m.stage === stage)
}

const calcPlano = computed(() => {
  return calculatePlanoFromOplaag(form.value.oplaag_pcs, form.value.up_per_plano, form.value.insheet_percent)
})

const logisticsCalc = computed(() => {
  return calculateJobLogistics({
    oplaag_pcs: form.value.oplaag_pcs,
    pcs_per_koli: form.value.pcs_per_koli,
    koli_per_pallet: form.value.koli_per_pallet,
    berat_pcs_gram: form.value.berat_pcs_gram,
    armada_id: form.value.armada_id,
    lead_time_kirim_jam: form.value.lead_time_kirim_jam
  })
})

const stageEstimations = computed(() => {
  let currentDt = `${form.value.start_date} ${form.value.start_time}`
  const totalPlano = calcPlano.value.totalPlano
  const oplaag = Number(form.value.oplaag_pcs) || 0

  return form.value.stages.map((st) => {
    if (!st.enabled) {
      return { durationDisplay: '-', start: '-', end: '-' }
    }
    const machine = masterMesinPPIC.find(m => m.id === st.mesin_id)
    if (!machine) return { durationDisplay: '-', start: '-', end: '-' }

    const isPlano = machine.unit === 'plano'
    const qty = isPlano ? totalPlano : oplaag
    const minutes = calculateEstimatedMinutes(qty, machine.speed_jph)
    const startStr = currentDt
    const endStr = addMinutesToDateTime(currentDt, minutes)

    // Jeda buffer antar mesin 20 menit
    currentDt = addMinutesToDateTime(endStr, 20)

    return {
      durationMinutes: minutes,
      durationDisplay: formatMinutesToHuman(minutes),
      start: startStr,
      end: endStr
    }
  })
})

const lastStageEndDateTime = computed(() => {
  const activeEstimations = stageEstimations.value.filter(s => s.end !== '-')
  if (activeEstimations.length === 0) return `${form.value.start_date} ${form.value.start_time}`
  return activeEstimations[activeEstimations.length - 1].end
})

const deliveryEvaluation = computed(() => {
  const prodEnd = lastStageEndDateTime.value
  // Loading 60 menit + waktu perjalanan ekspedisi
  const loadingTimeMinutes = 60
  const travelTimeMinutes = (form.value.lead_time_kirim_jam || 3) * 60
  const estArrival = addMinutesToDateTime(prodEnd, loadingTimeMinutes + travelTimeMinutes)
  const deadline = `${form.value.deadline_date} ${form.value.deadline_time || '17:00'}`

  const marginMinutes = diffMinutes(estArrival, deadline)
  const isSafe = marginMinutes >= 0

  let marginText = ''
  if (isSafe) {
    marginText = `Selesai ${formatMinutesToHuman(marginMinutes)} sebelum deadline`
  } else {
    marginText = `Terlambat ${formatMinutesToHuman(Math.abs(marginMinutes))} dari deadline!`
  }

  return {
    prodEnd,
    estArrival,
    deadline,
    marginMinutes,
    isSafe,
    marginText
  }
})

const formatNumber = (num) => {
  if (num === null || num === undefined) return '0'
  return Number(num).toLocaleString('id-ID')
}

const handleSubmit = async () => {
  if (!form.value.no_pp.trim()) {
    await showAlert('Nomor Job Order (PP) wajib diisi!', { title: 'Peringatan', type: 'warning' })
    return
  }

  const result = addPpicJobOrderSchedule({
    no_pp: form.value.no_pp,
    produk_nama: form.value.produk_nama,
    oplaag_pcs: form.value.oplaag_pcs,
    up_per_plano: form.value.up_per_plano,
    insheet_percent: form.value.insheet_percent,
    start_datetime: `${form.value.start_date} ${form.value.start_time}`,
    deadline_date: form.value.deadline_date,
    deadline_time: form.value.deadline_time,
    pcs_per_koli: form.value.pcs_per_koli,
    koli_per_pallet: form.value.koli_per_pallet,
    berat_pcs_gram: form.value.berat_pcs_gram,
    armada_id: form.value.armada_id,
    lead_time_kirim_jam: form.value.lead_time_kirim_jam,
    stages: form.value.stages
  })

  const msg =
    `Job Order ${form.value.no_pp} berhasil dirilis ke dalam sistem produksi!\n` +
    `• Total Kebutuhan Plano: ${formatNumber(calcPlano.value.totalPlano)} plano (${form.value.up_per_plano} Up)\n` +
    `• Jam Mulai Cetak: ${form.value.start_date} ${form.value.start_time} WIB\n` +
    `• Muatan Logistik: ${formatNumber(logisticsCalc.value.totalKoli)} Koli (${formatNumber(logisticsCalc.value.totalPallet)} Pallet) &bull; ${logisticsCalc.value.ritase} Ritase ${logisticsCalc.value.armada.nama}\n` +
    `• Batas Pengiriman: ${form.value.deadline_date} ${form.value.deadline_time} WIB\n` +
    `• Evaluasi: ${deliveryEvaluation.value.marginText}`

  await showAlert(msg, {
    title: 'Work Order & Jadwal Mesin Berhasil Dirilis',
    type: 'success'
  })

  router.push('/ppic/jadwal')
}
</script>

<style scoped>
.form-sec-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  margin: 1.25rem 0 0.6rem;
  padding-bottom: 4px;
  border-bottom: 2px solid #e2e8f0;
}
.form-sec-title:first-of-type {
  margin-top: 0;
}
.stages-box {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}
.stage-item-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  transition: all 0.2s ease;
}
.stage-item-card.disabled {
  opacity: 0.6;
  background: #f1f5f9;
}
.mb-1-5 {
  margin-bottom: 1.5rem;
}
</style>
