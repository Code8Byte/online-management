import { ref, computed } from 'vue'

// ==========================================
// 1. MASTER MESIN & KAPASITAS STANDAR PPIC
// ==========================================
export const masterMesinPPIC = [
  // Cetak (Satuan: Plano / Jam)
  { id: 'sm23', stage: 'cetak', nama: 'SM 23 (Heidelberg Speedmaster 102)', speed_jph: 8500, jam_hari: 16, unit: 'plano' },
  { id: 'sm25', stage: 'cetak', nama: 'SM 25 (Heidelberg CD 102)', speed_jph: 9000, jam_hari: 16, unit: 'plano' },
  { id: 'sm27', stage: 'cetak', nama: 'SM 27 (Heidelberg SM)', speed_jph: 7500, jam_hari: 16, unit: 'plano' },
  { id: 'kba106', stage: 'cetak', nama: 'KBA Rapida 106 High Speed', speed_jph: 10000, jam_hari: 16, unit: 'plano' },
  { id: 'sm10', stage: 'cetak', nama: 'SM 10 (Heidelberg 2 Warna)', speed_jph: 6500, jam_hari: 16, unit: 'plano' },

  // Completing (Satuan: Plano / Jam)
  { id: 'hsf1', stage: 'completing', nama: 'Hot Stamping Foil 1 & UV Gloss', speed_jph: 4500, jam_hari: 16, unit: 'plano' },
  { id: 'hsf2', stage: 'completing', nama: 'Hot Stamping Foil 2', speed_jph: 4000, jam_hari: 16, unit: 'plano' },
  { id: 'uv_obat', stage: 'completing', nama: 'Varnish UV Gloss Obat Pharma', speed_jph: 6000, jam_hari: 16, unit: 'plano' },
  { id: 'lam_flute', stage: 'completing', nama: 'Laminasi Flute E/F Corrugated', speed_jph: 4000, jam_hari: 16, unit: 'plano' },
  { id: 'lam_holo', stage: 'completing', nama: 'Laminasi Transfer Hologram', speed_jph: 3500, jam_hari: 16, unit: 'plano' },

  // Cabut (Die-Cutting Pond: Input Plano, Output Pcs)
  { id: 'ms1', stage: 'cabut', nama: 'Bobst MS 1 (Die Plat 12 Up)', speed_jph: 6000, jam_hari: 16, unit: 'plano' },
  { id: 'ms2', stage: 'cabut', nama: 'Bobst MS 2 Presisi', speed_jph: 6500, jam_hari: 16, unit: 'plano' },
  { id: 'ms3', stage: 'cabut', nama: 'Bobst MS 3 Double Crease', speed_jph: 5500, jam_hari: 16, unit: 'plano' },
  { id: 'ms4', stage: 'cabut', nama: 'Bobst MS 4 High Speed', speed_jph: 5500, jam_hari: 16, unit: 'plano' },

  // Lipat (Folder Gluer: Satuan Pcs / Jam)
  { id: 'gluer_herui', stage: 'lipat', nama: 'Herui Speed Gluer', speed_jph: 35000, jam_hari: 16, unit: 'pcs' },
  { id: 'gluer_ml1', stage: 'lipat', nama: 'Mesin Lipat 1 Crash Lock', speed_jph: 30000, jam_hari: 16, unit: 'pcs' },
  { id: 'gluer_ml2', stage: 'lipat', nama: 'Mesin Lipat 2 Straight Line', speed_jph: 35000, jam_hari: 16, unit: 'pcs' },
  { id: 'gluer_ml3', stage: 'lipat', nama: 'Mesin Lipat 3 Multi-Point', speed_jph: 25000, jam_hari: 16, unit: 'pcs' },

  // Sortir (QC & Inspection: Satuan Pcs / Jam)
  { id: 'sortir_fs500', stage: 'sortir', nama: 'Focusight FS-500 Shark', speed_jph: 40000, jam_hari: 16, unit: 'pcs' },
  { id: 'sortir_fs600', stage: 'sortir', nama: 'Focusight FS-600 Sheet', speed_jph: 35000, jam_hari: 16, unit: 'pcs' },
  { id: 'sortir_raptor', stage: 'sortir', nama: 'Luster Raptor Optical', speed_jph: 35000, jam_hari: 16, unit: 'pcs' },
  { id: 'sortir_manual', stage: 'sortir', nama: 'Meja Sortir Manual Lini A/B', speed_jph: 15000, jam_hari: 16, unit: 'pcs' }
]

