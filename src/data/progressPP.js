import { ref } from 'vue'

export const initialJobOrders = [
  {
    id: 1,
    no_pp: 'PP-2026-0821',
    produk_nama: 'Etiket Rokok Djarum 12 Super',
    status: 'aktif',
    plan_total: 50000,
    plan_unit: 'plano',
    alur_tipe: 'standar',
    alur_label: 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Lipat ➜ Sortir ➜ Kirim)',
    created_at: '2026-10-25',
    last_update: '2026-10-31 14:30',
    cetak: {
      stage: 'Cetak',
      tag: 'CTK',
      mesin: 'SM 23 (Heidelberg)',
      operator: 'Slamet',
      opsi: 'Druk Offset 5 Warna + Waterbase Varnish',
      status: 'selesai',
      unit: 'plano',
      plan: 50000,
      output: 49500,
      good: 48500,
      reject: 1000,
      progress: 99.0
    },
    completing: {
      stage: 'Completing',
      tag: 'CMP',
      mesin: 'Hot Stamping Foil 1 & UV Gloss',
      operator: 'Wahyudi',
      opsi: 'Hot Foil Gold & Spot UV Glossy',
      status: 'proses',
      unit: 'plano',
      plan: 48500, // Hasil baik dari cetak
      output: 45000,
      good: 44200,
      reject: 800,
      progress: 92.8
    },
    cabut: {
      stage: 'Cabut',
      tag: 'CBT',
      mesin: 'Bobst MS 1 (Die Plat 12 Up)',
      operator: 'Haryanto',
      opsi: 'Pond Potong & Stripping (Plano ➜ Pcs)',
      status: 'proses',
      input_unit: 'plano',
      unit: 'pcs',
      input_plano: 44200, // Hasil baik dari completing
      plan: 44200,
      output: 38000,
      good: 37400,
      reject: 600,
      progress: 86.0
    },
    lipat: {
      stage: 'Lipat',
      tag: 'LPT',
      mesin: 'Herui Speed Gluer',
      operator: 'Sugeng',
      opsi: 'Straight Line Gluing & Folding Box',
      status: 'proses',
      unit: 'pcs',
      plan: 37400, // Hasil baik dari cabut
      output: 30000,
      good: 29600,
      reject: 400,
      progress: 80.2
    },
    sortir: {
      stage: 'Sortir',
      tag: 'SRT',
      mesin: 'Focusight FS-500 Shark',
      operator: 'Bambang S.',
      opsi: 'Final Quality Check Box Jadi',
      status: 'proses',
      unit: 'pcs',
      plan: 29600, // Hasil baik dari lipat
      s1: { output: 25000, good: 24300, reject: 700 },
      s2: { output: 700, good: 580, reject: 120 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 25700,
      total_good: 24880,
      total_reject: 820,
      progress: 84.5
    }
  },
  {
    id: 2,
    no_pp: 'PP-2026-0824',
    produk_nama: 'Box Farmasi Kalbe Farma 500mg',
    status: 'aktif',
    plan_total: 40000,
    plan_unit: 'plano',
    alur_tipe: 'sortir_dulu',
    alur_label: 'Sortir Dulu Baru Lipat (Cetak ➜ Completing ➜ Cabut ➜ Sortir Sheet ➜ Lipat ➜ Kirim)',
    created_at: '2026-10-26',
    last_update: '2026-10-31 15:10',
    cetak: {
      stage: 'Cetak',
      tag: 'CTK',
      mesin: 'SM 25 (Heidelberg)',
      operator: 'Agus',
      opsi: 'Druk Cetak 4 Warna CMYK Presisi',
      status: 'selesai',
      unit: 'plano',
      plan: 40000,
      output: 40200,
      good: 39500,
      reject: 700,
      progress: 100.0
    },
    completing: {
      stage: 'Completing',
      tag: 'CMP',
      mesin: 'Varnish UV Gloss Obat',
      operator: 'Yanto',
      opsi: 'Full Coating Varnish Waterbase Pharma',
      status: 'selesai',
      unit: 'plano',
      plan: 39500,
      output: 39200,
      good: 38800,
      reject: 400,
      progress: 99.2
    },
    cabut: {
      stage: 'Cabut',
      tag: 'CBT',
      mesin: 'Bobst MS 2 Presisi',
      operator: 'Kardi',
      opsi: 'Die-Cutting Presisi Kalbe (Plano ➜ Pcs)',
      status: 'selesai',
      input_unit: 'plano',
      unit: 'pcs',
      input_plano: 38800,
      plan: 38800,
      output: 38800,
      good: 38200,
      reject: 600,
      progress: 100.0
    },
    sortir: {
      stage: 'Sortir',
      tag: 'SRT',
      mesin: 'Focusight FS-600 Sheet',
      operator: 'Eko Prasetyo',
      opsi: 'Inspeksi Flat Sheet Hasil Pond Sebelum Lem',
      status: 'proses',
      unit: 'pcs',
      urutan: 'sebelum_lipat',
      plan: 38200, // Langsung terima dari cabut
      s1: { output: 35000, good: 34500, reject: 500 },
      s2: { output: 500, good: 420, reject: 80 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 35500,
      total_good: 34920,
      total_reject: 580,
      progress: 91.4
    },
    lipat: {
      stage: 'Lipat',
      tag: 'LPT',
      mesin: 'Mesin Lipat 2 Straight Line',
      operator: 'Nurhadi',
      opsi: 'Lipat & Lem Hasil Sheet yang Sudah Disortir',
      status: 'proses',
      unit: 'pcs',
      urutan: 'setelah_sortir',
      plan: 34920, // Menerima sheet yang sudah lolos sortir
      output: 28000,
      good: 27700,
      reject: 300,
      progress: 80.0
    }
  },
  {
    id: 3,
    no_pp: 'PP-2026-0828',
    produk_nama: 'Outer Corrugated Box Indofood Flat Sheet',
    status: 'aktif',
    plan_total: 60000,
    plan_unit: 'plano',
    alur_tipe: 'tanpa_lipat',
    alur_label: 'Sortir Saja Lalu Kirim (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Langsung Kirim Tanpa Lipat)',
    created_at: '2026-10-27',
    last_update: '2026-10-31 11:20',
    cetak: {
      stage: 'Cetak',
      tag: 'CTK',
      mesin: 'KBA Rapida 106',
      operator: 'Doni',
      opsi: 'Druk Cetak Kraft Sheet 3 Warna',
      status: 'selesai',
      unit: 'plano',
      plan: 60000,
      output: 59800,
      good: 58500,
      reject: 1300,
      progress: 99.7
    },
    completing: {
      stage: 'Completing',
      tag: 'CMP',
      mesin: 'Laminasi Flute E/F Flute',
      operator: 'Darto',
      opsi: 'Mounting Corrugated Laminasi Flute',
      status: 'proses',
      unit: 'plano',
      plan: 58500,
      output: 45000,
      good: 44200,
      reject: 800,
      progress: 76.9
    },
    cabut: {
      stage: 'Cabut',
      tag: 'CBT',
      mesin: 'Bobst MS 3 Double Crease',
      operator: 'Bambang',
      opsi: 'Pond Sheet Karton Tebal (Plano ➜ Pcs)',
      status: 'antri',
      input_unit: 'plano',
      unit: 'pcs',
      input_plano: 44200,
      plan: 44200,
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    },
    lipat: {
      stage: 'Lipat',
      tag: 'LPT',
      mesin: '-',
      operator: '-',
      opsi: 'Dilewati: Produk dikirim lembaran datar (flat packaging)',
      status: 'lewati',
      unit: 'pcs',
      plan: 0,
      output: 0,
      good: 0,
      reject: 0,
      progress: 100.0
    },
    sortir: {
      stage: 'Sortir',
      tag: 'SRT',
      mesin: 'Luster Raptor Optical',
      operator: 'Dwi Cahyono',
      opsi: 'Sortir Cacat Dimensi Lembaran Flat Karton',
      status: 'antri',
      unit: 'pcs',
      plan: 44200, // Menerima langsung dari Cabut
      s1: { output: 0, good: 0, reject: 0 },
      s2: { output: 0, good: 0, reject: 0 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 0,
      total_good: 0,
      total_reject: 0,
      progress: 0
    }
  },
  {
    id: 4,
    no_pp: 'PP-2026-0831',
    produk_nama: 'Inner Frame Gudang Garam Merah',
    status: 'aktif',
    plan_total: 35000,
    plan_unit: 'plano',
    alur_tipe: 'tanpa_sortir',
    alur_label: 'Lipat Langsung Terus Kirim (Cetak ➜ Completing ➜ Cabut ➜ Lipat ➜ Langsung Kirim Tanpa Sortir)',
    created_at: '2026-10-28',
    last_update: '2026-10-31 16:40',
    cetak: {
      stage: 'Cetak',
      tag: 'CTK',
      mesin: 'SM 10 (Heidelberg)',
      operator: 'Hendra',
      opsi: 'Druk 2 Warna Merah & Hitam Solid',
      status: 'proses',
      unit: 'plano',
      plan: 35000,
      output: 28000,
      good: 27400,
      reject: 600,
      progress: 80.0
    },
    completing: {
      stage: 'Completing',
      tag: 'CMP',
      mesin: 'Hot Stamping Foil 2',
      operator: 'Joko',
      opsi: 'Foil Logo Gudang Garam Emas',
      status: 'antri',
      unit: 'plano',
      plan: 27400,
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    },
    cabut: {
      stage: 'Cabut',
      tag: 'CBT',
      mesin: 'Bobst MS 4',
      operator: 'Suwarno',
      opsi: 'Die-cut Inner Frame (Plano ➜ Pcs)',
      status: 'antri',
      input_unit: 'plano',
      unit: 'pcs',
      input_plano: 27400,
      plan: 27400,
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    },
    lipat: {
      stage: 'Lipat',
      tag: 'LPT',
      mesin: 'Mesin Lipat 3 Multi-Point',
      operator: 'Ari',
      opsi: 'High Speed Folding + Sensor Cacat Otomatis (Inline QC)',
      status: 'antri',
      unit: 'pcs',
      plan: 27400,
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    },
    sortir: {
      stage: 'Sortir',
      tag: 'SRT',
      mesin: '-',
      operator: '-',
      opsi: 'Dilewati: QC telah terintegrasi di sensor mesin lipat',
      status: 'lewati',
      unit: 'pcs',
      plan: 0,
      s1: { output: 0, good: 0, reject: 0 },
      s2: { output: 0, good: 0, reject: 0 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 0,
      total_good: 0,
      total_reject: 0,
      progress: 100.0
    }
  },
  {
    id: 5,
    no_pp: 'PP-2026-0815',
    produk_nama: 'Brosur Promosi Ekspor Quality',
    status: 'selesai',
    plan_total: 25000,
    plan_unit: 'plano',
    alur_tipe: 'standar',
    alur_label: 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Lipat ➜ Sortir ➜ Kirim)',
    created_at: '2026-10-20',
    last_update: '2026-10-29 17:00',
    cetak: {
      stage: 'Cetak',
      tag: 'CTK',
      mesin: 'SM 23 (Heidelberg)',
      operator: 'Slamet',
      opsi: 'Druk Brosur Art Paper 150gr',
      status: 'selesai',
      unit: 'plano',
      plan: 25000,
      output: 25200,
      good: 24700,
      reject: 500,
      progress: 100.0
    },
    completing: {
      stage: 'Completing',
      tag: 'CMP',
      mesin: 'Laminasi Doff & Spot UV',
      operator: 'Wahyudi',
      opsi: 'Laminasi Matte Doff 2 Sisi',
      status: 'selesai',
      unit: 'plano',
      plan: 24700,
      output: 24600,
      good: 24300,
      reject: 300,
      progress: 100.0
    },
    cabut: {
      stage: 'Cabut',
      tag: 'CBT',
      mesin: 'Bobst MS 1',
      operator: 'Haryanto',
      opsi: 'Potong Rel Lipatan (Plano ➜ Pcs)',
      status: 'selesai',
      input_unit: 'plano',
      unit: 'pcs',
      input_plano: 24300,
      plan: 24300,
      output: 24300,
      good: 24000,
      reject: 300,
      progress: 100.0
    },
    lipat: {
      stage: 'Lipat',
      tag: 'LPT',
      mesin: 'Mesin Lipat Brosur Z-Fold',
      operator: 'Sugeng',
      opsi: 'Lipat Z-Fold 3 Lipatan',
      status: 'selesai',
      unit: 'pcs',
      plan: 24000,
      output: 24000,
      good: 23900,
      reject: 100,
      progress: 100.0
    },
    sortir: {
      stage: 'Sortir',
      tag: 'SRT',
      mesin: 'Meja Sortir Manual Lini B',
      operator: 'Tim Sortir B',
      opsi: 'Inspeksi Lipatan & Packing Kraft',
      status: 'selesai',
      unit: 'pcs',
      plan: 23900,
      s1: { output: 23900, good: 23700, reject: 200 },
      s2: { output: 200, good: 180, reject: 20 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 24100,
      total_good: 23880,
      total_reject: 220,
      progress: 100.0
    }
  },
  {
    id: 6,
    no_pp: 'PP-2026-0835',
    produk_nama: 'Custom Rigid Box Gift Set',
    status: 'req',
    plan_total: 15000,
    plan_unit: 'plano',
    alur_tipe: 'standar',
    alur_label: 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Lipat ➜ Sortir ➜ Kirim)',
    created_at: '2026-10-31',
    last_update: '2026-10-31 09:00',
    cetak: {
      stage: 'Cetak',
      tag: 'CTK',
      mesin: 'SM 27 (Heidelberg)',
      operator: 'Triyono',
      opsi: 'Druk Greyboard & Linen Paper',
      status: 'antri',
      unit: 'plano',
      plan: 15000,
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    },
    completing: {
      stage: 'Completing',
      tag: 'CMP',
      mesin: 'Laminasi Transfer Hologram',
      operator: 'Kuswanto',
      opsi: 'Hologram Hot Foil Emboss',
      status: 'antri',
      unit: 'plano',
      plan: 15000,
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    },
    cabut: {
      stage: 'Cabut',
      tag: 'CBT',
      mesin: 'Bobst MS 1',
      operator: 'Haryanto',
      opsi: 'Die-cutting Pola Rigid (Plano ➜ Pcs)',
      status: 'antri',
      input_unit: 'plano',
      unit: 'pcs',
      input_plano: 15000,
      plan: 15000,
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    },
    lipat: {
      stage: 'Lipat',
      tag: 'LPT',
      mesin: 'Herui Speed Gluer',
      operator: 'Sugeng',
      opsi: 'Manual Gluing & Box Wrapping',
      status: 'antri',
      unit: 'pcs',
      plan: 15000,
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    },
    sortir: {
      stage: 'Sortir',
      tag: 'SRT',
      mesin: 'Focusight FS-500',
      operator: 'Bambang S.',
      opsi: 'Inspeksi Presisi Cover & Dimensi',
      status: 'antri',
      unit: 'pcs',
      plan: 15000,
      s1: { output: 0, good: 0, reject: 0 },
      s2: { output: 0, good: 0, reject: 0 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 0,
      total_good: 0,
      total_reject: 0,
      progress: 0
    }
  }
]

export const jobOrdersState = ref([...initialJobOrders])

export function getJobOrders() {
  return jobOrdersState.value
}

export function updateJobOrder(id, updatedFields) {
  const index = jobOrdersState.value.findIndex(j => j.id === id)
  if (index !== -1) {
    jobOrdersState.value[index] = { ...jobOrdersState.value[index], ...updatedFields }
  }
}

export function formatNumber(val) {
  if (val === undefined || val === null || isNaN(val)) return '0'
  return Number(val).toLocaleString('id-ID')
}

export function getPct(current, target) {
  const c = Number(current) || 0
  const t = Number(target) || 0
  if (t <= 0) return 0
  return (c / t) * 100
}

export function getStatusTone(status) {
  if (status === 'selesai') return 'tone-ok'
  if (status === 'proses' || status === 'aktif') return 'tone-brand'
  if (status === 'antri' || status === 'req') return 'tone-warn'
  if (status === 'lewati') return 'tone-skip'
  return 'tone-muted'
}

export function getStatusLabel(status) {
  if (status === 'selesai') return 'Selesai'
  if (status === 'proses') return 'Sedang Berjalan'
  if (status === 'aktif') return 'Aktif'
  if (status === 'antri') return 'Antri / Belum Mulai'
  if (status === 'req') return 'Menunggu ACC'
  if (status === 'lewati') return 'Dilewati (Tidak Lewat Alur)'
  return status || '-'
}
