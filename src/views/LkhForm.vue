<template>
  <div class="card lkh-form-page">
    <!-- Header Page -->
    <div class="d-flex justify-between align-center mb-1 flex-wrap gap-05">
      <div>
        <h2 class="card-title" style="margin:0; border:none; padding:0;">
          <span>✍️</span> {{ isEdit ? 'Edit LKH' : 'Form LKH' }}
        </h2>
      </div>
      <div class="d-flex gap-05 align-center">
        <button type="button" class="btn btn-secondary btn-sm" @click="toggleAllSections">
          {{ areAllOpen ? '⊟ Ciutkan Semua' : '⊞ Buka Semua' }}
        </button>
      </div>
    </div>

    <!-- Main Form -->
    <form @submit.prevent="saveLkh">

      <!-- SECTION 1: DATA SHIFT, MESIN & OPERATOR (GLOBAL SHIFT) -->
      <Section1ShiftMesin
        :form="form"
        :master="master"
        :is-open="openSections[1]"
        :is-complete="isSectionComplete(1)"
        :has-prefilled-history="hasPrefilledHistory"
        :get-mesin-nama="getMesinNama"
        @toggle="toggleSection(1)"
        @next="goToNextSection(1)"
        @shift-change="onShiftChange"
      />

      <!-- SECTION 2: JAM KERJA SHIFT (START & FINISH GLOBAL) -->
      <Section2JamKerja
        :form="form"
        :is-open="openSections[2]"
        :is-complete="isSectionComplete(2)"
        :shift-raw-duration-jam="shiftRawDurationJam"
        :time-warning="timeWarning"
        :format-time-snippet="formatTimeSnippet"
        @toggle="toggleSection(2)"
        @next="goToNextSection(2)"
        @shift-time-change="onShiftTimeChange"
        @lembur-change="onLemburChange"
      />

      <!-- SECTION 3: PERSIAPAN & 5S (GLOBAL SHIFT) -->
      <Section3Persiapan5S
        :form="form"
        :is-open="openSections[3]"
        :is-complete="isSectionComplete(3)"
        @toggle="toggleSection(3)"
        @next="goToNextSection(3)"
      />

      <!-- SECTION 4: DAFTAR JOB ORDER (PP), HASIL SORTIR & DOWNTIME (MULTI-PP) -->
      <Section4JobOrder
        :form="form"
        :master="master"
        :is-open="openSections[4]"
        :is-complete="isSectionComplete(4)"
        :per-pp-calculated="perPpCalculated"
        :summary-calculated="summaryCalculated"
        :downtime-shortcuts="downtimeShortcuts"
        :get-pp-label="getPpLabel"
        :get-pp-obj="getPpObj"
        :format-time-snippet="formatTimeSnippet"
        :is-jam-pindah-valid="isJamPindahValid"
        :get-dt-cat="getDtCat"
        :get-dt-waste="getDtWaste"
        :get-category-badge-style="getCategoryBadgeStyle"
        @toggle="toggleSection(4)"
        @next="goToNextSection(4)"
        @add-pp="addPpItem"
        @remove-pp="removePpItem"
        @pp-change="onPpChange"
        @open-request-pp="openRequestPpModal"
        @result-change="onPpResultChange"
        @open-modal-cacat="openModalCacatForPp"
        @sync-total-layar="syncTotalLayar"
        @manual-total-layar="onTotalLayarManualInput"
        @quick-add-dt="quickAddDowntime"
        @add-dt="addDowntime"
      />

      <!-- SECTION 5: CATATAN MASALAH & PIC (OPSIONAL) -->
      <Section5CatatanMasalah
        :form="form"
        :is-open="openSections[5]"
        :is-complete="isSectionComplete(5)"
        @toggle="toggleSection(5)"
        @next="goToNextSection(5)"
      />

      <!-- SECTION 6: RINGKASAN OTOMATIS & KALKULASI OEE (SUBMIT BUTTON) -->
      <Section6RingkasanOee
        :form="form"
        :is-open="openSections[6]"
        :is-complete="isSectionComplete(6)"
        :per-pp-calculated="perPpCalculated"
        :summary-calculated="summaryCalculated"
        :saving="saving"
        :is-edit="isEdit"
        :get-pp-label="getPpLabel"
        :oee-color-class="oeeColorClass"
        @toggle="toggleSection(6)"
      />

    </form>
  </div>

  <!-- MODAL DETAIL REJECT WIP (PER PP ITEM) -->
  <ModalDetailCacat
    :is-open="showModalCacat"
    :active-pp-index="activePpIndexForCacat"
    :current-pp-obj="currentModalPpObj"
    :modal-detail-cacat="modalDetailCacat"
    :master-jenis-cacat="master.jenis_cacat"
    :defect-shortcuts="defectShortcuts"
    :modal-cacat-total="modalCacatTotal"
    :is-top-defect="isTopDefect"
    @close="showModalCacat = false"
    @apply="applyCacatModal"
    @reset="resetCacatModal"
    @focus-defect="focusDefectInput"
  />

  <!-- MODAL REQUEST NOMOR PP BARU -->
  <div v-if="showModalRequestPp" class="modal-overlay" @click.self="showModalRequestPp = false">
    <div class="modal-content" style="max-width: 480px;">
      <div class="d-flex justify-between align-center mb-1 pb-05 border-bottom">
        <h3 style="margin: 0; font-size: 1.1rem; color: #1e40af;">
          <span>📦</span> Request Nomor PP Baru
        </h3>
        <button type="button" class="btn btn-secondary btn-sm" @click="showModalRequestPp = false">✕</button>
      </div>

      <p class="text-muted" style="font-size: 0.82rem; margin-top: -0.25rem; margin-bottom: 1rem;">
        Isi nomor PP dan nama produk yang sedang disortir. PP akan langsung otomatis terpilih pada form LKH dengan status <b>Request (req)</b>. Perhitungan OEE dan persentase progress akan aktif setelah Admin melakukan ACC.
      </p>

      <form @submit.prevent="submitRequestPp">
        <div class="form-group mb-1">
          <label>Nomor PP <span class="text-danger">*</span></label>
          <input 
            type="text" 
            v-model="formRequestPp.no_pp" 
            class="form-control" 
            required 
            placeholder="Contoh: PP/2026/045" 
            autofocus 
          />
        </div>

        <div class="form-group mb-1">
          <label>Nama Produk <span class="text-danger">*</span></label>
          <input 
            type="text" 
            v-model="formRequestPp.produk_nama" 
            class="form-control" 
            required 
            placeholder="Contoh: Surya Pro 16" 
          />
        </div>

        <div class="form-group mb-1">
          <label>Famili / Kategori Produk <span class="text-muted">(Opsional)</span></label>
          <input 
            type="text" 
            v-model="formRequestPp.famili" 
            class="form-control" 
            placeholder="Contoh: HL, SKT, dll." 
          />
        </div>

        <div class="d-flex justify-between gap-05 mt-15 pt-05 border-top">
          <button type="button" class="btn btn-secondary" @click="showModalRequestPp = false">Batal</button>
          <button type="submit" class="btn btn-primary font-bold" :disabled="savingRequestPp">
            {{ savingRequestPp ? 'Menyimpan...' : 'Kirim Request & Pilih PP' }}
          </button>
        </div>
      </form>
    </div>
  </div>