// ==========================================
// 2. FORMULA KALKULATOR PLANO & KAPASITAS
// ==========================================
export function calculatePlanoFromOplaag(oplaagPcs, upPerPlano, insheetPercent = 2) {
  const o = Number(oplaagPcs) || 0
  const u = Number(upPerPlano) || 1
  if (o <= 0 || u <= 0) {
    return {
      nettoPlano: 0,
      insheetPlano: 0,
      totalPlano: 0
    }
  }
  const nettoPlano = Math.ceil(o / u)
  const insheetPlano = Math.ceil(nettoPlano * (Number(insheetPercent || 2) / 100))
  const totalPlano = nettoPlano + insheetPlano
  return {
    nettoPlano,
    insheetPlano,
    totalPlano
  }
}

export function calculateEstimatedDays(quantity, speedJph, workHoursPerDay = 16) {
  const q = Number(quantity) || 0
  const s = Number(speedJph) || 1
  const h = Number(workHoursPerDay) || 16
  const totalHours = q / s
  const days = Math.max(1, Math.ceil(totalHours / h))
  return {
    totalHours: Number(totalHours.toFixed(1)),
    days
  }
}

export function addDaysToDate(dateStr, daysToAdd) {
  const [y, m, d] = dateStr.split('-').map(Number)
  const dt = new Date(y, m - 1, d)
  dt.setDate(dt.getDate() + Number(daysToAdd))
  const pad = (n) => String(n).padStart(2, '0')
  return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}`
}

// ==========================================
// 3. JADWAL AWAL SEED SCHEDULES DENGAN AUTO-EXTEND
// ==========================================
const baseSchedules = [
  // PP-2026-0821: Djarum 12 Super (Oplaag 2.000.000 pcs, 1 Plano isi 40 Up -> 50.000 plano)
  {
    id: 101,
    no_pp: 'PP-2026-0821',
    produk_nama: 'Etiket Rokok Djarum 12 Super',
    stage: 'cetak',
    stage_num: 1,
    mesin_id: 'sm23',
    mesin_nama: 'SM 23 (Heidelberg Speedmaster 102)',
    oplaag_pcs: 2000000,
    up_per_plano: 40,
    plan_plano: 50000,
    unit: 'plano',
    plan_qty: 50000,
    actual_output: 49500,
    progress: 99.0,
    status: 'selesai',
    start_date: '2026-10-25',
    planned_end_date: '2026-10-27',
    actual_end_date: '2026-10-27'
  },
  {
    id: 102,
    no_pp: 'PP-2026-0821',
    produk_nama: 'Etiket Rokok Djarum 12 Super',
    stage: 'completing',
    stage_num: 2,
    mesin_id: 'hsf1',
    mesin_nama: 'Hot Stamping Foil 1 & UV Gloss',
    oplaag_pcs: 2000000,
    up_per_plano: 40,
    plan_plano: 48500,
    unit: 'plano',
    plan_qty: 48500,
    actual_output: 45000,
    progress: 92.8,
    status: 'proses',
    start_date: '2026-10-28',
    planned_end_date: '2026-10-31',
    actual_end_date: null
  },
  {
    id: 103,
    no_pp: 'PP-2026-0821',
    produk_nama: 'Etiket Rokok Djarum 12 Super',
    stage: 'cabut',
    stage_num: 3,
    mesin_id: 'ms1',
    mesin_nama: 'Bobst MS 1 (Die Plat 12 Up)',
    oplaag_pcs: 2000000,
    up_per_plano: 40,
    plan_plano: 44200,
    unit: 'pcs',
    plan_qty: 44200,
    actual_output: 38000,
    progress: 86.0,
    status: 'proses',
    start_date: '2026-10-29',
    planned_end_date: '2026-10-31', // Jadwal planned akhir 31 Okt tapi baru 86%, harusnya extend!
    actual_end_date: null
  },
  {
    id: 104,
    no_pp: 'PP-2026-0821',
    produk_nama: 'Etiket Rokok Djarum 12 Super',
    stage: 'lipat',
    stage_num: 4,
    mesin_id: 'gluer_herui',
    mesin_nama: 'Herui Speed Gluer',
    oplaag_pcs: 2000000,
    up_per_plano: 40,
    plan_plano: 37400,
    unit: 'pcs',
    plan_qty: 37400,
    actual_output: 30000,
    progress: 80.2,
    status: 'proses',
    start_date: '2026-10-30',
    planned_end_date: '2026-10-31', // Terlewati & belum selesai -> Auto Extend!
    actual_end_date: null
  },
  {
    id: 105,
    no_pp: 'PP-2026-0821',
    produk_nama: 'Etiket Rokok Djarum 12 Super',
    stage: 'sortir',
    stage_num: 5,
    mesin_id: 'sortir_fs500',
    mesin_nama: 'Focusight FS-500 Shark',
    oplaag_pcs: 2000000,
    up_per_plano: 40,
    plan_plano: 29600,
    unit: 'pcs',
    plan_qty: 29600,
    actual_output: 25700,
    progress: 84.5,
    status: 'proses',
    start_date: '2026-10-31',
    planned_end_date: '2026-11-01',
    actual_end_date: null
  },

  // PP-2026-0824: Box Farmasi Kalbe (Oplaag 160.000 pcs, 1 Plano isi 4 pcs -> 40.000 plano)
  {
    id: 201,
    no_pp: 'PP-2026-0824',
    produk_nama: 'Box Farmasi Kalbe Farma 500mg',
    stage: 'cetak',
    stage_num: 1,
    mesin_id: 'sm25',
    mesin_nama: 'SM 25 (Heidelberg CD 102)',
    oplaag_pcs: 160000,
    up_per_plano: 4,
    plan_plano: 40000,
    unit: 'plano',
    plan_qty: 40000,
    actual_output: 40200,
    progress: 100.0,
    status: 'selesai',
    start_date: '2026-10-26',
    planned_end_date: '2026-10-28',
    actual_end_date: '2026-10-28'
  },
  {
    id: 202,
    no_pp: 'PP-2026-0824',
    produk_nama: 'Box Farmasi Kalbe Farma 500mg',
    stage: 'completing',
    stage_num: 2,
    mesin_id: 'uv_obat',
    mesin_nama: 'Varnish UV Gloss Obat Pharma',
    oplaag_pcs: 160000,
    up_per_plano: 4,
    plan_plano: 39500,
    unit: 'plano',
    plan_qty: 39500,
    actual_output: 39200,
    progress: 99.2,
    status: 'selesai',
    start_date: '2026-10-28',
    planned_end_date: '2026-10-29',
    actual_end_date: '2026-10-29'
  },
  {
    id: 203,
    no_pp: 'PP-2026-0824',
    produk_nama: 'Box Farmasi Kalbe Farma 500mg',
    stage: 'cabut',
    stage_num: 3,
    mesin_id: 'ms2',
    mesin_nama: 'Bobst MS 2 Presisi',
    oplaag_pcs: 160000,
    up_per_plano: 4,
    plan_plano: 38800,
    unit: 'pcs',
    plan_qty: 38800,
    actual_output: 38800,
    progress: 100.0,
    status: 'selesai',
    start_date: '2026-10-29',
    planned_end_date: '2026-10-30',
    actual_end_date: '2026-10-30'
  },
  {
    id: 204,
    no_pp: 'PP-2026-0824',
    produk_nama: 'Box Farmasi Kalbe Farma 500mg',
    stage: 'sortir',
    stage_num: 4,
    mesin_id: 'sortir_fs600',
    mesin_nama: 'Focusight FS-600 Sheet',
    oplaag_pcs: 160000,
    up_per_plano: 4,
    plan_plano: 38200,
    unit: 'pcs',
    plan_qty: 38200,
    actual_output: 35500,
    progress: 91.4,
    status: 'proses',
    start_date: '2026-10-30',
    planned_end_date: '2026-10-31', // Hari ini 1 Nov, baru 91.4% -> Auto Extend!
    actual_end_date: null
  },
  {
    id: 205,
    no_pp: 'PP-2026-0824',
    produk_nama: 'Box Farmasi Kalbe Farma 500mg',
    stage: 'lipat',
    stage_num: 5,
    mesin_id: 'gluer_ml2',
    mesin_nama: 'Mesin Lipat 2 Straight Line',
    oplaag_pcs: 160000,
    up_per_plano: 4,
    plan_plano: 34920,
    unit: 'pcs',
    plan_qty: 34920,
    actual_output: 28000,
    progress: 80.0,
    status: 'proses',
    start_date: '2026-10-31',
    planned_end_date: '2026-11-02',
    actual_end_date: null
  },

  // PP-2026-0828: Outer Corrugated Box (Oplaag 240.000 pcs, 1 Plano isi 4 pcs -> 60.000 plano)
  {
    id: 301,
    no_pp: 'PP-2026-0828',
    produk_nama: 'Outer Corrugated Box Indofood Flat Sheet',
    stage: 'cetak',
    stage_num: 1,
    mesin_id: 'kba106',
    mesin_nama: 'KBA Rapida 106 High Speed',
    oplaag_pcs: 240000,
    up_per_plano: 4,
    plan_plano: 60000,
    unit: 'plano',
    plan_qty: 60000,
    actual_output: 59800,
    progress: 99.7,
    status: 'selesai',
    start_date: '2026-10-27',
    planned_end_date: '2026-10-29',
    actual_end_date: '2026-10-29'
  },
  {
    id: 302,
    no_pp: 'PP-2026-0828',
    produk_nama: 'Outer Corrugated Box Indofood Flat Sheet',
    stage: 'completing',
    stage_num: 2,
    mesin_id: 'lam_flute',
    mesin_nama: 'Laminasi Flute E/F Corrugated',
    oplaag_pcs: 240000,
    up_per_plano: 4,
    plan_plano: 58500,
    unit: 'plano',
    plan_qty: 58500,
    actual_output: 45000,
    progress: 76.9,
    status: 'proses',
    start_date: '2026-10-29',
    planned_end_date: '2026-10-31', // Planned selesai kemarin tapi baru 76.9% -> Auto Extend!
    actual_end_date: null
  },
  {
    id: 303,
    no_pp: 'PP-2026-0828',
    produk_nama: 'Outer Corrugated Box Indofood Flat Sheet',
    stage: 'cabut',
    stage_num: 3,
    mesin_id: 'ms3',
    mesin_nama: 'Bobst MS 3 Double Crease',
    oplaag_pcs: 240000,
    up_per_plano: 4,
    plan_plano: 44200,
    unit: 'pcs',
    plan_qty: 44200,
    actual_output: 0,
    progress: 0,
    status: 'antri',
    start_date: '2026-11-01',
    planned_end_date: '2026-11-03',
    actual_end_date: null
  },
  {
    id: 304,
    no_pp: 'PP-2026-0828',
    produk_nama: 'Outer Corrugated Box Indofood Flat Sheet',
    stage: 'sortir',
    stage_num: 4,
    mesin_id: 'sortir_raptor',
    mesin_nama: 'Luster Raptor Optical',
    oplaag_pcs: 240000,
    up_per_plano: 4,
    plan_plano: 44200,
    unit: 'pcs',
    plan_qty: 44200,
    actual_output: 0,
    progress: 0,
    status: 'antri',
    start_date: '2026-11-03',
    planned_end_date: '2026-11-04',
    actual_end_date: null
  }
]

// ==========================================
// 4. REACTIVE STATE & AUTO-EXTEND EVALUATION
// ==========================================
export const schedulesState = ref([...baseSchedules])

// Tanggal referensi sistem (Hari ini: 2026-11-01)
export const CURRENT_SYSTEM_DATE = '2026-11-01'

/**
 * Memproses Auto-Extend secara otomatis pada task yang molor / belum selesai
 */
export const evaluatedSchedules = computed(() => {
  return schedulesState.value.map(task => {
    const isCompleted = task.status === 'selesai' || task.progress >= 100
    const isSkipped = task.status === 'lewati'
    
    // Periksa apakah planned_end_date sudah terlewati atau hari ini
    const isOverdue = task.planned_end_date <= CURRENT_SYSTEM_DATE
    
    if (!isCompleted && !isSkipped && isOverdue) {
      // Hitung kekurangan output
      const remaining = Math.max(0, (task.plan_qty || 0) - (task.actual_output || 0))
      
      // Ambil kapasitas mesin per hari
      const machine = masterMesinPPIC.find(m => m.id === task.mesin_id)
      const dailyCap = machine ? machine.speed_jph * machine.jam_hari : 100000
      
      // Hitung perpanjangan hari yang dibutuhkan
      const neededExtraDays = Math.max(1, Math.ceil(remaining / dailyCap))
      const extendedEndDate = addDaysToDate(CURRENT_SYSTEM_DATE, neededExtraDays)
      
      return {
        ...task,
        is_extended: true,
        extend_days: neededExtraDays,
        effective_end_date: extendedEndDate,
        extend_reason: `Progress ${task.progress.toFixed(1)}% (kurang ${Number(remaining).toLocaleString('id-ID')} ${task.unit}). Otomatis extend +${neededExtraDays} hari s/d ${extendedEndDate}.`
      }
    }
    
    return {
      ...task,
      is_extended: false,
      extend_days: 0,
      effective_end_date: task.planned_end_date
    }
  })
})

/**
 * Menambah Job Order & Rangkaian Jadwal Otomatis dari Form Input PPIC
 */
export function addPpicJobOrderSchedule(payload) {
  const {
    no_pp,
    produk_nama,
    oplaag_pcs,
    up_per_plano,
    insheet_percent,
    start_date,
    stages // Array of stage config: { stage, mesin_id, enabled }
  } = payload

  // 1. Kalkulasi Plano
  const calc = calculatePlanoFromOplaag(oplaag_pcs, up_per_plano, insheet_percent)
  const totalPlano = calc.totalPlano

  let currentDate = start_date || CURRENT_SYSTEM_DATE
  const newTasks = []
  let nextId = Date.now()

  // 2. Loop tahapan produksi berurutan (cascade jadwal otomatis)
  stages.forEach((st, idx) => {
    if (!st.enabled) return

    const machine = masterMesinPPIC.find(m => m.id === st.mesin_id)
    if (!machine) return

    // Tentukan kuota proses
    const isPlanoStage = machine.unit === 'plano'
    const stageQty = isPlanoStage ? totalPlano : Number(oplaag_pcs)

    // Hitung durasi hari kerja berdasarkan kapasitas mesin
    const dur = calculateEstimatedDays(stageQty, machine.speed_jph, machine.jam_hari)
    const plannedEndDate = addDaysToDate(currentDate, dur.days)

    newTasks.push({
      id: nextId++,
      no_pp,
      produk_nama,
      stage: st.stage,
      stage_num: idx + 1,
      mesin_id: machine.id,
      mesin_nama: machine.nama,
      oplaag_pcs: Number(oplaag_pcs),
      up_per_plano: Number(up_per_plano),
      plan_plano: totalPlano,
      unit: machine.unit,
      plan_qty: stageQty,
      actual_output: 0,
      progress: 0,
      status: 'antri',
      start_date: currentDate,
      planned_end_date: plannedEndDate,
      actual_end_date: null
    })

    // Tanggal proses berikutnya dimulai setelah proses ini selesai
    currentDate = plannedEndDate
  })

  // Simpan ke state
  schedulesState.value.unshift(...newTasks)
  return newTasks
}
