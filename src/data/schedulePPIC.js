import { ref, computed } from 'vue'
import { apiFetch } from '../utils/api'

// ==========================================
// 1. MASTER MESIN & KAPASITAS STANDAR PPIC
// Urutan Pabrik: Cetak -> Completing -> Cabut -> Sortir -> Lipat
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

  // Cabut (Die-Cutting Pond: Input Plano, Konversi ke Pcs)
  { id: 'ms1', stage: 'cabut', nama: 'Bobst MS 1 (Die Plat 4 Up)', speed_jph: 6000, jam_hari: 16, unit: 'plano' },
  { id: 'ms2', stage: 'cabut', nama: 'Bobst MS 2 Presisi', speed_jph: 6500, jam_hari: 16, unit: 'plano' },
  { id: 'ms3', stage: 'cabut', nama: 'Bobst MS 3 Double Crease', speed_jph: 5500, jam_hari: 16, unit: 'plano' },
  { id: 'ms4', stage: 'cabut', nama: 'Bobst MS 4 High Speed', speed_jph: 5500, jam_hari: 16, unit: 'plano' },

  // Sortir (QC & Optical Inspection Sheet Hasil Pond: Satuan Pcs / Jam)
  { id: 'sortir_fs500', stage: 'sortir', nama: 'Focusight FS-500 Shark', speed_jph: 40000, jam_hari: 16, unit: 'pcs' },
  { id: 'sortir_fs600', stage: 'sortir', nama: 'Focusight FS-600 Sheet', speed_jph: 35000, jam_hari: 16, unit: 'pcs' },
  { id: 'sortir_raptor', stage: 'sortir', nama: 'Luster Raptor Optical', speed_jph: 35000, jam_hari: 16, unit: 'pcs' },
  { id: 'sortir_manual', stage: 'sortir', nama: 'Meja Sortir Manual Lini A/B', speed_jph: 15000, jam_hari: 16, unit: 'pcs' },

  // Lipat (Folder Gluer Pcs Lolos Sortir: Satuan Pcs / Jam)
  { id: 'gluer_herui', stage: 'lipat', nama: 'Herui Speed Gluer', speed_jph: 35000, jam_hari: 16, unit: 'pcs' },
  { id: 'gluer_ml1', stage: 'lipat', nama: 'Mesin Lipat 1 Crash Lock', speed_jph: 30000, jam_hari: 16, unit: 'pcs' },
  { id: 'gluer_ml2', stage: 'lipat', nama: 'Mesin Lipat 2 Straight Line', speed_jph: 35000, jam_hari: 16, unit: 'pcs' },
  { id: 'gluer_ml3', stage: 'lipat', nama: 'Mesin Lipat 3 Multi-Point', speed_jph: 25000, jam_hari: 16, unit: 'pcs' }
]

// ==========================================
// 2. MASTER ARMADA LOGISTIK PENGIRIMAN
// ==========================================
export const masterArmadaLogistik = [
  { id: 'engkel', nama: 'Truk Engkel Box (CDE)', kapasitas_pallet: 4, kapasitas_koli: 80, tonase_kg: 2200 },
  { id: 'cdd', nama: 'Colt Diesel Double (CDD)', kapasitas_pallet: 10, kapasitas_koli: 200, tonase_kg: 4500 },
  { id: 'cdd_long', nama: 'CDD Long Box 14 Pallet', kapasitas_pallet: 14, kapasitas_koli: 280, tonase_kg: 6000 },
  { id: 'fuso', nama: 'Truk Fuso Berat 8 Ton', kapasitas_pallet: 18, kapasitas_koli: 400, tonase_kg: 10000 },
  { id: 'wingbox', nama: 'Tronton Wingbox 24ft', kapasitas_pallet: 26, kapasitas_koli: 600, tonase_kg: 18000 }
]

// ==========================================
// 3. UTILITY & FORMULA KALKULATOR JAM & MENIT
// ==========================================
export function calculatePlanoFromOplaag(oplaagPcs, upPerPlano, insheetPercent = 2) {
  const o = Number(oplaagPcs) || 0
  const u = Number(upPerPlano) || 1
  if (o <= 0 || u <= 0) {
    return { nettoPlano: 0, insheetPlano: 0, totalPlano: 0 }
  }
  const nettoPlano = Math.ceil(o / u)
  const insheetPlano = Math.ceil(nettoPlano * (Number(insheetPercent || 2) / 100))
  const totalPlano = nettoPlano + insheetPlano
  return { nettoPlano, insheetPlano, totalPlano }
}

