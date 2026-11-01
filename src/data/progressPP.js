import { ref } from 'vue'

export const initialJobOrders = [
  {
    id: 1,
    no_pp: 'PP-2026-0821',
    produk_nama: 'Etiket Rokok Djarum 12 Super',
    status: 'aktif',
    plan_total: 50000,
    plan_unit: 'plano',
    up_per_plano: 4, // Input PPIC: 1 Plano isi 4 pcs
    alur_tipe: 'standar',
    alur_label: 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Lipat ➜ Kirim)',
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
      mesin: 'Bobst MS 1 (Die Plat 4 Up)',
      operator: 'Haryanto',
      opsi: 'Pond Potong & Stripping (Konversi Plano ➜ Pcs)',
      status: 'proses',
      input_unit: 'plano',
      unit: 'pcs',
      input_plano: 44200, // Hasil baik dari completing
      potong_plano: 38000, // Realisasi potong dalam plano
      good_plano: 37400, // Lembar plano baik
      reject_plano: 600, // Lembar plano afval
      up_per_plano: 4, // 1 plano isi 4 pcs (PPIC)
      plan: 176800, // 44200 plano x 4 up = 176800 pcs
      output: 152000, // 38000 plano x 4 up = 152000 pcs
      good: 149600, // 37400 plano x 4 up = 149600 pcs
      reject: 2400, // 600 plano x 4 up = 2400 pcs
      progress: 86.0
    },
    sortir: {
      stage: 'Sortir',
      tag: 'SRT',
      mesin: 'Focusight FS-500 Shark',
      operator: 'Bambang S.',
      opsi: 'Inspeksi Kualitas Sheet Hasil Cabut Sebelum Dilipat',
      status: 'proses',
      unit: 'pcs',
      plan: 149600, // Hasil baik dari Cabut (pcs)
      s1: { output: 120000, good: 118000, reject: 2000 },
      s2: { output: 2000, good: 1700, reject: 300 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 122000,
      total_good: 119700,
      total_reject: 2300,
      progress: 81.5
    },
    lipat: {
      stage: 'Lipat',
      tag: 'LPT',
      mesin: 'Herui Speed Gluer',
      operator: 'Sugeng',
      opsi: 'Straight Line Gluing & Folding Box',
      status: 'proses',
      unit: 'pcs',
      plan: 119700, // Menerima hasil baik sortir
      output: 100000,
      good: 99200,
      reject: 800,
      progress: 83.5
    }
  },
  {
    id: 2,
    no_pp: 'PP-2026-0824',
    produk_nama: 'Box Farmasi Kalbe Farma 500mg',
    status: 'aktif',
    plan_total: 40000,
    plan_unit: 'plano',
    up_per_plano: 4, // Input PPIC: 1 Plano isi 4 pcs
    alur_tipe: 'standar',
    alur_label: 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir Sheet ➜ Lipat ➜ Kirim)',
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
      potong_plano: 38800, // Realisasi potong plano
      good_plano: 38200, // Plano baik
      reject_plano: 600, // Plano reject
      up_per_plano: 4, // 1 plano = 4 pcs
      plan: 155200, // 38800 x 4
      output: 155200, // 38800 x 4
      good: 152800, // 38200 x 4
      reject: 2400, // 600 x 4
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
      plan: 152800, // Langsung terima dari Cabut (pcs)
      s1: { output: 140000, good: 138000, reject: 2000 },
      s2: { output: 2000, good: 1680, reject: 320 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 142000,
      total_good: 139680,
      total_reject: 2320,
      progress: 92.9
    },
    lipat: {
      stage: 'Lipat',
      tag: 'LPT',
      mesin: 'Mesin Lipat 2 Straight Line',
      operator: 'Nurhadi',
      opsi: 'Lipat & Lem Hasil Sheet yang Sudah Disortir',
      status: 'proses',
      unit: 'pcs',
      plan: 139680, // Menerima sheet lolos sortir
      output: 112000,
      good: 110800,
      reject: 1200,
      progress: 80.2
    }
  },
  {
    id: 3,
    no_pp: 'PP-2026-0828',
    produk_nama: 'Outer Corrugated Box Indofood Flat Sheet',
    status: 'aktif',
    plan_total: 60000,
    plan_unit: 'plano',
    up_per_plano: 2, // Input PPIC: 1 Plano isi 2 pcs
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
      potong_plano: 0,
      good_plano: 0,
      reject_plano: 0,
      up_per_plano: 2,
      plan: 88400, // 44200 x 2
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    },
    sortir: {
      stage: 'Sortir',
      tag: 'SRT',
      mesin: 'Luster Raptor Optical',
      operator: 'Dwi Cahyono',
      opsi: 'Sortir Cacat Dimensi Lembaran Flat Karton',
      status: 'antri',
      unit: 'pcs',
      plan: 88400, // Menerima langsung dari Cabut (pcs)
      s1: { output: 0, good: 0, reject: 0 },
      s2: { output: 0, good: 0, reject: 0 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 0,
      total_good: 0,
      total_reject: 0,
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
    }
  },
  {
    id: 4,
    no_pp: 'PP-2026-0831',
    produk_nama: 'Inner Frame Gudang Garam Merah',
    status: 'aktif',
    plan_total: 35000,
    plan_unit: 'plano',
    up_per_plano: 6, // Input PPIC: 1 Plano isi 6 pcs
    alur_tipe: 'standar',
    alur_label: 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Lipat ➜ Kirim)',
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
      potong_plano: 0,
      good_plano: 0,
      reject_plano: 0,
      up_per_plano: 6,
      plan: 164400, // 27400 x 6
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    },
    sortir: {
      stage: 'Sortir',
      tag: 'SRT',
      mesin: 'Focusight FS-500',
      operator: 'Tim Sortir A',
      opsi: 'Inspeksi Presisi Register & Foil Sheet',
      status: 'antri',
      unit: 'pcs',
      plan: 164400, // 27400 x 6
      s1: { output: 0, good: 0, reject: 0 },
      s2: { output: 0, good: 0, reject: 0 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 0,
      total_good: 0,
      total_reject: 0,
      progress: 0
    },
    lipat: {
      stage: 'Lipat',
      tag: 'LPT',
      mesin: 'Mesin Lipat 3 Multi-Point',
      operator: 'Ari',
      opsi: 'High Speed Folding Inner Frame',
      status: 'antri',
      unit: 'pcs',
      plan: 164400,
      output: 0,
      good: 0,
      reject: 0,
      progress: 0
    }
  },
  {
    id: 5,
    no_pp: 'PP-2026-0815',
    produk_nama: 'Brosur Promosi Ekspor Quality',
    status: 'selesai',
    plan_total: 25000,
    plan_unit: 'plano',
    up_per_plano: 4, // Input PPIC: 1 Plano isi 4 pcs
    alur_tipe: 'standar',
    alur_label: 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Lipat ➜ Kirim)',
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
      good: 24300, // Hasil baik completing = 24.300 plano
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
      input_plano: 24300, // Input diterima dari completing
      potong_plano: 24300, // Realisasi potong: 24.300 plano
      good_plano: 24000, // Hasil baik plano: 24.000 plano
      reject_plano: 300, // Afval plano: 300 plano
      up_per_plano: 4, // Input PPIC: 1 Plano isi 4 pcs
      plan: 97200, // 24300 x 4 = 97.200 pcs
      output: 97200, // 24300 x 4 = 97.200 pcs
      good: 96000, // 24000 plano x 4 up = 96.000 pcs (konversi otomatis)
      reject: 1200, // 300 plano x 4 up = 1.200 pcs (konversi otomatis)
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
      plan: 96000, // Menerima hasil baik cabut (96.000 pcs)
      s1: { output: 96000, good: 95200, reject: 800 },
      s2: { output: 800, good: 720, reject: 80 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 96800,
      total_good: 95920,
      total_reject: 880,
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
      plan: 95920, // Menerima hasil baik sortir (95.920 pcs)
      output: 95920,
      good: 95500,
      reject: 420,
      progress: 100.0
    }
  },
  {
    id: 6,
    no_pp: 'PP-2026-0836',
    produk_nama: 'Packaging Kosmetik Wardah Series Exclusive',
    status: 'req',
    plan_total: 15000,
    plan_unit: 'plano',
    up_per_plano: 6, // Input PPIC: 1 Plano isi 6 pcs
    alur_tipe: 'standar',
    alur_label: 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Lipat ➜ Kirim)',
    created_at: '2026-10-31',
    last_update: '2026-10-31 09:15',
    cetak: {
      stage: 'Cetak',
      tag: 'CTK',
      mesin: 'SM 23 (Heidelberg)',
      operator: 'Slamet',
      opsi: 'Druk Spot UV + Pearl Silver Ink',
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
      mesin: 'Hot Stamping Foil 1 & UV Gloss',
      operator: 'Wahyudi',
      opsi: 'Embossing Logo & Hot Foil Hologram',
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
      potong_plano: 0,
      good_plano: 0,
      reject_plano: 0,
      up_per_plano: 6,
      plan: 90000, // 15000 x 6
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
      plan: 90000,
      s1: { output: 0, good: 0, reject: 0 },
      s2: { output: 0, good: 0, reject: 0 },
      s3: { output: 0, good: 0, reject: 0 },
      total_output: 0,
      total_good: 0,
      total_reject: 0,
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
      plan: 90000,
      output: 0,
      good: 0,
      reject: 0,
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