</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { apiFetch, authState } from '../utils/api'

// Sub-components per Section
import Section1ShiftMesin from './lkh/Section1ShiftMesin.vue'
import Section2JamKerja from './lkh/Section2JamKerja.vue'
import Section3Persiapan5S from './lkh/Section3Persiapan5S.vue'
import Section4JobOrder from './lkh/Section4JobOrder.vue'
import Section5CatatanMasalah from './lkh/Section5CatatanMasalah.vue'
import Section6RingkasanOee from './lkh/Section6RingkasanOee.vue'
import ModalDetailCacat from './lkh/ModalDetailCacat.vue'

const router = useRouter()
const route = useRoute()
const isEdit = computed(() => !!route.params.id)

const pad = (n) => n.toString().padStart(2, '0')
const getLocalDate = () => {
  const d = new Date()
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

// 6 Section Titles & Short Labels
const sectionTitles = {
  1: 'Data Shift, Mesin & Operator',
  2: 'Jam Kerja Shift',
  3: 'Persiapan & 5S (Shift)',
  4: 'Job Order (PP), Hasil & Downtime',
  5: 'Catatan Masalah & PIC',
  6: 'Ringkasan & Kalkulasi OEE'
}

const sectionShortLabels = {
  1: '1. Shift',
  2: '2. Jam',
  3: '3. 5S',
  4: '4. PP & Hasil',
  5: '5. Masalah',
  6: '6. Simpan'
}

// Accordion State
const openSections = ref({
  1: true,
  2: true,
  3: true,
  4: true,
  5: true,
  6: true
})

const activeSection = ref(1)
const areAllOpen = computed(() => Object.values(openSections.value).every(Boolean))

const toggleSection = (step) => {
  openSections.value[step] = !openSections.value[step]
  if (openSections.value[step]) {
    activeSection.value = step
  }
}

const toggleAllSections = () => {
  const nextState = !areAllOpen.value
  for (let i = 1; i <= 6; i++) {
    openSections.value[i] = nextState
  }
}

const jumpToSection = (step) => {
  openSections.value[step] = true
  activeSection.value = step
}

const goToNextSection = (currentStep) => {
  const next = currentStep + 1
  if (next <= 6) {
    openSections.value[next] = true
    activeSection.value = next
  }
}

// Sticky Summary State
const isStickyCollapsed = ref(false)

const scrollToSaveSection = () => {
  openSections.value[6] = true
  activeSection.value = 6
  nextTick(() => {
    const el = document.getElementById('section-6-card')
    if (el) el.scrollIntoView({ behavior: 'smooth' })
  })
}

// Master Data
const master = ref({ 
  pp: [], 
  shift: [], 
  mesin: [], 
  produk: [], 
  jenis_cacat: [], 
  kode_downtime: [] 
})

// Factory helper for a new PP Item
const createNewPpItem = (defaultJamMulai = '') => ({
  pp_id: '',
  tahap_sortir: 'S1',
  jam_mulai: defaultJamMulai,
  good: 0,
  reject: 0,
  setup_reject: 0,
  total_layar: 0,
  isTotalLayarManual: false,
  downtime: [],
  detailCacat: {},
  akumulasiPp: { total_tersortir: 0, total_s1: 0, total_s2: 0, total_s3: 0, total_semua: 0 }
})

// Form Structure
const form = ref({
  tanggal: getLocalDate(),
  shift: '',
  mesin_id: '',
  operator: '',
  pic: authState.value.username || '',
  start: '',
  finish: '',
  lembur_menit: 0,
  keterangan_lembur: '',
  setting_menit: 0,
  '5s_menit': 0,
  problem: '',
  root_cause: '',
  tindakan_sementara: '',
  tindakan_perbaikan: '',
  pp_items: [createNewPpItem()]
})

const hasPrefilledHistory = ref(false)
const saving = ref(false)
const timeWarning = ref('')

// Modal Reject WIP State
const showModalCacat = ref(false)
const activePpIndexForCacat = ref(0)
const modalDetailCacat = ref({})

// Modal Request PP State
const showModalRequestPp = ref(false)
const targetPpIndexForRequest = ref(0)
const formRequestPp = ref({ no_pp: '', produk_nama: '', famili: '' })
const savingRequestPp = ref(false)

const openRequestPpModal = (idx) => {
  targetPpIndexForRequest.value = idx
  formRequestPp.value = { no_pp: '', produk_nama: '', famili: '' }
  showModalRequestPp.value = true
}

const submitRequestPp = async () => {
  const no_pp = (formRequestPp.value.no_pp || '').trim()
  const produk_nama = (formRequestPp.value.produk_nama || '').trim()
  const famili = (formRequestPp.value.famili || '').trim()

  if (!no_pp || !produk_nama) {
    alert('Nomor PP dan Nama Produk wajib diisi!')
    return
  }

  savingRequestPp.value = true
  try {
    const res = await apiFetch('master.php?action=request_pp', {
      method: 'POST',
      body: JSON.stringify({ no_pp, produk_nama, famili })
    })

    if (res && res.pp) {
      // Perbarui master.pp jika belum ada
      const idx = master.value.pp.findIndex(p => p.id === res.pp.id)
      if (idx >= 0) {
        master.value.pp[idx] = res.pp
      } else {
        master.value.pp.unshift(res.pp)
      }

      // Otomatis pilih PP ini di item form yang aktif
      const targetIdx = targetPpIndexForRequest.value
      if (form.value.pp_items[targetIdx]) {
        form.value.pp_items[targetIdx].pp_id = res.pp.id
        onPpChange(targetIdx)
      }

      showModalRequestPp.value = false
      alert(res.message || `Request PP '${no_pp}' berhasil! Nomor PP telah dipilih otomatis.`)
    }
  } catch (err) {
    alert('Gagal request PP: ' + (err.message || 'Terjadi kesalahan sistem.'))
  } finally {
    savingRequestPp.value = false
  }
}

// Shortcuts definitions
const downtimeShortcuts = [
  { kode: 'STEL', label: 'Stel Mesin' },
  { kode: 'DEFECT', label: 'Defect' },
  { kode: 'BDEV', label: 'Ban Delivery' },
  { kode: 'T.BAHAN', label: 'Tunggu Bahan' },
  { kode: 'SERVC', label: 'Maintenance' }
]

const defectShortcuts = [
  { nama: 'NODA' },
  { nama: 'WARNA' },
  { nama: 'BLOBOR' },
  { nama: 'BARET' },
  { nama: 'BRONTOK' }
]

const isTopDefect = (nama) => defectShortcuts.some(d => d.nama.toLowerCase() === (nama || '').toLowerCase())

// Helpers to lookup PP object and label
const getPpObj = (ppId) => master.value.pp.find(p => p.id === ppId)
const getPpLabel = (ppId) => {
  const p = getPpObj(ppId)
  return p ? `${p.no_pp} - ${p.produk_nama}` : ''
}
const getMesinNama = (id) => {
  const m = master.value.mesin.find(x => x.id === id)
  return m ? m.nama : ''
}

const formatTimeSnippet = (isoStr) => {
  if (!isoStr) return '--:--'
  try {
    const d = new Date(isoStr)
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`
  } catch (e) {
    return '--:--'
  }
}

// Category and Waste helpers for downtime
const getDtWaste = (kodeId) => {
  const found = master.value.kode_downtime.find(k => k.id === kodeId)
  return found?.jenis_waste || ''
}

const getDtCat = (kodeId) => {
  const found = master.value.kode_downtime.find(k => k.id === kodeId)
  return found?.kategori || ''
}

const getCategoryBadgeStyle = (cat) => {
  const c = (cat || '').toLowerCase()
  if (c === 'method' || c === 'metode') return 'background: #ede9fe; color: #6d28d9; border: 1px solid #ddd6fe;'
  if (c === 'machine' || c === 'mesin') return 'background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;'
  if (c === 'material') return 'background: #fef3c7; color: #b45309; border: 1px solid #fde68a;'
  if (c === 'man' || c === 'manusia') return 'background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;'
  if (c === 'environment' || c === 'lingkungan') return 'background: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4;'
  return 'background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;'
}

// Downtime handlers
const addDowntime = (item) => {
  if (!item.downtime) item.downtime = []
  item.downtime.push({
    kode_id: '',
    menit: 0,
    keterangan: ''
  })
}

const quickAddDowntime = (item, kode) => {
  if (!item.downtime) item.downtime = []
  const found = master.value.kode_downtime.find(k => (k.kode || '').toUpperCase() === kode.toUpperCase())
  if (found) {
    item.downtime.push({
      kode_id: found.id,
      menit: 10,
      keterangan: found.arti || ''
    })
  } else {
    addDowntime(item)
  }
}

// PP Items Add & Remove
const addPpItem = () => {
  let defaultJam = ''
  if (form.value.start && form.value.finish) {
    const s = new Date(form.value.start).getTime()
    const f = new Date(form.value.finish).getTime()
    const mid = new Date(s + (f - s) * (form.value.pp_items.length / (form.value.pp_items.length + 1)))
    defaultJam = `${mid.getFullYear()}-${pad(mid.getMonth() + 1)}-${pad(mid.getDate())}T${pad(mid.getHours())}:${pad(mid.getMinutes())}`
  }
  form.value.pp_items.push(createNewPpItem(defaultJam))
}

const removePpItem = (idx) => {
  if (form.value.pp_items.length <= 1) {
    alert('Shift minimal harus memiliki 1 Job Order (PP).')
    return
  }
  if (!confirm(`Apakah Anda yakin ingin menghapus PP #${idx + 1} dari shift ini?`)) return
  form.value.pp_items.splice(idx, 1)
}

// PP Result change & sync Total Layar
const onPpResultChange = (item) => {
  if (!item.isTotalLayarManual) {
    item.total_layar = (Number(item.good) || 0) + (Number(item.reject) || 0) + (Number(item.setup_reject) || 0)
  }
}

const onTotalLayarManualInput = (item) => {
  const autoVal = (Number(item.good) || 0) + (Number(item.reject) || 0) + (Number(item.setup_reject) || 0)
  item.isTotalLayarManual = (Number(item.total_layar) !== autoVal)
}

const syncTotalLayar = (item) => {
  item.total_layar = (Number(item.good) || 0) + (Number(item.reject) || 0) + (Number(item.setup_reject) || 0)
  item.isTotalLayarManual = false
}

// Validation of Jam Pindah (for item 2+)
const isJamPindahValid = (idx) => {
  if (idx === 0) return true
  const item = form.value.pp_items[idx]
  if (!item.jam_mulai) return false

  const prevItem = form.value.pp_items[idx - 1]
  const prevTimeStr = idx === 1 ? form.value.start : prevItem.jam_mulai
  if (!prevTimeStr) return false

  const prevDate = new Date(prevTimeStr)
  const currDate = new Date(item.jam_mulai)
  const finishDate = form.value.finish ? new Date(form.value.finish) : null

  if (currDate <= prevDate) return false
  if (finishDate && currDate > finishDate) return false
  return true
}

// Shift duration helper
const shiftRawDurationJam = computed(() => {
  if (!form.value.start || !form.value.finish) return 0
  const s = new Date(form.value.start)
  const f = new Date(form.value.finish)
  let diff = (f - s) / (1000 * 60)
  if (diff < 0) diff += 24 * 60
  return diff / 60
})

// PER-PP CALCULATIONS
const perPpCalculated = computed(() => {
  const items = form.value.pp_items || []
  const n = items.length
  const setting_menit = Number(form.value.setting_menit) || 0
  const lima_s_menit = Number(form.value['5s_menit']) || 0

  return items.map((item, idx) => {
    const good = Number(item.good) || 0
    const reject = Number(item.reject) || 0
    const setup = Number(item.setup_reject) || 0
    const total_proses = good + reject + setup

    // Start & End times for this PP
    const startTimeStr = idx === 0 ? form.value.start : item.jam_mulai
    const endTimeStr = idx < n - 1 ? (items[idx + 1].jam_mulai || form.value.finish) : form.value.finish

    let waktu_prod_jam = 0
    let raw_diff_menit = 0

    if (startTimeStr && endTimeStr) {
      const s = new Date(startTimeStr)
      const f = new Date(endTimeStr)
      let diff = (f - s) / (1000 * 60)
      if (diff < 0) diff += 24 * 60
      raw_diff_menit = diff

      // Deduct setting if first PP, deduct 5s if last PP
      let potong = 0
      if (idx === 0) potong += setting_menit
      if (idx === n - 1) potong += lima_s_menit

      const waktu_prod_menit = Math.max(0, diff - potong)
      waktu_prod_jam = waktu_prod_menit / 60
    }

    const total_dt_menit = (item.downtime || []).reduce((acc, cur) => acc + (Number(cur.menit) || 0), 0)
    const jam_jalan = Math.max(0, waktu_prod_jam - (total_dt_menit / 60))
    const actual_jph = jam_jalan > 0 ? (total_proses / jam_jalan) : 0

    const selPp = getPpObj(item.pp_id)
    const is_pending = !selPp || selPp.status === 'req' || (parseFloat(selPp.std_jph) || 0) <= 0
    const std_jph = selPp ? (parseFloat(selPp.std_jph) || 0) : 0
    const target_shift = Math.round(jam_jalan * std_jph)

    const availability = waktu_prod_jam > 0 ? (jam_jalan / waktu_prod_jam) : 0
    const performance = (!is_pending && target_shift > 0) ? (total_proses / target_shift) : 0

    const total_sortir = good + setup
    const quality_oee = total_sortir > 0 ? (good / total_sortir) : (total_proses > 0 ? 1 : 0)

    const total_wip = good + reject
    const quality_wip = total_wip > 0 ? (good / total_wip) : 0

    const oee = (!is_pending) ? availability * performance * quality_oee : 0
    const sisa_waktu = Math.round(jam_jalan * 60)

    return {
      idx,
      raw_diff_menit,
      waktu_prod_jam,
      total_dt_menit,
      jam_jalan,
      total_proses,
      actual_jph,
      std_jph,
      target_shift,
      availability,
      performance,
      quality_oee,
      quality_wip,
      oee,
      sisa_waktu,
      is_pending
    }
  })
})

// SUMMARY AGGREGATE CALCULATIONS
const summaryCalculated = computed(() => {
  const perPp = perPpCalculated.value || []
  const has_pending = perPp.some(c => c.is_pending)
  const total_proses = perPp.reduce((acc, c) => acc + c.total_proses, 0)
  const total_good = (form.value.pp_items || []).reduce((acc, c) => acc + (Number(c.good) || 0), 0)
  const total_reject = (form.value.pp_items || []).reduce((acc, c) => acc + (Number(c.reject) || 0), 0)
  const total_setup_reject = (form.value.pp_items || []).reduce((acc, c) => acc + (Number(c.setup_reject) || 0), 0)
  const total_dt_menit = perPp.reduce((acc, c) => acc + c.total_dt_menit, 0)
  const total_jam_jalan = perPp.reduce((acc, c) => acc + c.jam_jalan, 0)
  const total_waktu_prod_jam = perPp.reduce((acc, c) => acc + c.waktu_prod_jam, 0)
  const total_target_shift = perPp.reduce((acc, c) => acc + (c.is_pending ? 0 : c.target_shift), 0)

  const actual_jph = total_jam_jalan > 0 ? (total_proses / total_jam_jalan) : 0
  const availability = total_waktu_prod_jam > 0 ? (total_jam_jalan / total_waktu_prod_jam) : 0
  const performance = (!has_pending && total_target_shift > 0) ? (total_proses / total_target_shift) : 0

  const total_sortir = total_good + total_setup_reject
  const quality_oee = total_sortir > 0 ? (total_good / total_sortir) : (total_proses > 0 ? 1 : 0)
  const oee = (!has_pending) ? availability * performance * quality_oee : 0

  return {
    total_proses,
    total_good,
    total_reject,
    total_setup_reject,
    total_dt_menit,
    total_jam_jalan,
    total_waktu_prod_jam,
    total_target_shift,
    actual_jph,
    availability,
    performance,
    quality_oee,
    oee,
    has_pending
  }
})

const oeeColorClass = (oeeVal) => {
  const pct = (oeeVal || 0) * 100
  if (pct >= 85) return 'badge-success text-success'
  if (pct >= 70) return 'badge-warning text-warning'
  return 'badge-danger text-danger'
}

// Section completion check
const isSectionComplete = (s) => {
  switch (s) {
    case 1:
      return !!form.value.tanggal && !!form.value.shift && !!form.value.mesin_id && !!form.value.operator
    case 2:
      return !!form.value.start && !!form.value.finish && !timeWarning.value
    case 3:
      return form.value.setting_menit !== '' && form.value['5s_menit'] !== ''
    case 4:
      return (form.value.pp_items || []).length > 0 && form.value.pp_items.every((item, idx) => {
        const hasPp = !!item.pp_id
        const hasTahap = !!item.tahap_sortir
        const hasGood = item.good !== '' && item.good !== null && Number(item.good) >= 0
        const hasValidTime = idx === 0 || (!!item.jam_mulai && isJamPindahValid(idx))
        const hasValidDt = (item.downtime || []).length === 0 || item.downtime.every(d => d.kode_id && d.menit > 0)
        return hasPp && hasTahap && hasGood && hasValidTime && hasValidDt
      })
    case 5:
      return true // Optional section
    case 6:
      return summaryCalculated.value.total_proses > 0
    default:
      return false
  }
}

const completedCount = computed(() => {
  let count = 0
  for (let s = 1; s <= 6; s++) {
    if (isSectionComplete(s)) count++
  }
  return count
})

// Time validation & shift changes
const validateTime = () => {
  timeWarning.value = ''
  if (!form.value.start || !form.value.finish) return
  
  const start = new Date(form.value.start)
  const finish = new Date(form.value.finish)
  
  if (finish <= start) {
    timeWarning.value = 'Jam Finish Shift harus setelah Jam Start Shift.'
  }
}

const onShiftTimeChange = () => {
  validateTime()
  if (form.value.pp_items.length > 0) {
    form.value.pp_items[0].jam_mulai = form.value.start
  }
}

const onShiftChange = () => {
  if (!form.value.shift || !form.value.tanggal) return
  const s = master.value.shift.find(x => x.shift == form.value.shift)
  if (!s) return
  
  const [startH, startM] = s.jam_mulai.split(':')
  const [endH, endM] = s.jam_selesai.split(':')
  
  const startDate = new Date(form.value.tanggal)
  startDate.setHours(parseInt(startH), parseInt(startM), 0)
  
  const finishDate = new Date(form.value.tanggal)
  finishDate.setHours(parseInt(endH), parseInt(endM), 0)
  
  if (finishDate <= startDate) {
    finishDate.setDate(finishDate.getDate() + 1)
  }
  
  form.value.start = `${startDate.getFullYear()}-${pad(startDate.getMonth()+1)}-${pad(startDate.getDate())}T${pad(startDate.getHours())}:${pad(startDate.getMinutes())}`
  form.value.finish = `${finishDate.getFullYear()}-${pad(finishDate.getMonth()+1)}-${pad(finishDate.getDate())}T${pad(finishDate.getHours())}:${pad(finishDate.getMinutes())}`
  
  if (form.value.lembur_menit > 0) {
    const f = new Date(finishDate)
    f.setMinutes(f.getMinutes() + Number(form.value.lembur_menit))
    form.value.finish = `${f.getFullYear()}-${pad(f.getMonth() + 1)}-${pad(f.getDate())}T${pad(f.getHours())}:${pad(f.getMinutes())}`
  }
  lastLemburMenit = Number(form.value.lembur_menit) || 0

  onShiftTimeChange()
}

// Lembur handlers
let lastLemburMenit = 0

const onLemburChange = () => {
  const dur = Math.max(0, Number(form.value.lembur_menit) || 0)
  form.value.lembur_menit = dur
  adjustFinishForLembur(lastLemburMenit, dur)
  lastLemburMenit = dur
}

const setLembur = (dur) => {
  const newLembur = Math.max(0, Number(dur) || 0)
  form.value.lembur_menit = newLembur
  adjustFinishForLembur(lastLemburMenit, newLembur)
  lastLemburMenit = newLembur
}

const adjustFinishForLembur = (oldLembur, newLembur) => {
  if (!form.value.finish) return
  const diffMenit = (Number(newLembur) || 0) - (Number(oldLembur) || 0)
  if (diffMenit === 0) return
  const f = new Date(form.value.finish)
  f.setMinutes(f.getMinutes() + diffMenit)
  form.value.finish = `${f.getFullYear()}-${pad(f.getMonth() + 1)}-${pad(f.getDate())}T${pad(f.getHours())}:${pad(f.getMinutes())}`
  onShiftTimeChange()
}

// PP selection changed on a specific PP item
const onPpChange = async (idx) => {
  const item = form.value.pp_items[idx]
  if (!item || !item.pp_id) {
    if (item) item.akumulasiPp = { total_tersortir: 0, total_s1: 0, total_s2: 0, total_s3: 0, total_semua: 0 }
    return
  }
  try {
    const res = await apiFetch(`lkh.php?action=akumulasi_pp&pp_id=${item.pp_id}`)
    item.akumulasiPp = {
      total_tersortir: res.total_tersortir || res.total_s1 || res.total_proses || 0,
      total_s1: res.total_s1 ?? (res.total_tersortir || res.total_proses || 0),
      total_s2: res.total_s2 || 0,
      total_s3: res.total_s3 || 0,
      total_semua: res.total_semua || 0
    }
  } catch (e) {
    console.error('Failed to get PP accumulation', e)
  }
}

// Modal Cacat (Reject WIP) handlers
const currentModalPpObj = computed(() => {
  const item = form.value.pp_items[activePpIndexForCacat.value]
  return item ? getPpObj(item.pp_id) : null
})

const openModalCacatForPp = (idx) => {
  activePpIndexForCacat.value = idx
  const item = form.value.pp_items[idx]
  modalDetailCacat.value = { ...(item.detailCacat || {}) }
  showModalCacat.value = true
}

const modalCacatTotal = computed(() => {
  let tot = 0
  for (const k in modalDetailCacat.value) {
    tot += Number(modalDetailCacat.value[k]) || 0
  }
  return tot
})

const focusDefectInput = (nama) => {
  const found = master.value.jenis_cacat.find(j => (j.nama || '').toUpperCase() === nama.toUpperCase())
  if (found) {
    nextTick(() => {
      const el = document.getElementById(`defect-input-${found.id}`)
      if (el) {
        el.focus()
        el.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }
    })
  }
}

const applyCacatModal = () => {
  const item = form.value.pp_items[activePpIndexForCacat.value]
  if (item) {
    item.detailCacat = { ...modalDetailCacat.value }
    item.reject = modalCacatTotal.value
    onPpResultChange(item)
  }
  showModalCacat.value = false
}

const resetCacatModal = () => {
  modalDetailCacat.value = {}
  const item = form.value.pp_items[activePpIndexForCacat.value]
  if (item) {
    item.detailCacat = {}
    item.reject = 0
    onPpResultChange(item)
  }
  showModalCacat.value = false
}

// SAVE LKH
const saveLkh = async () => {
  saving.value = true
  try {
    // Validate that all PP items have PP selected and valid times
    for (let i = 0; i < form.value.pp_items.length; i++) {
      const item = form.value.pp_items[i]
      if (!item.pp_id) {
        throw new Error(`Silakan pilih Nomor PP pada Job Order #${i + 1}`)
      }
      if (i > 0 && !isJamPindahValid(i)) {
        throw new Error(`Jam Pindah pada PP #${i + 1} tidak valid. Pastikan berada di antara jam start dan finish shift.`)
      }
    }

    // Format pp_items payload
    const formattedPpItems = form.value.pp_items.map((item, idx) => {
      const cacatData = []
      for (const [jid, qty] of Object.entries(item.detailCacat || {})) {
        if (Number(qty) > 0) {
          cacatData.push({ jenis_cacat_id: jid, qty: Number(qty), kategori: 'reject' })
        }
      }

      const ppCalc = perPpCalculated.value[idx] || {}

      return {
        pp_id: item.pp_id,
        tahap_sortir: item.tahap_sortir || 'S1',
        jam_mulai: idx === 0 ? form.value.start : item.jam_mulai,
        good: Number(item.good) || 0,
        reject: Number(item.reject) || 0,
        setup_reject: Number(item.setup_reject) || 0,
        total_layar: Number(item.total_layar) || ((Number(item.good) || 0) + (Number(item.reject) || 0) + (Number(item.setup_reject) || 0)),
        downtime: (item.downtime || []).map(d => ({
          kode_id: d.kode_id,
          menit: Number(d.menit) || 0,
          keterangan: d.keterangan || ''
        })),
        cacat: cacatData,
        plan_shift: ppCalc.target_shift || 0,
        jam_jalan: ppCalc.jam_jalan || 0,
        total_dt_menit: ppCalc.total_dt_menit || 0,
        oee: ppCalc.oee || 0
      }
    })

    // Construct full payload (including backward compatibility top-level fields from PP #1)
    const firstPp = formattedPpItems[0] || {}
    const payload = {
      tanggal: form.value.tanggal,
      shift: form.value.shift,
      mesin_id: form.value.mesin_id,
      operator: form.value.operator,
      pic: form.value.pic,
      start: form.value.start,
      finish: form.value.finish,
      lembur_menit: Number(form.value.lembur_menit) || 0,
      keterangan_lembur: form.value.keterangan_lembur || '',
      setting_menit: Number(form.value.setting_menit) || 0,
      '5s_menit': Number(form.value['5s_menit']) || 0,
      problem: form.value.problem || '',
      root_cause: form.value.root_cause || '',
      tindakan_sementara: form.value.tindakan_sementara || '',
      tindakan_perbaikan: form.value.tindakan_perbaikan || '',
      
      // Multi-PP items array
      pp_items: formattedPpItems,

      // Top-level fallback fields for backward compatibility with 1-PP views
      pp_id: firstPp.pp_id,
      tahap_sortir: firstPp.tahap_sortir,
      good: summaryCalculated.value.total_good,
      reject: summaryCalculated.value.total_reject,
      setup_reject: summaryCalculated.value.total_setup_reject,
      total_layar: summaryCalculated.value.total_proses,
      downtime: formattedPpItems.flatMap(p => p.downtime),
      cacat: formattedPpItems.flatMap(p => p.cacat),
      plan_shift: summaryCalculated.value.total_target_shift
    }

    if (isEdit.value) {
      await apiFetch(`lkh.php?action=update&id=${route.params.id}`, {
        method: 'PUT',
        body: JSON.stringify(payload)
      })
    } else {
      await apiFetch('lkh.php?action=create', {
        method: 'POST',
        body: JSON.stringify(payload)
      })
    }

    // Save last used machine and operator to localStorage
    const userKey = (authState.value.username || 'default').toLowerCase()
    if (form.value.mesin_id) {
      localStorage.setItem(`lkh_last_mesin_${userKey}`, form.value.mesin_id)
    }
    if (form.value.operator) {
      localStorage.setItem(`lkh_last_operator_${userKey}`, form.value.operator)
    }
    if (form.value.shift) {
      localStorage.setItem(`lkh_last_shift_${userKey}`, form.value.shift)
    }

    router.push('/lkh')
  } catch (e) {
    alert('Gagal menyimpan LKH: ' + e.message)
  } finally {
    saving.value = false
  }
}

// Lifecycle Mounted
onMounted(async () => {
  try {
    const [pp, shift, mesin, produk, jc, kd] = await Promise.all([
      apiFetch('master.php?action=pp'),
      apiFetch('master.php?action=shift_jadwal'),
      apiFetch('master.php?action=mesin'),
      apiFetch('master.php?action=produk'),
      apiFetch('master.php?action=jenis_cacat'),
      apiFetch('master.php?action=kode_downtime')
    ])
    master.value = { pp, shift, mesin, produk, jenis_cacat: jc, kode_downtime: kd }
    
    if (isEdit.value) {
      const data = await apiFetch(`lkh.php?action=detail&id=${route.params.id}`)

      if (authState.value.role !== 'admin') {
        const currentPic = (data.pic || '').trim().toLowerCase()
        const currentUser = (authState.value.username || '').trim().toLowerCase()
        if (currentPic && currentPic !== currentUser) {
          alert(`Akses ditolak: Anda hanya dapat mengubah data LKH milik akun Anda (PIC: ${authState.value.username}). Data LKH ini dicatat oleh PIC: ${data.pic || '(Kosong)'}.`)
          router.replace('/lkh')
          return
        }
      }

      form.value = {
        ...form.value,
        tanggal: data.tanggal,
        shift: data.shift,
        mesin_id: data.mesin_id,
        operator: data.operator,
        pic: data.pic || authState.value.username || '',
        start: data.start,
        finish: data.finish,
        lembur_menit: Number(data.lembur_menit) || 0,
        keterangan_lembur: data.keterangan_lembur || '',
        setting_menit: data.setting_menit || 0,
        '5s_menit': data['5s_menit'] || 0,
        problem: data.problem || '',
        root_cause: data.root_cause || '',
        tindakan_sementara: data.tindakan_sementara || '',
        tindakan_perbaikan: data.tindakan_perbaikan || '',
        pp_items: []
      }
      lastLemburMenit = Number(data.lembur_menit) || 0

      // Check if data already has pp_items (new format) or is legacy 1-PP format
      if (data.pp_items && Array.isArray(data.pp_items) && data.pp_items.length > 0) {
        form.value.pp_items = data.pp_items.map(p => {
          const detailCacat = {}
          if (p.cacat) {
            p.cacat.forEach(c => {
              if (c.kategori === 'reject') {
                detailCacat[c.jenis_cacat_id] = Number(c.qty)
              }
            })
          }
          const totalProsesSaved = (Number(p.good) || 0) + (Number(p.reject) || 0) + (Number(p.setup_reject) || 0)
          return {
            pp_id: p.pp_id,
            tahap_sortir: p.tahap_sortir || 'S1',
            jam_mulai: p.jam_mulai || form.value.start,
            good: Number(p.good) || 0,
            reject: Number(p.reject) || 0,
            setup_reject: Number(p.setup_reject) || 0,
            total_layar: Number(p.total_layar) || totalProsesSaved,
            isTotalLayarManual: Number(p.total_layar) !== totalProsesSaved,
            downtime: p.downtime || [],
            detailCacat,
            akumulasiPp: { total_tersortir: 0, total_s1: 0, total_s2: 0, total_s3: 0, total_semua: 0 }
          }
        })
      } else {
        // BACKWARD COMPATIBILITY: Wrap legacy 1-PP record into array of 1 PP item
        const detailCacat = {}
        if (data.cacat) {
          data.cacat.forEach(c => {
            if (c.kategori === 'reject') {
              detailCacat[c.jenis_cacat_id] = Number(c.qty)
            }
          })
        }
        const totalProsesSaved = (Number(data.good) || 0) + (Number(data.reject) || 0) + (Number(data.setup_reject) || 0)
        form.value.pp_items = [{
          pp_id: data.pp_id || '',
          tahap_sortir: data.tahap_sortir || 'S1',
          jam_mulai: data.start || '',
          good: Number(data.good) || 0,
          reject: Number(data.reject) || 0,
          setup_reject: Number(data.setup_reject) || 0,
          total_layar: Number(data.total_layar) || totalProsesSaved,
          isTotalLayarManual: Number(data.total_layar) !== totalProsesSaved,
          downtime: data.downtime || [],
          detailCacat,
          akumulasiPp: { total_tersortir: 0, total_s1: 0, total_s2: 0, total_s3: 0, total_semua: 0 }
        }]
      }

      // Fetch accumulation for all PP items
      for (let i = 0; i < form.value.pp_items.length; i++) {
        onPpChange(i)
      }
    } else {
      // PRE-FILL OPERATOR (MESIN & SHIFT TETAP KOSONG DEFAULT UNTUK DIPILIH OPERATOR)
      form.value.pic = authState.value.username || ''
      form.value.mesin_id = ''
      form.value.shift = ''
      const userKey = (authState.value.username || 'default').toLowerCase()
      const savedOperator = localStorage.getItem(`lkh_last_operator_${userKey}`)

      if (savedOperator) {
        form.value.operator = savedOperator
        hasPrefilledHistory.value = true
      } else if (authState.value.username) {
        form.value.operator = authState.value.username
      }

      if (route.query.pp_id) {
        const foundPp = master.value.pp.find(p => p.id == route.query.pp_id)
        if (foundPp && form.value.pp_items.length > 0) {
          form.value.pp_items[0].pp_id = foundPp.id
          if (route.query.tahap_sortir) {
            form.value.pp_items[0].tahap_sortir = route.query.tahap_sortir
          }
          onPpChange(0)
        }
      }
    }
  } catch (e) {
    console.error('Failed to initialize form', e)
  }
})
</script>

<style>
/* ==========================================================================
   Accordion & Step UI Styles (Global untuk Komponen LKH)
   ========================================================================== */
.lkh-form-page {
  position: relative;
  padding-bottom: 2rem;
}

/* Stepper Card */
.stepper-card {
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: 0.85rem 1rem;
  box-shadow: var(--shadow-sm);
}

.stepper-status {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.stepper-step-badge {
  background: var(--primary-light);
  color: var(--primary);
  font-weight: 800;
  font-size: 0.75rem;
  padding: 0.2rem 0.6rem;
  border-radius: var(--radius-full);
  border: 1px solid var(--primary-border);
  text-transform: uppercase;
}

.stepper-title-text {
  font-size: 0.95rem;
  color: #1e293b;
}

.stepper-progress-bg {
  width: 100%;
  height: 6px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
}

.stepper-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6 0%, #10b981 100%);
  transition: width 0.35s ease;
}

/* Stepper Pills Navigation */
.stepper-pills {
  display: flex;
  gap: 0.4rem;
  overflow-x: auto;
  padding-bottom: 0.25rem;
  scrollbar-width: thin;
}

.stepper-pill-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.7rem;
  background: #f8fafc;
  border: 1px solid var(--border);
  border-radius: var(--radius-full);
  font-size: 0.75rem;
  color: #475569;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s ease;
  font-family: inherit;
}

.stepper-pill-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.stepper-pill-btn.pill-active {
  background: #eff6ff;
  border-color: #3b82f6;
  color: #1d4ed8;
  font-weight: 700;
}

.stepper-pill-btn.pill-complete .pill-number {
  background: #10b981;
  color: #ffffff;
}

.pill-number {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #cbd5e1;
  color: #1e293b;
  font-size: 0.65rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
}

.pill-label {
  font-size: 0.75rem;
}

/* Accordion Item */
.accordion-item {
  background: #ffffff;
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  overflow: hidden;
  transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.accordion-item.is-open {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}

.accordion-header {
  padding: 0.85rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  background: #f8fafc;
  user-select: none;
  transition: background 0.15s ease;
  gap: 0.75rem;
}

.accordion-header:hover {
  background: #f1f5f9;
}

.accordion-header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.step-circle {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #475569;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 800;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.step-circle.step-done {
  background: #10b981;
  color: #ffffff;
}

.accordion-title {
  margin: 0;
  font-size: 0.95rem;
  color: #1e293b;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.accordion-snippet {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 480px;
}

.accordion-header-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.accordion-chevron {
  font-size: 0.75rem;
  color: #64748b;
  width: 20px;
  text-align: center;
}

.accordion-body {
  padding: 1rem;
  border-top: 1px solid var(--border-light);
  background: #ffffff;
}

.accordion-step-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 0.75rem;
  border-top: 1px dashed var(--border);
}

/* PP Repeatable Card */
.pp-repeatable-card {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: var(--radius-md);
  padding: 1rem;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.bg-green-soft {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}

.bg-blue-soft {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
}

/* Quick Chip Bar */
.quick-chip-bar {
  background: #f8fafc;
  border: 1px solid var(--border);
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.quick-chip-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #334155;
  white-space: nowrap;
}

.chip-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  border: 1px solid transparent;
  font-family: inherit;
  transition: all 0.15s ease;
}

.chip-btn:active {
  transform: scale(0.97);
}

.chip-dt {
  background: #fef3c7;
  color: #92400e;
  border-color: #fde68a;
}
.chip-dt:hover {
  background: #fde68a;
}

.chip-defect {
  background: #fee2e2;
  color: #991b1b;
  border-color: #fecaca;
}
.chip-defect:hover {
  background: #fecaca;
}

/* Badge Waste */
.badge-waste {
  background: #e0f2fe;
  color: #0369a1;
  border: 1px solid #bae6fd;
  font-size: 0.72rem;
  font-weight: 700;
}

/* Sticky Summary Card */
.sticky-summary-card {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 320px;
  max-width: calc(100vw - 32px);
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(10px);
  border: 1px solid #bfdbfe;
  box-shadow: 0 10px 25px -5px rgba(30, 58, 138, 0.2), 0 8px 10px -6px rgba(30, 58, 138, 0.1);
  border-radius: 12px;
  z-index: 99;
  overflow: hidden;
  transition: all 0.25s ease;
}

@media (max-width: 768px) {
  .sticky-summary-card {
    bottom: 68px; /* Above android bottom navigation bar */
    left: 10px;
    right: 10px;
    width: auto;
  }
}

.sticky-card-header {
  padding: 0.4rem 0.75rem;
  background: #eff6ff;
  border-bottom: 1px solid #dbeafe;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sticky-live-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
  animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(1.2); }
}