export function formatMinutesToHuman(minutes) {
  const m = Math.max(0, Math.round(Number(minutes) || 0))
  const jam = Math.floor(m / 60)
  const sisaMenit = m % 60
  if (jam === 0) return `${sisaMenit} Menit`
  if (sisaMenit === 0) return `${jam} Jam`
  return `${jam} Jam ${sisaMenit} Menit`
}

export function addMinutesToDateTime(dtStr, minutes) {
  if (!dtStr) return '2026-11-01 08:00'
  const clean = dtStr.replace(' ', 'T')
  const dt = new Date(clean)
  if (isNaN(dt.getTime())) return dtStr
  dt.setMinutes(dt.getMinutes() + Number(minutes))
  const pad = (n) => String(n).padStart(2, '0')
  const yr = dt.getFullYear()
  const mo = pad(dt.getMonth() + 1)
  const dy = pad(dt.getDate())
  const hr = pad(dt.getHours())
  const mi = pad(dt.getMinutes())
  return `${yr}-${mo}-${dy} ${hr}:${mi}`
}

export function diffMinutes(dtStartStr, dtEndStr) {
  if (!dtStartStr || !dtEndStr) return 0
  const dt1 = new Date(dtStartStr.replace(' ', 'T'))
  const dt2 = new Date(dtEndStr.replace(' ', 'T'))
  return Math.round((dt2.getTime() - dt1.getTime()) / (1000 * 60))
}

