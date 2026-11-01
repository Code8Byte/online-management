<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    // 1. POPULATE MESIN JIKA KURANG LENGKAP
    $machines = [
        // Cetak
        ['nama' => 'SM 23 (Heidelberg Speedmaster 102)', 'divisi' => 'cetak', 'speed_jph' => 8500, 'unit' => 'plano'],
        ['nama' => 'SM 25 (Heidelberg CD 102)', 'divisi' => 'cetak', 'speed_jph' => 9000, 'unit' => 'plano'],
        ['nama' => 'SM 27 (Heidelberg SM)', 'divisi' => 'cetak', 'speed_jph' => 7500, 'unit' => 'plano'],
        ['nama' => 'KBA Rapida 106 High Speed', 'divisi' => 'cetak', 'speed_jph' => 10000, 'unit' => 'plano'],
        ['nama' => 'SM 10 (Heidelberg 2 Warna)', 'divisi' => 'cetak', 'speed_jph' => 6500, 'unit' => 'plano'],

        // Completing
        ['nama' => 'Hot Stamping Foil 1 & UV Gloss', 'divisi' => 'completing', 'speed_jph' => 4500, 'unit' => 'plano'],
        ['nama' => 'Hot Stamping Foil 2', 'divisi' => 'completing', 'speed_jph' => 4000, 'unit' => 'plano'],
        ['nama' => 'Varnish UV Gloss Obat Pharma', 'divisi' => 'completing', 'speed_jph' => 6000, 'unit' => 'plano'],
        ['nama' => 'Laminasi Flute E/F Corrugated', 'divisi' => 'completing', 'speed_jph' => 4000, 'unit' => 'plano'],
        ['nama' => 'Laminasi Transfer Hologram', 'divisi' => 'completing', 'speed_jph' => 3500, 'unit' => 'plano'],

        // Cabut
        ['nama' => 'Bobst MS 1 (Die Plat 4 Up)', 'divisi' => 'cabut', 'speed_jph' => 6000, 'unit' => 'plano'],
        ['nama' => 'Bobst MS 2 Presisi', 'divisi' => 'cabut', 'speed_jph' => 6500, 'unit' => 'plano'],
        ['nama' => 'Bobst MS 3 Double Crease', 'divisi' => 'cabut', 'speed_jph' => 5500, 'unit' => 'plano'],
        ['nama' => 'Bobst MS 4 High Speed', 'divisi' => 'cabut', 'speed_jph' => 5500, 'unit' => 'plano'],

        // Sortir
        ['nama' => 'Focusight FS-500 Shark', 'divisi' => 'sortir', 'speed_jph' => 40000, 'unit' => 'pcs'],
        ['nama' => 'Focusight FS-600 Sheet', 'divisi' => 'sortir', 'speed_jph' => 35000, 'unit' => 'pcs'],
        ['nama' => 'Luster Raptor Optical', 'divisi' => 'sortir', 'speed_jph' => 35000, 'unit' => 'pcs'],
        ['nama' => 'Meja Sortir Manual Lini A/B', 'divisi' => 'sortir', 'speed_jph' => 15000, 'unit' => 'pcs'],

        // Lipat
        ['nama' => 'Herui Speed Gluer', 'divisi' => 'lipat', 'speed_jph' => 35000, 'unit' => 'pcs'],
        ['nama' => 'Mesin Lipat 1 Crash Lock', 'divisi' => 'lipat', 'speed_jph' => 30000, 'unit' => 'pcs'],
        ['nama' => 'Mesin Lipat 2 Straight Line', 'divisi' => 'lipat', 'speed_jph' => 35000, 'unit' => 'pcs'],
        ['nama' => 'Mesin Lipat 3 Multi-Point', 'divisi' => 'lipat', 'speed_jph' => 25000, 'unit' => 'pcs'],
    ];

    foreach ($machines as $m) {
        $stmt = $pdo->prepare("SELECT id FROM mesin WHERE nama = ? LIMIT 1");
        $stmt->execute([$m['nama']]);
        if ($row = $stmt->fetch()) {
            $pdo->prepare("UPDATE mesin SET divisi = ?, speed_jph = ?, unit = ?, aktif = 1 WHERE id = ?")
                ->execute([$m['divisi'], $m['speed_jph'], $m['unit'], $row['id']]);
        } else {
            $pdo->prepare("INSERT INTO mesin (nama, divisi, speed_jph, unit, aktif) VALUES (?, ?, ?, ?, 1)")
                ->execute([$m['nama'], $m['divisi'], $m['speed_jph'], $m['unit']]);
        }
    }

    // Update existing Mesin Sortir 01/02/03 to sortir division
    $pdo->exec("UPDATE mesin SET divisi = 'sortir' WHERE divisi IS NULL OR divisi = ''");

    // 2. POPULATE PRODUK
    $products = [
        ['nama' => 'Etiket Rokok Djarum 12 Super', 'famili' => 'Cigarette Packaging', 'std_jph' => 38000],
        ['nama' => 'Box Farmasi Kalbe Farma 500mg', 'famili' => 'Pharma Box', 'std_jph' => 32000],
        ['nama' => 'Outer Corrugated Box Indofood Flat Sheet', 'famili' => 'Food & Beverage', 'std_jph' => 30000],
        ['nama' => 'Box Kemasan Teh Botol Sosro', 'famili' => 'Beverage Packaging', 'std_jph' => 35000],
        ['nama' => 'Box Ampul Farmasi 10ml', 'famili' => 'Pharma Ampul', 'std_jph' => 40000],
    ];

    $prodMap = [];
    foreach ($products as $p) {
        $stmt = $pdo->prepare("SELECT id FROM produk WHERE nama = ? LIMIT 1");
        $stmt->execute([$p['nama']]);
        if ($row = $stmt->fetch()) {
            $prodMap[$p['nama']] = (int)$row['id'];
        } else {
            $pdo->prepare("INSERT INTO produk (nama, famili, std_jph, aktif) VALUES (?, ?, ?, 1)")
                ->execute([$p['nama'], $p['famili'], $p['std_jph']]);
            $prodMap[$p['nama']] = (int)$pdo->lastInsertId();
        }
    }

    // Bersihkan data Job Order dan LKH lama agar reset bersih
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE antrian_pallet");
    $pdo->exec("TRUNCATE TABLE lkh_downtime");
    $pdo->exec("TRUNCATE TABLE lkh_cacat");
    $pdo->exec("TRUNCATE TABLE lkh_pp_items");
    $pdo->exec("TRUNCATE TABLE lkh");
    $pdo->exec("TRUNCATE TABLE pp_stages");
    $pdo->exec("TRUNCATE TABLE pp");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 3. POPULATE PP (JOB ORDERS)
    $jobOrders = [
        [
            'no_pp' => 'PP-2026-0821',
            'produk_nama' => 'Etiket Rokok Djarum 12 Super',
            'plan_total' => 50000,
            'oplaag_pcs' => 200000,
            'up_per_plano' => 4,
            'total_plano' => 50000,
            'status' => 'berjalan',
            'start_datetime' => '2026-11-01 08:00:00',
            'deadline_datetime' => '2026-11-03 16:00:00',
            'muatan_koli' => 200,
            'muatan_pallet' => 10,
            'armada' => 'Colt Diesel Double (CDD)',
            'ritase' => 1,
            'alur_tipe' => 'standar',
            'alur_label' => 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Lipat ➜ Kirim)',
            'is_extended' => 0
        ],
        [
            'no_pp' => 'PP-2026-0824',
            'produk_nama' => 'Box Farmasi Kalbe Farma 500mg',
            'plan_total' => 40000,
            'oplaag_pcs' => 240000,
            'up_per_plano' => 6,
            'total_plano' => 40000,
            'status' => 'berjalan',
            'start_datetime' => '2026-11-01 09:30:00',
            'deadline_datetime' => '2026-11-04 14:00:00',
            'muatan_koli' => 240,
            'muatan_pallet' => 12,
            'armada' => 'CDD Long Box 14 Pallet',
            'ritase' => 1,
            'alur_tipe' => 'standar',
            'alur_label' => 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Lipat ➜ Kirim)',
            'is_extended' => 0
        ],
        [
            'no_pp' => 'PP-2026-0828',
            'produk_nama' => 'Outer Corrugated Box Indofood Flat Sheet',
            'plan_total' => 25000,
            'oplaag_pcs' => 50000,
            'up_per_plano' => 2,
            'total_plano' => 25000,
            'status' => 'berjalan',
            'start_datetime' => '2026-11-01 10:00:00',
            'deadline_datetime' => '2026-11-03 12:00:00',
            'muatan_koli' => 100,
            'muatan_pallet' => 5,
            'armada' => 'Truk Engkel Box (CDE)',
            'ritase' => 1,
            'alur_tipe' => 'sortir_langsung_kirim',
            'alur_label' => 'Sortir Saja Langsung Kirim (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Kirim)',
            'is_extended' => 0
        ],
        [
            'no_pp' => 'PP-2026-0835',
            'produk_nama' => 'Box Kemasan Teh Botol Sosro',
            'plan_total' => 45000,
            'oplaag_pcs' => 180000,
            'up_per_plano' => 4,
            'total_plano' => 45000,
            'status' => 'berjalan',
            'start_datetime' => '2026-11-01 08:00:00',
            'deadline_datetime' => '2026-11-03 18:00:00',
            'muatan_koli' => 180,
            'muatan_pallet' => 9,
            'armada' => 'Colt Diesel Double (CDD)',
            'ritase' => 1,
            'alur_tipe' => 'standar',
            'alur_label' => 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Lipat ➜ Kirim)',
            'is_extended' => 1
        ],
        [
            'no_pp' => 'PP-2026-0841',
            'produk_nama' => 'Box Ampul Farmasi 10ml',
            'plan_total' => 17000,
            'oplaag_pcs' => 100000,
            'up_per_plano' => 6,
            'total_plano' => 17000,
            'status' => 'selesai',
            'start_datetime' => '2026-11-01 11:30:00',
            'deadline_datetime' => '2026-11-02 17:00:00',
            'muatan_koli' => 200,
            'muatan_pallet' => 10,
            'armada' => 'Colt Diesel Double (CDD)',
            'ritase' => 1,
            'alur_tipe' => 'standar',
            'alur_label' => 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Lipat ➜ Kirim)',
            'is_extended' => 0
        ],
    ];

    $ppMap = [];
    foreach ($jobOrders as $jo) {
        $pId = $prodMap[$jo['produk_nama']] ?? 1;
        $stmt = $pdo->prepare("SELECT id FROM pp WHERE no_pp = ? LIMIT 1");
        $stmt->execute([$jo['no_pp']]);
        if ($row = $stmt->fetch()) {
            $pdo->prepare("UPDATE pp SET produk_id = ?, plan_total = ?, oplaag_pcs = ?, up_per_plano = ?, total_plano = ?, status = ?, start_datetime = ?, deadline_datetime = ?, muatan_koli = ?, muatan_pallet = ?, armada = ?, ritase = ?, alur_tipe = ?, alur_label = ?, is_extended = ? WHERE id = ?")
                ->execute([
                    $pId, $jo['plan_total'], $jo['oplaag_pcs'], $jo['up_per_plano'], $jo['total_plano'],
                    $jo['status'], $jo['start_datetime'], $jo['deadline_datetime'], $jo['muatan_koli'],
                    $jo['muatan_pallet'], $jo['armada'], $jo['ritase'], $jo['alur_tipe'], $jo['alur_label'],
                    $jo['is_extended'], $row['id']
                ]);
            $ppMap[$jo['no_pp']] = (int)$row['id'];
        } else {
            $pdo->prepare("INSERT INTO pp (no_pp, produk_id, plan_total, oplaag_pcs, up_per_plano, total_plano, status, start_datetime, deadline_datetime, muatan_koli, muatan_pallet, armada, ritase, alur_tipe, alur_label, is_extended) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([
                    $jo['no_pp'], $pId, $jo['plan_total'], $jo['oplaag_pcs'], $jo['up_per_plano'], $jo['total_plano'],
                    $jo['status'], $jo['start_datetime'], $jo['deadline_datetime'], $jo['muatan_koli'],
                    $jo['muatan_pallet'], $jo['armada'], $jo['ritase'], $jo['alur_tipe'], $jo['alur_label'],
                    $jo['is_extended']
                ]);
            $ppMap[$jo['no_pp']] = (int)$pdo->lastInsertId();
        }
    }

    // 4. POPULATE PP_STAGES
    $stagesData = [
        // PP-2026-0821
        [
            'pp_no' => 'PP-2026-0821', 'stage' => 'cetak', 'stage_num' => 1,
            'mesin_id' => 'sm23', 'mesin_nama' => 'SM 23 (Heidelberg Speedmaster 102)', 'operator' => 'Tito & Slamet',
            'opsi' => 'Druk Offset 5 Warna + Waterbase Varnish', 'unit' => 'plano',
            'plan_qty' => 50000, 'actual_output' => 49500, 'good' => 48500, 'reject' => 1000, 'progress' => 99.0, 'status' => 'selesai',
            'start_datetime' => '2026-11-01 08:00:00', 'planned_end_datetime' => '2026-11-01 13:52:00', 'actual_end_datetime' => '2026-11-01 13:52:00', 'duration_minutes' => 352
        ],
        [
            'pp_no' => 'PP-2026-0821', 'stage' => 'completing', 'stage_num' => 2,
            'mesin_id' => 'hsf1', 'mesin_nama' => 'Hot Stamping Foil 1 & UV Gloss', 'operator' => 'Nico & Wahyudi',
            'opsi' => 'Hot Foil Gold & Spot UV Glossy', 'unit' => 'plano',
            'plan_qty' => 48500, 'actual_output' => 45000, 'good' => 44200, 'reject' => 800, 'progress' => 92.8, 'status' => 'proses',
            'start_datetime' => '2026-11-01 14:15:00', 'planned_end_datetime' => '2026-11-02 01:01:00', 'actual_end_datetime' => NULL, 'duration_minutes' => 646
        ],
        [
            'pp_no' => 'PP-2026-0821', 'stage' => 'cabut', 'stage_num' => 3,
            'mesin_id' => 'ms1', 'mesin_nama' => 'Bobst MS 1 (Die Plat 4 Up)', 'operator' => 'Haryanto',
            'opsi' => 'Pond Potong & Stripping (Konversi Plano ➜ Pcs)', 'unit' => 'plano',
            'plan_qty' => 44200, 'actual_output' => 40000, 'good' => 39250, 'reject' => 750, 'progress' => 90.5, 'status' => 'proses',
            'start_datetime' => '2026-11-02 01:30:00', 'planned_end_datetime' => '2026-11-02 08:52:00', 'actual_end_datetime' => NULL, 'duration_minutes' => 442
        ],
        [
            'pp_no' => 'PP-2026-0821', 'stage' => 'sortir', 'stage_num' => 4,
            'mesin_id' => 'sortir_fs500', 'mesin_nama' => 'Focusight FS-500 Shark', 'operator' => 'Reno & Tim Sortir',
            'opsi' => 'Kamera High Speed & Meja Manual', 'unit' => 'pcs',
            'plan_qty' => 157000, 'actual_output' => 140000, 'good' => 136500, 'reject' => 3500, 'progress' => 89.2, 'status' => 'proses',
            'start_datetime' => '2026-11-02 09:15:00', 'planned_end_datetime' => '2026-11-02 13:10:00', 'actual_end_datetime' => NULL, 'duration_minutes' => 235,
            's1_good' => 136500, 's1_reject' => 3500, 's2_good' => 0, 's2_reject' => 0, 's3_good' => 0, 's3_reject' => 0
        ],
        [
            'pp_no' => 'PP-2026-0821', 'stage' => 'lipat', 'stage_num' => 5,
            'mesin_id' => 'gluer_herui', 'mesin_nama' => 'Herui Speed Gluer', 'operator' => 'Jevon & Eko',
            'opsi' => 'Lem Samping High Speed Cold Glue', 'unit' => 'pcs',
            'plan_qty' => 136500, 'actual_output' => 95000, 'good' => 94000, 'reject' => 1000, 'progress' => 69.6, 'status' => 'proses',
            'start_datetime' => '2026-11-02 13:30:00', 'planned_end_datetime' => '2026-11-02 17:24:00', 'actual_end_datetime' => NULL, 'duration_minutes' => 234
        ],

        // PP-2026-0824
        [
            'pp_no' => 'PP-2026-0824', 'stage' => 'cetak', 'stage_num' => 1,
            'mesin_id' => 'sm25', 'mesin_nama' => 'SM 25 (Heidelberg CD 102)', 'operator' => 'Yoel & Agus',
            'opsi' => 'Druk 6 Warna UV + Primer Coating', 'unit' => 'plano',
            'plan_qty' => 40000, 'actual_output' => 40200, 'good' => 39500, 'reject' => 700, 'progress' => 100.0, 'status' => 'selesai',
            'start_datetime' => '2026-11-01 09:30:00', 'planned_end_datetime' => '2026-11-01 13:56:00', 'actual_end_datetime' => '2026-11-01 13:56:00', 'duration_minutes' => 266
        ],
        [
            'pp_no' => 'PP-2026-0824', 'stage' => 'completing', 'stage_num' => 2,
            'mesin_id' => 'uv_obat', 'mesin_nama' => 'Varnish UV Gloss Obat Pharma', 'operator' => 'Sodok & Rudi',
            'opsi' => 'Full Varnish Calender & Anti Jamur', 'unit' => 'plano',
            'plan_qty' => 39500, 'actual_output' => 38000, 'good' => 37600, 'reject' => 400, 'progress' => 96.2, 'status' => 'proses',
            'start_datetime' => '2026-11-01 14:15:00', 'planned_end_datetime' => '2026-11-01 20:50:00', 'actual_end_datetime' => NULL, 'duration_minutes' => 395
        ],
        [
            'pp_no' => 'PP-2026-0824', 'stage' => 'cabut', 'stage_num' => 3,
            'mesin_id' => 'ms2', 'mesin_nama' => 'Bobst MS 2 Presisi', 'operator' => 'Bambang',
            'opsi' => 'Pond 6 Up Die & Emboss Braille Pharma', 'unit' => 'plano',
            'plan_qty' => 37600, 'actual_output' => 30000, 'good' => 29500, 'reject' => 500, 'progress' => 79.8, 'status' => 'proses',
            'start_datetime' => '2026-11-01 21:15:00', 'planned_end_datetime' => '2026-11-02 03:02:00', 'actual_end_datetime' => NULL, 'duration_minutes' => 347
        ],
        [
            'pp_no' => 'PP-2026-0824', 'stage' => 'sortir', 'stage_num' => 4,
            'mesin_id' => 'sortir_fs600', 'mesin_nama' => 'Focusight FS-600 Sheet', 'operator' => 'Reno & Tim Sortir',
            'opsi' => 'Deteksi Barcode & Braille Kamera Sensor', 'unit' => 'pcs',
            'plan_qty' => 177000, 'actual_output' => 120000, 'good' => 118000, 'reject' => 2000, 'progress' => 67.8, 'status' => 'proses',
            'start_datetime' => '2026-11-02 03:30:00', 'planned_end_datetime' => '2026-11-02 08:34:00', 'actual_end_datetime' => NULL, 'duration_minutes' => 304,
            's1_good' => 118000, 's1_reject' => 2000, 's2_good' => 0, 's2_reject' => 0, 's3_good' => 0, 's3_reject' => 0
        ],
        [
            'pp_no' => 'PP-2026-0824', 'stage' => 'lipat', 'stage_num' => 5,
            'mesin_id' => 'gluer_ml1', 'mesin_nama' => 'Mesin Lipat 1 Crash Lock', 'operator' => 'Jevon',
            'opsi' => 'Crash Bottom Lock Pharma', 'unit' => 'pcs',
            'plan_qty' => 118000, 'actual_output' => 0, 'good' => 0, 'reject' => 0, 'progress' => 0.0, 'status' => 'antri',
            'start_datetime' => '2026-11-02 09:00:00', 'planned_end_datetime' => '2026-11-02 12:56:00', 'actual_end_datetime' => NULL, 'duration_minutes' => 236
        ],

        // PP-2026-0841 (Selesai semua)
        [
            'pp_no' => 'PP-2026-0841', 'stage' => 'cetak', 'stage_num' => 1,
            'mesin_id' => 'kba106', 'mesin_nama' => 'KBA Rapida 106 High Speed', 'operator' => 'Calvin & Doni',
            'opsi' => 'Druk 4 Warna Offset Pharma', 'unit' => 'plano',
            'plan_qty' => 17000, 'actual_output' => 17000, 'good' => 16800, 'reject' => 200, 'progress' => 100.0, 'status' => 'selesai',
            'start_datetime' => '2026-11-01 11:30:00', 'planned_end_datetime' => '2026-11-01 13:12:00', 'actual_end_datetime' => '2026-11-01 13:12:00', 'duration_minutes' => 102
        ],
        [
            'pp_no' => 'PP-2026-0841', 'stage' => 'cabut', 'stage_num' => 2,
            'mesin_id' => 'ms2', 'mesin_nama' => 'Bobst MS 2 Presisi', 'operator' => 'Bambang',
            'opsi' => 'Pond Potong 6 Up Presisi', 'unit' => 'plano',
            'plan_qty' => 16800, 'actual_output' => 16800, 'good' => 16700, 'reject' => 100, 'progress' => 100.0, 'status' => 'selesai',
            'start_datetime' => '2026-11-01 13:40:00', 'planned_end_datetime' => '2026-11-01 16:15:00', 'actual_end_datetime' => '2026-11-01 16:15:00', 'duration_minutes' => 155
        ],
        [
            'pp_no' => 'PP-2026-0841', 'stage' => 'sortir', 'stage_num' => 3,
            'mesin_id' => 'sortir_fs500', 'mesin_nama' => 'Focusight FS-500 Shark', 'operator' => 'Reno',
            'opsi' => 'Optical Sortir Ampul', 'unit' => 'pcs',
            'plan_qty' => 100200, 'actual_output' => 100200, 'good' => 99500, 'reject' => 700, 'progress' => 100.0, 'status' => 'selesai',
            'start_datetime' => '2026-11-01 16:30:00', 'planned_end_datetime' => '2026-11-01 19:00:00', 'actual_end_datetime' => '2026-11-01 19:00:00', 'duration_minutes' => 150,
            's1_good' => 99500, 's1_reject' => 700, 's2_good' => 0, 's2_reject' => 0, 's3_good' => 0, 's3_reject' => 0
        ],
        [
            'pp_no' => 'PP-2026-0841', 'stage' => 'lipat', 'stage_num' => 4,
            'mesin_id' => 'gluer_herui', 'mesin_nama' => 'Herui Speed Gluer', 'operator' => 'Jevon',
            'opsi' => 'Lipat Lem Gluer Straight', 'unit' => 'pcs',
            'plan_qty' => 99500, 'actual_output' => 99500, 'good' => 99200, 'reject' => 300, 'progress' => 100.0, 'status' => 'selesai',
            'start_datetime' => '2026-11-01 19:20:00', 'planned_end_datetime' => '2026-11-01 22:10:00', 'actual_end_datetime' => '2026-11-01 22:10:00', 'duration_minutes' => 170
        ],
    ];

    $pdo->exec("DELETE FROM pp_stages");
    foreach ($stagesData as $st) {
        $ppId = $ppMap[$st['pp_no']] ?? null;
        if (!$ppId) continue;
        $pdo->prepare("
            INSERT INTO pp_stages (
                pp_id, stage, stage_num, mesin_id, mesin_nama, operator, opsi, unit,
                plan_qty, actual_output, good, reject, progress, status,
                start_datetime, planned_end_datetime, actual_end_datetime, duration_minutes,
                s1_good, s1_reject, s2_good, s2_reject, s3_good, s3_reject
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $ppId, $st['stage'], $st['stage_num'], $st['mesin_id'], $st['mesin_nama'], $st['operator'], $st['opsi'], $st['unit'],
            $st['plan_qty'], $st['actual_output'], $st['good'], $st['reject'], $st['progress'], $st['status'],
            $st['start_datetime'], $st['planned_end_datetime'], $st['actual_end_datetime'], $st['duration_minutes'],
            $st['s1_good'] ?? 0, $st['s1_reject'] ?? 0, $st['s2_good'] ?? 0, $st['s2_reject'] ?? 0, $st['s3_good'] ?? 0, $st['s3_reject'] ?? 0
        ]);
    }

    // 5. POPULATE LKH DUMMY (UNTUK SETIAP DIVISI)
    $pdo->exec("DELETE FROM lkh");
    $pdo->exec("DELETE FROM lkh_pp_items");
    $pdo->exec("DELETE FROM lkh_downtime");
    $pdo->exec("DELETE FROM lkh_cacat");

    // LKH 1: Divisi Cetak (Tito)
    $pp1Id = $ppMap['PP-2026-0821'];
    $mesinCtk = $pdo->query("SELECT id FROM mesin WHERE divisi = 'cetak' LIMIT 1")->fetchColumn();
    $pdo->prepare("
        INSERT INTO lkh (divisi, pp_id, tahap_sortir, tanggal, shift, mesin_id, operator, start, finish, good, reject, setup_reject, total_layar, total_proses, waktu_prod_jam, jam_jalan, plan_shift)
        VALUES ('cetak', ?, 'CTK', '2026-10-31', 1, ?, 'Tito & Slamet', '2026-10-31 08:00:00', '2026-10-31 16:00:00', 48500, 1000, 200, 49700, 49700, 8.00, 7.25, 50000)
    ")->execute([$pp1Id, $mesinCtk]);
    $lkh1Id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO lkh_pp_items (lkh_id, pp_id, tahap_sortir, jam_mulai, jam_selesai, waktu_prod_jam, jam_jalan, good, reject, setup_reject, total_proses, plan_shift, actual_jph, availability, performance, quality_oee, oee) VALUES (?, ?, 'CTK', '2026-10-31 08:00:00', '2026-10-31 16:00:00', 8.00, 7.25, 48500, 1000, 200, 49700, 50000, 6855, 0.91, 0.99, 0.98, 0.88)")
        ->execute([$lkh1Id, $pp1Id]);

    // LKH 2: Divisi Completing (Nico)
    $mesinCmp = $pdo->query("SELECT id FROM mesin WHERE divisi = 'completing' LIMIT 1")->fetchColumn();
    $pdo->prepare("
        INSERT INTO lkh (divisi, pp_id, tahap_sortir, tanggal, shift, mesin_id, operator, start, finish, good, reject, setup_reject, total_layar, total_proses, waktu_prod_jam, jam_jalan, plan_shift)
        VALUES ('completing', ?, 'CMP', '2026-11-01', 1, ?, 'Nico & Wahyudi', '2026-11-01 08:00:00', '2026-11-01 16:00:00', 44200, 800, 150, 45150, 45150, 8.00, 7.10, 48500)
    ")->execute([$pp1Id, $mesinCmp]);
    $lkh2Id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO lkh_pp_items (lkh_id, pp_id, tahap_sortir, jam_mulai, jam_selesai, waktu_prod_jam, jam_jalan, good, reject, setup_reject, total_proses, plan_shift, actual_jph, availability, performance, quality_oee, oee) VALUES (?, ?, 'CMP', '2026-11-01 08:00:00', '2026-11-01 16:00:00', 8.00, 7.10, 44200, 800, 150, 45150, 48500, 6359, 0.89, 0.93, 0.98, 0.81)")
        ->execute([$lkh2Id, $pp1Id]);

    // LKH 3: Divisi Sortir (Reno)
    $mesinSrt = $pdo->query("SELECT id FROM mesin WHERE divisi = 'sortir' LIMIT 1")->fetchColumn();
    $pdo->prepare("
        INSERT INTO lkh (divisi, pp_id, tahap_sortir, tanggal, shift, mesin_id, operator, start, finish, good, reject, setup_reject, total_layar, total_proses, waktu_prod_jam, jam_jalan, plan_shift)
        VALUES ('sortir', ?, 'S1', '2026-11-01', 1, ?, 'Reno', '2026-11-01 07:00:00', '2026-11-01 15:00:00', 136500, 3500, 500, 140500, 140500, 8.00, 7.30, 150000)
    ")->execute([$pp1Id, $mesinSrt]);
    $lkh3Id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO lkh_pp_items (lkh_id, pp_id, tahap_sortir, jam_mulai, jam_selesai, waktu_prod_jam, jam_jalan, good, reject, setup_reject, total_proses, plan_shift, actual_jph, availability, performance, quality_oee, oee) VALUES (?, ?, 'S1', '2026-11-01 07:00:00', '2026-11-01 15:00:00', 8.00, 7.30, 136500, 3500, 500, 140500, 150000, 19246, 0.91, 0.94, 0.97, 0.83)")
        ->execute([$lkh3Id, $pp1Id]);

    // LKH 4: Divisi Lipat (Jevon)
    $mesinLpt = $pdo->query("SELECT id FROM mesin WHERE divisi = 'lipat' LIMIT 1")->fetchColumn();
    $pdo->prepare("
        INSERT INTO lkh (divisi, pp_id, tahap_sortir, tanggal, shift, mesin_id, operator, start, finish, good, reject, setup_reject, total_layar, total_proses, waktu_prod_jam, jam_jalan, plan_shift)
        VALUES ('lipat', ?, 'LPT', '2026-11-01', 2, ?, 'Jevon & Eko', '2026-11-01 15:00:00', '2026-11-01 23:00:00', 94000, 1000, 200, 95200, 95200, 8.00, 7.20, 100000)
    ")->execute([$pp1Id, $mesinLpt]);
    $lkh4Id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO lkh_pp_items (lkh_id, pp_id, tahap_sortir, jam_mulai, jam_selesai, waktu_prod_jam, jam_jalan, good, reject, setup_reject, total_proses, plan_shift, actual_jph, availability, performance, quality_oee, oee) VALUES (?, ?, 'LPT', '2026-11-01 15:00:00', '2026-11-01 23:00:00', 8.00, 7.20, 94000, 1000, 200, 95200, 100000, 13222, 0.90, 0.95, 0.99, 0.85)")
        ->execute([$lkh4Id, $pp1Id]);

    echo json_encode([
        "status" => "success",
        "message" => "Database seeded with rich dummy test data successfully!",
        "counts" => [
            "mesin" => $pdo->query("SELECT COUNT(*) FROM mesin")->fetchColumn(),
            "produk" => $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn(),
            "pp" => $pdo->query("SELECT COUNT(*) FROM pp")->fetchColumn(),
            "pp_stages" => $pdo->query("SELECT COUNT(*) FROM pp_stages")->fetchColumn(),
            "lkh" => $pdo->query("SELECT COUNT(*) FROM lkh")->fetchColumn(),
            "lkh_pp_items" => $pdo->query("SELECT COUNT(*) FROM lkh_pp_items")->fetchColumn(),
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "error" => $e->getMessage()]);
}