.btn-sticky-toggle {
  background: transparent;
  border: none;
  font-size: 0.75rem;
  font-weight: 700;
  color: #3b82f6;
  cursor: pointer;
  padding: 2px 6px;
}

.sticky-card-body {
  padding: 0.6rem 0.85rem;
}

.sticky-metric-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
}

.sticky-metric-col small {
  display: block;
  font-size: 0.68rem;
  line-height: 1;
  margin-bottom: 2px;
}

.sticky-metric-val {
  font-size: 0.95rem;
  font-weight: 800;
  color: #0f172a;
}
.sticky-metric-val small {
  display: inline;
  font-size: 0.7rem;
  font-weight: normal;
  color: #64748b;
}

.sticky-metric-oee {
  font-size: 1.15rem;
  font-weight: 900;
}

/* Metric cards in Section 6 */
.metric-card {
  background: white;
  padding: 0.6rem 0.85rem;
  border-radius: var(--radius-sm);
  border: 1px solid #dbeafe;
}

.highlight-oee {
  background: #dcfce7 !important;
  border-color: #86efac !important;
}

/* Alert Callouts */
.alert-callout-info {
  font-size: 0.82rem;
  color: #1e40af;
  background: #eff6ff;
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  border: 1px solid #bfdbfe;
}

.pp-info-card {
  background: white;
  border: 1px solid var(--border);
  padding: 0.65rem 0.85rem;
  border-radius: var(--radius-md);
}

.p-075 { padding: 0.75rem; }
.pb-05 { padding-bottom: 0.5rem; }
.pb-02 { padding-bottom: 0.2rem; }
.pt-05 { padding-top: 0.5rem; }
.mb-15 { margin-bottom: 1.5rem; }
.border-rounded { border-radius: var(--radius-md); border: 1px solid var(--border); }
.border-bottom { border-bottom: 1px solid var(--border); }
.border-top { border-top: 1px solid var(--border); }
.bg-white { background: #ffffff; }
.bg-light { background: #f8fafc; }
.mt-02 { margin-top: 0.2rem; }
.gap-02 { gap: 0.2rem; }
.btn-xs { padding: 3px 8px; font-size: 0.72rem; border-radius: 4px; }
.btn-outline-primary { background: transparent; border: 1px solid #3b82f6; color: #2563eb; cursor: pointer; }
.btn-outline-primary:hover { background: #eff6ff; }
.btn-outline-danger { background: transparent; border: 1px solid #ef4444; color: #dc2626; cursor: pointer; }
.btn-outline-danger:hover { background: #fef2f2; }
.btn-mobile-block { display: inline-block; }
@media (max-width: 600px) {
  .btn-mobile-block { width: 100%; }
}
</style>