export function calculateEstimatedMinutes(quantity, speedJph) {
  const q = Number(quantity) || 0
  const s = Number(speedJph) || 1
  const hours = q / s
  return Math.max(15, Math.round(hours * 60))
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
  if (!dateStr) return '2026-11-01'
  const [y, m, d] = dateStr.split(' ')[0].split('-').map(Number)
  const dt = new Date(y, m - 1, d)
  dt.setDate(dt.getDate() + Number(daysToAdd))
  const pad = (n) => String(n).padStart(2, '0')
  return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}`
}

export function calculateJobLogistics({
  oplaag_pcs,
  pcs_per_koli = 500,
  koli_per_pallet = 20,
  berat_pcs_gram = 25,
  armada_id = 'cdd',
  lead_time_kirim_jam = 3
}) {
  const pcs = Number(oplaag_pcs) || 0
  const perKoli = Math.max(1, Number(pcs_per_koli) || 500)
  const totalKoli = Math.ceil(pcs / perKoli)
  
  const perPallet = Math.max(1, Number(koli_per_pallet) || 20)
  const totalPallet = Math.ceil(totalKoli / perPallet)
  
  const beratPerPcs = Number(berat_pcs_gram) || 25
  const totalBeratKg = Math.round((pcs * beratPerPcs) / 1000)
  
  const armada = masterArmadaLogistik.find(a => a.id === armada_id) || masterArmadaLogistik[1]
  const ritase = Math.max(1, Math.ceil(totalPallet / armada.kapasitas_pallet))
  
  return {
    totalKoli,
    totalPallet,
    totalBeratKg,
    armada,
    ritase,
    leadTimeKirimJam: Number(lead_time_kirim_jam) || 3
  }
}

// ==========================================
// 4. JADWAL SEED DENGAN RINCIAN JAM & MENIT
// ==========================================
const baseSchedules = []

// ==========================================
// 5.1 SYNC DENGAN DATABASE MYSQL VIA API
// ==========================================
export async function fetchSchedulesFromApi() {
  try {
    const res = await apiFetch('ppic.php?action=gantt')
    if (Array.isArray(res)) {
      schedulesState.value = res.map(task => ({
        ...task,
        id: Number(task.id),
        stage_num: Number(task.stage_num),
        plan_qty: Number(task.plan_qty),
        actual_output: Number(task.actual_output),
        good: Number(task.good),
        reject: Number(task.reject),
        progress: Number(task.progress),
        duration_minutes: Number(task.duration_minutes),
        oplaag_pcs: Number(task.oplaag_pcs),
        up_per_plano: Number(task.up_per_plano),
        plan_plano: Number(task.plan_plano),
        muatan_koli: Number(task.muatan_koli),
        muatan_pallet: Number(task.muatan_pallet),
        ritase: Number(task.ritase),
        is_extended: Boolean(Number(task.is_extended))
      }))
    }
  } catch (e) {
    console.error('Failed to fetch schedules from API:', e)
  }
}

// Auto-fetch jika berjalan di browser
if (typeof window !== 'undefined') {
  setTimeout(() => { fetchSchedulesFromApi() }, 100)
}

// ==========================================
// 5. REACTIVE STATE & AUTO-EXTEND EVALUATION
// ==========================================
export const schedulesState = ref([...baseSchedules])

// Tanggal & Jam referensi sistem (Hari ini: 01 Nov 2026 jam 17:00 WIB)
export const CURRENT_SYSTEM_DATE = '2026-11-01'
export const CURRENT_SYSTEM_DATETIME = '2026-11-01 17:00'

/**
 * Memproses evaluasi timeline per jam & auto-extend per menit
 */
export const evaluatedSchedules = computed(() => {
  return schedulesState.value.map(task => {
    const isCompleted = task.status === 'selesai' || task.progress >= 100
    const isSkipped = task.status === 'lewati'
    
    // Periksa apakah waktu deadline rencana terlewati
    const endDateTime = task.planned_end_datetime || (task.planned_end_date + ' 23:59')
    const isOverdue = endDateTime <= CURRENT_SYSTEM_DATETIME
    
    if (!isCompleted && !isSkipped && isOverdue) {
      const remaining = Math.max(0, (task.plan_qty || 0) - (task.actual_output || 0))
      const machine = masterMesinPPIC.find(m => m.id === task.mesin_id)
      const speed = machine ? machine.speed_jph : 10000
      
      // Hitung perpanjangan menit yang dibutuhkan secara presisi
      const neededExtraMinutes = Math.max(30, Math.round((remaining / speed) * 60))
      const extendedDateTime = addMinutesToDateTime(CURRENT_SYSTEM_DATETIME, neededExtraMinutes)
      
      return {
        ...task,
        is_extended: true,
        extend_minutes: neededExtraMinutes,
        effective_end_datetime: extendedDateTime,
        effective_end_date: extendedDateTime.split(' ')[0],
        extend_reason: `Progress ${task.progress.toFixed(1)}% (kurang ${Number(remaining).toLocaleString('id-ID')} ${task.unit}). Auto-extend +${formatMinutesToHuman(neededExtraMinutes)} s/d ${extendedDateTime} WIB.`
      }
    }
    
    return {
      ...task,
      is_extended: false,
      extend_minutes: 0,
      effective_end_datetime: task.planned_end_datetime || (task.planned_end_date + ' 23:59'),
      effective_end_date: task.planned_end_date
    }
  })
})

/**
 * Menambah Job Order & Rangkaian Jadwal Presisi (Jam & Menit) dari Form Input PPIC
 */
export function addPpicJobOrderSchedule(payload) {
  const {
    no_pp,
    produk_nama,
    oplaag_pcs,
    up_per_plano,
    insheet_percent,
    start_datetime, // "YYYY-MM-DD HH:mm"
    deadline_date,  // "YYYY-MM-DD"
    deadline_time,  // "HH:mm"
    pcs_per_koli,
    koli_per_pallet,
    berat_pcs_gram,
    armada_id,
    lead_time_kirim_jam,
    stages // Array of stage config: { stage, mesin_id, enabled }
  } = payload

  // 1. Kalkulasi Plano
  const calc = calculatePlanoFromOplaag(oplaag_pcs, up_per_plano, insheet_percent)
  const totalPlano = calc.totalPlano

  // 2. Kalkulasi Muatan Logistik Pengiriman
  const logistics = calculateJobLogistics({
    oplaag_pcs,
    pcs_per_koli,
    koli_per_pallet,
    berat_pcs_gram,
    armada_id,
    lead_time_kirim_jam
  })

  const deadlineDateTime = `${deadline_date} ${deadline_time || '17:00'}`
  let currentDateTime = start_datetime || `${CURRENT_SYSTEM_DATE} 08:00`
  const newTasks = []
  let nextId = Date.now()

  // 3. Loop tahapan produksi berurutan (cascade jadwal per jam & menit)
  stages.forEach((st, idx) => {
    if (!st.enabled) return

    const machine = masterMesinPPIC.find(m => m.id === st.mesin_id)
    if (!machine) return

    const isPlanoStage = machine.unit === 'plano'
    const stageQty = isPlanoStage ? totalPlano : Number(oplaag_pcs)

    // Hitung durasi presisi menit
    const durationMinutes = calculateEstimatedMinutes(stageQty, machine.speed_jph)
    const endDateTime = addMinutesToDateTime(currentDateTime, durationMinutes)

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
      start_date: currentDateTime.split(' ')[0],
      start_datetime: currentDateTime,
      planned_end_date: endDateTime.split(' ')[0],
      planned_end_datetime: endDateTime,
      duration_minutes: durationMinutes,
      actual_end_date: null,
      deadline_datetime: deadlineDateTime,
      muatan_koli: logistics.totalKoli,
      muatan_pallet: logistics.totalPallet,
      armada: logistics.armada.nama,
      ritase: logistics.ritase
    })

    // Beri jeda/buffer setup 20 menit ke proses berikutnya
    currentDateTime = addMinutesToDateTime(endDateTime, 20)
  })

  // Simpan ke state teratas
  schedulesState.value.unshift(...newTasks)

  // Persist ke Database MySQL
  apiFetch('ppic.php?action=create_pp', {
    method: 'POST',
    body: JSON.stringify({
      no_pp,
      produk_nama: produk_nama || 'Packaging Box',
      oplaag_pcs: Number(oplaag_pcs),
      up_per_plano: Number(up_per_plano),
      total_plano: totalPlano,
      start_datetime: start_datetime,
      deadline_datetime: deadlineDateTime,
      muatan_koli: logistics.totalKoli,
      muatan_pallet: logistics.totalPallet,
      armada: logistics.armada.nama,
      ritase: logistics.ritase,
      stages: newTasks
    })
  }).then(() => {
    fetchSchedulesFromApi()
  }).catch(e => console.error('Failed to create PP in database:', e))

  return {
    tasks: newTasks,
    logistics,
    deadlineDateTime,
    finalEndDateTime: currentDateTime
  }
}

/**
 * Memperbarui Data Job Order (PP) secara terpusat
 */
export function updatePpicJobOrder(updatedPP) {
  const {
    no_pp,
    produk_nama,
    oplaag_pcs,
    up_per_plano,
    start_datetime,
    deadline_datetime,
    muatan_koli,
    muatan_pallet,
    armada,
    ritase
  } = updatedPP

  schedulesState.value = schedulesState.value.map(task => {
    if (task.no_pp === no_pp) {
      return {
        ...task,
        produk_nama: produk_nama !== undefined ? produk_nama : task.produk_nama,
        oplaag_pcs: oplaag_pcs !== undefined ? Number(oplaag_pcs) : task.oplaag_pcs,
        up_per_plano: up_per_plano !== undefined ? Number(up_per_plano) : task.up_per_plano,
        start_datetime: start_datetime !== undefined ? start_datetime : task.start_datetime,
        deadline_datetime: deadline_datetime !== undefined ? deadline_datetime : task.deadline_datetime,
        muatan_koli: muatan_koli !== undefined ? muatan_koli : task.muatan_koli,
        muatan_pallet: muatan_pallet !== undefined ? muatan_pallet : task.muatan_pallet,
        armada: armada !== undefined ? armada : task.armada,
        ritase: ritase !== undefined ? ritase : task.ritase
      }
    }
    return task
  })

  // Persist perubahan ke Database MySQL
  apiFetch('ppic.php?action=update_pp', {
    method: 'POST',
    body: JSON.stringify(updatedPP)
  }).catch(e => console.error('Failed to update PP in database:', e))
}

/**
 * Menghapus Job Order (PP) dari jadwal
 */
export function deletePpicJobOrder(no_pp) {
  schedulesState.value = schedulesState.value.filter(task => task.no_pp !== no_pp)
}
