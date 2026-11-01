<?php
require_once 'config.php';
checkAuth();
require_once __DIR__ . '/SimpleXlsxWriter.php';

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$shift = !empty($_GET['shift']) ? trim($_GET['shift']) : '1';
$mesin_id = $_GET['mesin_id'] ?? '';
$format = strtolower($_GET['format'] ?? 'html'); // 'html' (default print/pdf view) or 'xlsx'

$where = ["lkh.tanggal = ?", "lkh.shift = ?"];
$params = [$tanggal, $shift];
if (!empty($mesin_id)) {
    $where[] = "lkh.mesin_id = ?";
    $params[] = $mesin_id;
}

$rawPids = $_GET['produk_ids'] ?? $_GET['produk_id'] ?? null;
if (!empty($rawPids)) {
    $pids = is_array($rawPids) ? $rawPids : explode(',', (string)$rawPids);
    $pids = array_filter(array_map('intval', $pids), function($id) { return $id > 0; });
    if (!empty($pids)) {
        $inClause = implode(',', array_fill(0, count($pids), '?'));
        $where[] = "pp.produk_id IN ($inClause)";
        foreach ($pids as $pid) {
            $params[] = $pid;
        }
    }
}

$whereSql = implode(" AND ", $where);

// 1. Fetch LKH rows and per-PP items
$sql = "SELECT 
            lpi.id AS pp_item_id,
            lpi.lkh_id,
            lpi.pp_id,
            lpi.tahap_sortir,
            lpi.jam_mulai,
            lpi.jam_selesai,
            lpi.good,
            lpi.reject,
            lpi.setup_reject,
            lpi.total_layar,
            lpi.total_proses,
            lpi.setting_menit AS item_setting_menit,
            lpi.5s_menit AS item_5s_menit,
            lpi.total_dt_menit AS item_total_dt,
            lpi.jam_jalan AS item_jam_jalan,
            lpi.waktu_prod_jam AS item_waktu_prod_jam,
            lpi.urutan,
            lkh.tanggal,
            lkh.shift,
            lkh.mesin_id,
            lkh.operator,
            lkh.pic,
            lkh.start AS shift_start,
            lkh.finish AS shift_finish,
            lkh.setting_menit AS shift_setting_menit,
            lkh.5s_menit AS shift_5s_menit,
            lkh.lembur_menit,
            pp.no_pp,
            p.nama AS produk,
            p.famili,
            p.std_jph,
            m.nama AS mesin
        FROM lkh_pp_items lpi
        JOIN lkh ON lpi.lkh_id = lkh.id
        JOIN pp ON lpi.pp_id = pp.id
        JOIN produk p ON pp.produk_id = p.id
        JOIN mesin m ON lkh.mesin_id = m.id
        WHERE $whereSql
        ORDER BY m.nama ASC, lkh.start ASC, lpi.urutan ASC, lpi.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Catat Log Aktivitas Export CC
$actCcLog = ($format === 'xlsx') ? 'EXPORT_CC_EXCEL' : 'EXPORT_CC_PRINT';
$shiftCcLog = !empty($shift) ? "Shift {$shift}" : "Semua Shift";
$pCcLog = !empty($rawPids) ? "Produk: " . (is_array($rawPids) ? implode(',', $rawPids) : $rawPids) : "Semua Produk";
logActivity($pdo, $actCcLog, "Export Cost Control ({$format}) (Tanggal: {$tanggal}, {$shiftCcLog}, {$pCcLog}, Total: " . count($rows) . " data job order PP)");

// 2. Fetch Downtime and Defect Details per PP item
$ppItemIds = array_column($rows, 'pp_item_id');
$dtByItem = [];
$cacatByItem = [];

if (!empty($ppItemIds)) {
    $placeholders = implode(',', array_fill(0, count($ppItemIds), '?'));
    
    // Downtime details per PP item
    $stmtDt = $pdo->prepare("
        SELECT ld.lkh_pp_item_id, ld.kode_id, ld.menit, kd.kode, kd.arti
        FROM lkh_downtime ld
        JOIN kode_downtime kd ON ld.kode_id = kd.id
        WHERE ld.lkh_pp_item_id IN ($placeholders)
        ORDER BY kd.kode ASC
    ");
    $stmtDt->execute($ppItemIds);
    while ($dt = $stmtDt->fetch(PDO::FETCH_ASSOC)) {
        $dtByItem[$dt['lkh_pp_item_id']][] = $dt;
    }

    // Cacat / Defect details per PP item
    $stmtCacatVal = $pdo->prepare("
        SELECT lc.lkh_pp_item_id, lc.jenis_cacat_id, lc.qty, jc.nama
        FROM lkh_cacat lc
        JOIN jenis_cacat jc ON lc.jenis_cacat_id = jc.id
        WHERE lc.lkh_pp_item_id IN ($placeholders) AND lc.qty > 0
        ORDER BY jc.urutan ASC, jc.nama ASC
    ");
    $stmtCacatVal->execute($ppItemIds);
    while ($c = $stmtCacatVal->fetch(PDO::FETCH_ASSOC)) {
        $cacatByItem[$c['lkh_pp_item_id']][] = $c;
    }
}

// 3. Group strictly per Job Order (PP) per Mesin (Per PP dipisah detail downtime, waste, dan jam kerjanya)
$reportRows = [];
foreach ($rows as $r) {
    $tahap = !empty($r['tahap_sortir']) ? $r['tahap_sortir'] : 'S1';
    $key = $r['pp_item_id'];
    $ppDisplay = $r['no_pp'] . ($tahap !== 'S1' ? " [{$tahap}]" : "");

    // Calculate start - finish duration for this PP segment
    $s = strtotime($r['jam_mulai']);
    $f = strtotime($r['jam_selesai']);
    if ($f <= $s) $f += 86400; // overnight handling
    $dur = ($f - $s) / 3600;

    $persiapan = (int)($r['item_setting_menit'] ?? 0);
    $limaS = (int)($r['item_5s_menit'] ?? 0);
    $setup_reject = (int)($r['setup_reject'] ?? 0);
    $reject_wip = (int)($r['reject'] ?? 0);
    $good = (int)($r['good'] ?? 0);

    $dt_by_code = [];
    if (!empty($dtByItem[$r['pp_item_id']])) {
        foreach ($dtByItem[$r['pp_item_id']] as $dt) {
            $k = $dt['kode'];
            $dt_by_code[$k] = ($dt_by_code[$k] ?? 0) + (int)$dt['menit'];
        }
    }

    // Khusus export Cost Control: 5S & Administrasi Laporan otomatis masuk ke downtime LAIN / LAIN-LAIN
    if ($limaS > 0) {
        $dt_by_code['LAIN'] = ($dt_by_code['LAIN'] ?? 0) + $limaS;
    }

    $cacat_by_name = [];
    if (!empty($cacatByItem[$r['pp_item_id']])) {
        foreach ($cacatByItem[$r['pp_item_id']] as $c) {
            $cn = strtoupper(trim($c['nama']));
            $cacat_by_name[$cn] = ($cacat_by_name[$cn] ?? 0) + (int)$c['qty'];
        }
    }

    $jam_kerja = round($dur, 2);
    $total_dt = array_sum($dt_by_code);
    $jam_jalan = round($jam_kerja - ($total_dt + $persiapan) / 60, 2);
    if ($jam_jalan < 0) $jam_jalan = 0;
    $total_waste = $setup_reject + $reject_wip;

    $reportRows[$key] = [
        'pp_item_id' => $r['pp_item_id'],
        'mesin_id' => $r['mesin_id'],
        'nama_mesin' => $r['mesin'],
        'pp_id' => $r['pp_id'],
        'no_pp' => $r['no_pp'],
        'tahap_sortir' => $tahap,
        'pp_display' => $ppDisplay,
        'jam_mulai' => $r['jam_mulai'],
        'jam_selesai' => $r['jam_selesai'],
        'persiapan' => $persiapan,
        'setup_reject' => $setup_reject,
        'reject_wip' => $reject_wip,
        'good' => $good,
        'jam_kerja' => $jam_kerja,
        'total_dt' => $total_dt,
        'jam_jalan' => $jam_jalan,
        'total_waste' => $total_waste,
        'dt_by_code' => $dt_by_code,
        'cacat_by_name' => $cacat_by_name,
        'pic' => $r['pic'] ?: $r['operator']
    ];
}

// Compute Jam Kerja, Jam Jalan, and Total DT per PP row
$machineRows = array_values($reportRows);
$totalMesinUnique = count(array_unique(array_column($machineRows, 'mesin_id')));

// Format Tanggal Indonesia
$bulanIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$ts = strtotime($tanggal);
$tglIndo = date('j', $ts) . ' ' . ($bulanIndo[(int)date('n', $ts)] ?? date('F', $ts)) . ' ' . date('Y', $ts);
$shiftText = !empty($shift) ? "Shift $shift" : "Semua Shift";

$now = time();
$exportTimestamp = "Dicetak: " . date('j', $now) . " " . ($bulanIndo[(int)date('n', $now)] ?? date('F', $now)) . " " . date('Y', $now) . ", " . date('H:i', $now) . " WIB";

// Defect definitions
$waste_defects = [
    'PREMIER', 'SILVER', 'WARNA', 'UV', 'METASOL', 'BARET',
    'BLOBOR', 'NODA', 'EMBOSS', 'LAINNYA'
];
$known_defects = ['PREMIER', 'SILVER', 'WARNA', 'UV', 'METASOL', 'BARET', 'BLOBOR', 'NODA', 'EMBOSS'];

// Downtime definitions
$stmtDtCodes = $pdo->query("SELECT kode, arti FROM kode_downtime WHERE aktif = 1 ORDER BY kode ASC");
$dt_codes = [];
while ($row = $stmtDtCodes->fetch(PDO::FETCH_ASSOC)) {
    $dt_codes[] = [$row['kode'], $row['arti']];
}

// Determine PIC name
$pics = array_unique(array_filter(array_map('trim', array_column($rows, 'pic'))));
if (empty($pics)) {
    $pics = array_unique(array_filter(array_map('trim', array_column($rows, 'operator'))));
}
$picName = !empty($pics) ? implode(' / ', $pics) : 'adminsortir';

// =============================================================================
// BRANCH A: EXCEL (.xlsx) EXPORT (When requested with ?format=xlsx or ?format=excel)
// =============================================================================
if ($format === 'xlsx' || $format === 'excel') {
    $xlsx = new SimpleXlsxWriter();
    $xlsx->setFreezePane(4);
    $xlsx->setPageSetup('landscape', 9);

    $widths = [
        1 => 5,    // A: No
        2 => 28,   // B: Nama Mesin
        3 => 16,   // C: No PP
        4 => 14,   // D: Jam Kerja
        5 => 18,   // E: Total Down Time
        6 => 14,   // F: Jam Jalan
        7 => 14,   // G: Persiapan
        8 => 15,   // H: Hasil Baik / LAIN / SILVER
        9 => 11,   // I: PERBK / WARNA
        10 => 11,  // J: SERVC / UV
        11 => 11,  // K: SPC / METASOL
        12 => 11,  // L: STEL / BARET
        13 => 12,  // M: T.BAHAN / BLOBOR
        14 => 14,  // N: Total DT / NODA
        15 => 11,  // O: EMBOSS
        16 => 12   // P: LAINNYA
    ];
    $xlsx->setColWidths($widths);

    // Row 1: Title & Export Timestamp
    $xlsx->addMerge("A1:K1");
    $xlsx->addMerge("L1:P1");
    $xlsx->writeRow(1, [
        1 => ['value' => 'LAPORAN KERJA HARIAN - DEPARTEMEN LIPAT', 'style' => 22],
        12 => ['value' => $exportTimestamp, 'style' => 38]
    ], 24);

    // Row 2: Subtitle
    $xlsx->addMerge("A2:K2");
    $xlsx->writeRow(2, [
        1 => ['value' => "Tanggal: $tglIndo   |   Shift: $shiftText   |   Total: " . count($machineRows) . " PP (" . $totalMesinUnique . " Mesin)", 'style' => 23]
    ], 16);

    // Row 3: Blank separator
    $xlsx->writeRow(3, [], 8);

    // Header Row 4 (Dark Blue style 5)
    $t1_headers = [
        1 => ['value' => 'No', 'style' => 5],
        2 => ['value' => "Nama Mesin", 'style' => 5],
        3 => ['value' => "No PP", 'style' => 5],
        4 => ['value' => "Jam Kerja", 'style' => 5],
        5 => ['value' => "Total Down Time", 'style' => 5],
        6 => ['value' => "Jam Jalan", 'style' => 5],
        7 => ['value' => "Persiapan", 'style' => 5],
        8 => ['value' => "Hasil Baik (pcs)", 'style' => 5]
    ];
    $xlsx->writeRow(4, $t1_headers, 28);

    $numMachines = count($machineRows);
    $t1_start_row = 5;
    $t1_end_row = 5 + max(1, $numMachines) - 1;
    $t2_start_data_row = 10 + max(1, $numMachines);

    $m_idx = 0;
    foreach ($machineRows as $mId => $m) {
        $curRow = 5 + $m_idx;
        $refT2Row = $t2_start_data_row + $m_idx;
        
        $rowCells = [
            1 => ['value' => ($m_idx + 1), 'style' => 1],
            2 => ['value' => $m['nama_mesin'], 'style' => 45],
            3 => ['value' => $m['pp_display'], 'style' => 1],
            4 => ['value' => $m['jam_kerja'], 'type' => 'number', 'style' => 3],
            5 => ['value' => "=N{$refT2Row}", 'style' => 28],
            6 => ['value' => "=D{$curRow}-(E{$curRow}+G{$curRow})/60", 'style' => 31],
            7 => ['value' => $m['persiapan'], 'type' => 'number', 'style' => 28],
            8 => ['value' => $m['good'], 'type' => 'number', 'style' => 28]
        ];
        $xlsx->writeRow($curRow, $rowCells, 20);
        $m_idx++;
    }

    // Row TOTAL TABEL UTAMA
    $tot1_row = 5 + $numMachines;
    $xlsx->addMerge("A{$tot1_row}:C{$tot1_row}");
    $tot1Cells = [
        1 => ['value' => 'TOTAL', 'style' => 24],
        2 => ['style' => 24],
        3 => ['style' => 24],
        4 => ['value' => "=SUM(D5:D{$t1_end_row})", 'style' => 26],
        5 => ['value' => "=SUM(E5:E{$t1_end_row})", 'style' => 25],
        6 => ['value' => "=SUM(F5:F{$t1_end_row})", 'style' => 26],
        7 => ['value' => "=SUM(G5:G{$t1_end_row})", 'style' => 25],
        8 => ['value' => "=SUM(H5:H{$t1_end_row})", 'style' => 25]
    ];
    $xlsx->writeRow($tot1_row, $tot1Cells, 22);

    // Blank separator
    $sep1_row = $tot1_row + 1;
    $xlsx->writeRow($sep1_row, [], 10);

    // TABEL 2: DETAIL DOWNTIME
    $t2_title_row = $sep1_row + 1;
    $xlsx->addMerge("A{$t2_title_row}:N{$t2_title_row}");
    $t2_title_cells = [1 => ['value' => 'DETAIL DOWNTIME', 'style' => 6]];
    for ($c = 2; $c <= 14; $c++) $t2_title_cells[$c] = ['style' => 6];
    $xlsx->writeRow($t2_title_row, $t2_title_cells, 22);

    $t2_h1_row = $t2_title_row + 1;
    $t2_h2_row = $t2_title_row + 2;

    $xlsx->addMerge("A{$t2_h1_row}:A{$t2_h2_row}");
    $xlsx->addMerge("B{$t2_h1_row}:B{$t2_h2_row}");
    $xlsx->addMerge("C{$t2_h1_row}:C{$t2_h2_row}");
    $xlsx->addMerge("N{$t2_h1_row}:N{$t2_h2_row}");

    $t2_h1_cells = [
        1 => ['value' => 'No', 'style' => 7],
        2 => ['value' => 'Nama Mesin', 'style' => 7],
        3 => ['value' => 'No PP', 'style' => 7],
        14 => ['value' => 'Total DT', 'style' => 7]
    ];
    $t2_h2_cells = [
        1 => ['style' => 7],
        2 => ['style' => 7],
        3 => ['style' => 7],
        14 => ['style' => 7]
    ];

    foreach ($dt_codes as $i => $dt) {
        $col = 4 + $i;
        $t2_h1_cells[$col] = ['value' => $dt[0], 'style' => 7];
        $t2_h2_cells[$col] = ['value' => $dt[1], 'style' => 8];
    }

    $xlsx->writeRow($t2_h1_row, $t2_h1_cells, 18);
    $xlsx->writeRow($t2_h2_row, $t2_h2_cells, 20);

    $t2_data_start = $t2_h2_row + 1;
    $t2_data_end = $t2_data_start + $numMachines - 1;

    $m_idx = 0;
    foreach ($machineRows as $mId => $m) {
        $curRow = $t2_data_start + $m_idx;
        $rowCells = [
            1 => ['value' => ($m_idx + 1), 'style' => 1],
            2 => ['value' => $m['nama_mesin'], 'style' => 45],
            3 => ['value' => $m['pp_display'], 'style' => 1]
        ];

        foreach ($dt_codes as $i => $dt) {
            $col = 4 + $i;
            $val = (int)($m['dt_by_code'][$dt[0]] ?? 0);
            $rowCells[$col] = [
                'value' => $val,
                'type' => 'number',
                'style' => $val > 0 ? 32 : 2
            ];
        }

        $rowCells[14] = [
            'value' => "=SUM(D{$curRow}:M{$curRow})",
            'style' => 28
        ];

        $xlsx->writeRow($curRow, $rowCells, 20);
        $m_idx++;
    }

    // TOTAL TABEL 2
    $tot2_row = $t2_data_end + 1;
    $xlsx->addMerge("A{$tot2_row}:C{$tot2_row}");
    $tot2Cells = [
        1 => ['value' => 'TOTAL', 'style' => 24],
        2 => ['style' => 24],
        3 => ['style' => 24]
    ];
    for ($col = 4; $col <= 14; $col++) {
        $let = $xlsx->colLetter($col);
        $tot2Cells[$col] = [
            'value' => "=SUM({$let}{$t2_data_start}:{$let}{$t2_data_end})",
            'style' => 25
        ];
    }
    $xlsx->writeRow($tot2_row, $tot2Cells, 22);

    $sep2_row = $tot2_row + 1;
    $xlsx->writeRow($sep2_row, [], 10);

    // TABEL 3: DETAIL WASTE
    $total_waste_cols = 3 + 3 + count($waste_defects);
    $last_w_col_letter = $xlsx->colLetter($total_waste_cols);

    $t3_title_row = $sep2_row + 1;
    $xlsx->addMerge("A{$t3_title_row}:{$last_w_col_letter}{$t3_title_row}");
    $t3_title_cells = [1 => ['value' => 'DETAIL WASTE', 'style' => 9]];
    for ($c = 2; $c <= $total_waste_cols; $c++) $t3_title_cells[$c] = ['style' => 9];
    $xlsx->writeRow($t3_title_row, $t3_title_cells, 22);

    $t3_h_row = $t3_title_row + 1;
    $t3_h_cells = [
        1 => ['value' => 'No', 'style' => 10],
        2 => ['value' => 'Nama Mesin', 'style' => 10],
        3 => ['value' => 'No PP', 'style' => 10],
        4 => ['value' => "Rusakan Stell", 'style' => 10],
        5 => ['value' => "Reject WIP", 'style' => 10],
        6 => ['value' => "Total Waste", 'style' => 10]
    ];

    foreach ($waste_defects as $i => $dName) {
        $col = 7 + $i;
        $t3_h_cells[$col] = ['value' => $dName, 'style' => 10];
    }
    $xlsx->writeRow($t3_h_row, $t3_h_cells, 26);

    $t3_data_start = $t3_h_row + 1;
    $t3_data_end = $t3_data_start + $numMachines - 1;

    $m_idx = 0;
    foreach ($machineRows as $mId => $m) {
        $curRow = $t3_data_start + $m_idx;
        $rowCells = [
            1 => ['value' => ($m_idx + 1), 'style' => 1],
            2 => ['value' => $m['nama_mesin'], 'style' => 45],
            3 => ['value' => $m['pp_display'], 'style' => 1],
            4 => ['value' => $m['setup_reject'], 'type' => 'number', 'style' => 28],
            5 => ['value' => $m['reject_wip'], 'type' => 'number', 'style' => 28],
            6 => ['value' => "=D{$curRow}+E{$curRow}", 'style' => 28]
        ];

        $lainnyaQty = 0;
        foreach ($m['cacat_by_name'] as $cName => $qty) {
            if (!in_array($cName, $known_defects)) {
                $lainnyaQty += $qty;
            }
        }

        foreach ($waste_defects as $i => $dName) {
            $col = 7 + $i;
            $val = ($dName === 'LAINNYA') ? $lainnyaQty : (int)($m['cacat_by_name'][$dName] ?? 0);
            $rowCells[$col] = [
                'value' => $val,
                'type' => 'number',
                'style' => $val > 0 ? 33 : 2
            ];
        }

        $xlsx->writeRow($curRow, $rowCells, 20);
        $m_idx++;
    }

    // TOTAL TABEL 3
    $tot3_row = $t3_data_end + 1;
    $xlsx->addMerge("A{$tot3_row}:C{$tot3_row}");
    $tot3Cells = [
        1 => ['value' => 'TOTAL', 'style' => 24],
        2 => ['style' => 24],
        3 => ['style' => 24]
    ];

    for ($col = 4; $col <= $total_waste_cols; $col++) {
        $let = $xlsx->colLetter($col);
        $tot3Cells[$col] = [
            'value' => "=SUM({$let}{$t3_data_start}:{$let}{$t3_data_end})",
            'style' => 25
        ];
    }
    $xlsx->writeRow($tot3_row, $tot3Cells, 22);

    // Blank separator
    $sep3_row = $tot3_row + 1;
    $xlsx->writeRow($sep3_row, [], 12);

    // SIGNATURES
    $sig_h_row = $sep3_row + 1;
    $xlsx->addMerge("B{$sig_h_row}:D{$sig_h_row}");
    $xlsx->addMerge("G{$sig_h_row}:J{$sig_h_row}");

    $sigHCells = [
        2 => ['value' => 'Dibuat Oleh,', 'style' => 38],
        3 => ['style' => 38],
        4 => ['style' => 38],
        7 => ['value' => 'Diketahui Oleh,', 'style' => 38],
        8 => ['style' => 38],
        9 => ['style' => 38],
        10 => ['style' => 38]
    ];
    $xlsx->writeRow($sig_h_row, $sigHCells, 18);

    $xlsx->writeRow($sig_h_row + 1, [], 14);
    $xlsx->writeRow($sig_h_row + 2, [], 14);
    $xlsx->writeRow($sig_h_row + 3, [], 14);

    $sig_n_row = $sig_h_row + 4;
    $xlsx->addMerge("B{$sig_n_row}:D{$sig_n_row}");
    $xlsx->addMerge("G{$sig_n_row}:J{$sig_n_row}");

    $sigNCells = [
        2 => ['value' => $picName, 'style' => 38],
        3 => ['style' => 38],
        4 => ['style' => 38],
        7 => ['value' => '____________________________________', 'style' => 38],
        8 => ['style' => 38],
        9 => ['style' => 38],
        10 => ['style' => 38]
    ];
    $xlsx->writeRow($sig_n_row, $sigNCells, 18);

    $sig_t_row = $sig_n_row + 1;
    $xlsx->addMerge("B{$sig_t_row}:D{$sig_t_row}");
    $xlsx->addMerge("G{$sig_t_row}:J{$sig_t_row}");

    $sigTCells = [
        2 => ['value' => 'PIC', 'style' => 39],
        3 => ['style' => 39],
        4 => ['style' => 39],
        7 => ['value' => 'Cost Control', 'style' => 39],
        8 => ['style' => 39],
        9 => ['style' => 39],
        10 => ['style' => 39]
    ];
    $xlsx->writeRow($sig_t_row, $sigTCells, 16);

    // FOOTER ROW ON SHEET (Row 29)
    $footer_row = $sig_t_row + 2;
    $xlsx->addMerge("B{$footer_row}:D{$footer_row}");
    $xlsx->addMerge("G{$footer_row}:J{$footer_row}");
    $xlsx->addMerge("M{$footer_row}:P{$footer_row}");

    $footerCells = [
        2 => ['value' => 'PT PURA BARUTAMA UNIT OFFSET', 'style' => 38],
        7 => ['value' => 'CONFIDENTIAL', 'style' => 33], // bold red
        13 => ['value' => 'PRO/LKCL-REV_1', 'style' => 38]
    ];
    $xlsx->writeRow($footer_row, $footerCells, 18);

    $shiftSlug = !empty($shift) ? "_Shift$shift" : "";
    $filename = "Laporan_CC_Departemen_Lipat_{$tanggal}{$shiftSlug}.xlsx";
    $xlsx->output($filename);
    exit;
}

// =============================================================================
// BRANCH B: DIRECT PRINTABLE HTML / PDF VIEW (Default)
// Fits 100% cleanly onto 1 page A4 Landscape, ready to print or save as PDF
// =============================================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>LAPORAN KERJA HARIAN - DEPARTEMEN LIPAT</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
  <style>
    @page {
      size: A4 landscape;
      margin: 8mm 10mm 14mm 10mm;
      @bottom-right {
        content: counter(page) " / " counter(pages);
      }
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    body {
      font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #0f172a;
      background: #f8fafc;
      font-size: 8pt;
      line-height: 1.25;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    /* Screen-only Toolbar */
    .no-print-toolbar {
      background: #0f172a;
      color: #ffffff;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      position: sticky;
      top: 0;
      z-index: 999;
    }

    .toolbar-title {
      font-size: 10pt;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-action {
      background: #2563eb;
      color: #ffffff;
      border: none;
      padding: 6px 14px;
      border-radius: 4px;
      font-size: 8.5pt;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      font-family: inherit;
    }
    .btn-action:hover { background: #1d4ed8; }
    .btn-excel { background: #16a34a; }
    .btn-excel:hover { background: #15803d; }
    .btn-close { background: #475569; }
    .btn-close:hover { background: #334155; }

    /* Paper Container (Screen View) */
    .sheet-wrapper {
      padding: 15px 0;
      display: flex;
      justify-content: center;
      width: 100%;
    }

    .report-container {
      width: 100%;
      max-width: 277mm; /* A4 Landscape visual boundary on screen */
      background: #ffffff;
      padding: 10mm 10mm 12mm 10mm;
      box-shadow: 0 4px 14px rgba(0,0,0,0.08);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 188mm;
    }

    /* Header Section */
    .report-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 8px;
      padding-bottom: 6px;
      border-bottom: 2px solid #0f172a;
    }

    .title-area h1 {
      font-size: 12.5pt;
      font-weight: 800;
      color: #000000;
      letter-spacing: -0.2px;
      margin-bottom: 2px;
    }

    .subtitle {
      font-size: 8.5pt;
      color: #000000;
      font-weight: 500;
    }

    .export-time-badge {
      font-size: 7.5pt;
      color: #000000;
      padding: 3px 6px;
      border-radius: 4px;
      font-weight: 600;
      white-space: nowrap;
    }

    /* Tables Common */
    .table-section {
      width: 100%;
      margin-bottom: 8px;
      page-break-inside: auto;
      break-inside: auto;
    }

    table {
      width: 100%;
      table-layout: fixed;
      border-collapse: collapse;
      margin-bottom: 6px;
      word-wrap: break-word;
    }

    th, td {
      border: 1px solid #000000;
      padding: 3.5px 4px;
      font-size: 7.5pt;
      line-height: 1.2;
    }

    th {
      text-align: center;
      vertical-align: middle;
      font-weight: 700;
    }

    thead {
      display: table-header-group;
    }

    thead tr {
      page-break-inside: avoid;
      break-inside: avoid;
    }

    tbody tr {
      page-break-inside: avoid;
      break-inside: avoid;
    }

    tfoot {
      display: table-row-group;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    td.text-center { text-align: center; }
    td.text-right { text-align: right; }
    td.text-left { text-align: left; }
    td.font-bold { font-weight: 700; }

    .section-bar {
      color: #ffffff !important;
      font-weight: 800;
      text-align: center;
      padding: 4px 6px;
      font-size: 8pt;
      letter-spacing: 0.5px;
      page-break-after: avoid;
      break-after: avoid;
    }

    /* Tabel 1 (Utama) */
    .tbl-utama th {
      color: #000000 !important;
      font-size: 7.5pt;
    }

    /* Tabel 2 (Downtime) */
    .bar-downtime { color: #000000 !important;}
    .tbl-downtime th.th-dt-kode {
      color: #000000 !important;
      font-size: 7.5pt;
    }
    .tbl-downtime th.th-dt-arti {
      color: #000000 !important;
      font-size: 6.5pt;
      font-weight: 600;
      line-height: 1.1;
    }

    /* Tabel 3 (Waste) */
    .bar-waste { color: #000000 !important; }
    .tbl-waste th {
      color: #000000 !important;
      font-size: 7.5pt;
      line-height: 1.1;
    }

    /* Total Rows */
    tr.row-total td {
      background-color: #f1f5f9 !important;
      font-weight: 700;
      border-top: 1px solid #475569;
      border-bottom: 2px solid #0f172a;
    }

    /* Signatures Section */
    .signature-wrapper {
      width: 100%;
      margin-top: 10px;
      margin-bottom: 8px;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .signature-section {
      display: flex;
      justify-content: space-around;
      align-items: flex-start;
      padding: 0 30px;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .sig-box {
      width: 220px;
      text-align: center;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .sig-title {
      font-size: 8pt;
      font-weight: 700;
      margin-bottom: 32px;
    }

    .sig-name {
      font-size: 8pt;
      border-bottom: 1px solid #000000;
      display: inline-block;
      min-width: 150px;
      padding-bottom: 2px;
    }

    .sig-role {
      font-size: 7.5pt;
      font-weight: 700;
      color: #000000;
      margin-top: 3px;
    }

    /* Footer Section */
    .report-footer-wrapper {
      width: 100%;
    }

    .report-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1.5px solid #000000;
      padding-top: 4px;
      font-size: 7.5pt;
      font-weight: 700;
      color: #000000;
      background: #ffffff;
    }

    .footer-left { letter-spacing: 0.5px; }
    .footer-center {
      color: #dc2626 !important;
      font-weight: 900;
      letter-spacing: 2px;
      font-size: 8pt;
    }
    .footer-right {
      text-align: right;
      letter-spacing: 0.2px;
    }

    @media print {
      /* 1. Sembunyikan elemen non-cetak */
      .no-print,
      .no-print-toolbar,
      .btn-action,
      nav,
      header {
        display: none !important;
      }

      /* 2. Reset Body & Canvas Kertas */
      body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        color: #0f172a !important;
        font-size: 7.5pt !important;
        line-height: 1.2 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      .sheet-wrapper {
        padding: 0 !important;
        margin: 0 !important;
        display: block !important;
        width: 100% !important;
      }

      .report-container {
        padding: 0 !important;
        padding-bottom: 12mm !important; /* Ruang agar baris tabel tidak menimpa footer tetap */
        box-shadow: none !important;
        max-width: 100% !important;
        width: 100% !important;
        min-height: auto !important;
      }

      /* Pastikan semua elemen berwarna tetap tercetak */
      *, *::before, *::after {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }

      /* 3. Header Laporan */
      .report-header {
        page-break-after: avoid !important;
        break-after: avoid !important;
        margin-bottom: 6px !important;
        padding-bottom: 4px !important;
      }

      /* 4. Tabel & Pemisahan Baris */
      table {
        width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        page-break-inside: auto;
        break-inside: auto;
      }

      thead {
        display: table-header-group !important; /* Header tabel berulang jika pindah ke halaman berikutnya */
      }

      thead tr {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }

      tbody tr {
        page-break-inside: avoid !important; /* Baris tabel tidak boleh terbelah di tengah */
        break-inside: avoid !important;
      }

      tfoot {
        display: table-row-group !important; /* Baris total tetap menyatu dengan baris data tabel */
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }

      .table-section {
        page-break-inside: auto;
        break-inside: auto;
        margin-bottom: 6px !important;
        width: 100% !important;
      }

      .section-bar {
        page-break-after: avoid !important; /* Judul bar (oranye/merah) wajib ikut dengan baris pertama tabel */
        break-after: avoid !important;
      }

      /* 5. Blok Tanda Tangan Utuh */
      .signature-wrapper {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        margin-top: 14px !important;
        margin-bottom: 6px !important;
      }

      .signature-section {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        display: flex !important;
        justify-content: space-around !important;
        align-items: flex-start !important;
      }

      .sig-box {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
      }

      /* 6. Footer Tetap Muncul di Setiap Halaman */
      .report-footer {
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        background: #ffffff !important;
        border-top: 1.5px solid #0f172a !important;
        padding-top: 4px !important;
        padding-bottom: 2px !important;
        font-size: 7.5pt !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        z-index: 9999 !important;
      }

      .footer-page-num::before {
        content: " | Hal. " counter(page);
      }
    }
  </style>
</head>
<body>

  <!-- Screen Only Action Toolbar -->
  <div class="no-print no-print-toolbar">
    <div class="toolbar-title">
      📄 <span><b>Laporan Kerja Harian - Departemen Lipat</b></span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
      <label style="font-size: 0.85rem; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 4px;">
        Shift:
        <select onchange="location.href='?tanggal=<?= urlencode($tanggal) ?>&mesin_id=<?= urlencode($mesin_id) ?>&format=html&shift=' + encodeURIComponent(this.value)" style="padding: 4px 8px; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 0.85rem; background: #fff; cursor: pointer;">
          <option value="1" <?= $shift === '1' ? 'selected' : '' ?>>Shift 1</option>
          <option value="2" <?= $shift === '2' ? 'selected' : '' ?>>Shift 2</option>
          <option value="3" <?= $shift === '3' ? 'selected' : '' ?>>Shift 3</option>
        </select>
      </label>
      <button onclick="window.print()" class="btn-action">
        🖨️ PDF
      </button>
      <a href="?tanggal=<?= urlencode($tanggal) ?>&shift=<?= urlencode($shift) ?>&mesin_id=<?= urlencode($mesin_id) ?>&format=xlsx" class="btn-action btn-excel">
        📥 Excel
      </a>
      <button onclick="window.close()" class="btn-action btn-close">
        ✕ Tutup
      </button>
    </div>
  </div>

  <div class="sheet-wrapper">
    <div class="report-container">
      <div class="report-body">
        <!-- HEADER -->
        <div class="report-header">
          <div class="title-area">
            <h1>LAPORAN KERJA HARIAN - DEPARTEMEN LIPAT</h1>
            <div class="subtitle">
              Tanggal: <b><?= htmlspecialchars($tglIndo) ?></b> &nbsp;|&nbsp; Shift: <b><?= htmlspecialchars($shiftText) ?></b> &nbsp;|&nbsp; Total: <b><?= count($machineRows) ?> PP (<?= $totalMesinUnique ?> Mesin)</b>
            </div>
          </div>
          <div class="export-time-badge">
            <?= htmlspecialchars($exportTimestamp) ?>
          </div>
        </div>

        <!-- 1. TABEL UTAMA -->
        <div class="table-section">
          <table class="tbl-utama">
            <colgroup>
              <col style="width: 4%;">
              <col style="width: 15%;">
              <col style="width: 13%;">
              <col style="width: 11%;">
              <col style="width: 13%;">
              <col style="width: 11%;">
              <col style="width: 11%;">
              <col style="width: 13%;">
            </colgroup>
            <thead>
              <tr>
                <th>NO</th>
                <th style="text-align: left; padding-left: 8px;">NAMA MESIN</th>
                <th>NO PP</th>
                <th>JAM KERJA</th>
                <th>TOTAL DOWN TIME</th>
                <th>JAM JALAN</th>
                <th>PERSIAPAN</th>
                <th>HASIL BAIK</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $m_idx = 1;
              $tot_jk = 0; $tot_dt = 0; $tot_jj = 0; $tot_p = 0; $tot_good = 0;
              foreach ($machineRows as $m):
                  $tot_jk += $m['jam_kerja'];
                  $tot_dt += $m['total_dt'];
                  $tot_jj += $m['jam_jalan'];
                  $tot_p += $m['persiapan'];
                  $tot_good += $m['good'];
              ?>
              <tr>
                <td class="text-center"><?= $m_idx++ ?></td>
                <td class="text-left font-bold" style="padding-left: 8px;"><?= htmlspecialchars($m['nama_mesin']) ?></td>
                <td class="text-center"><?= htmlspecialchars($m['pp_display']) ?></td>
                <td class="text-right"><?= number_format($m['jam_kerja'], 2) ?></td>
                <td class="text-right font-bold" style="color: #000000;"><?= number_format($m['total_dt']) ?></td>
                <td class="text-right font-bold"><?= number_format($m['jam_jalan'], 2) ?></td>
                <td class="text-right"><?= number_format($m['persiapan']) ?></td>
                <td class="text-right font-bold" style="color: #000000;"><?= number_format($m['good']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="row-total">
                <td colspan="3" class="text-center">TOTAL</td>
                <td class="text-right"><?= number_format($tot_jk, 2) ?></td>
                <td class="text-right" style="color: #000000;"><?= number_format($tot_dt) ?></td>
                <td class="text-right"><?= number_format($tot_jj, 2) ?></td>
                <td class="text-right"><?= number_format($tot_p) ?></td>
                <td class="text-right" style="color: #000000;"><?= number_format($tot_good) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- 2. TABEL DETAIL DOWNTIME -->
        <div class="table-section">
          <table class="tbl-downtime">
            <colgroup>
              <col style="width: 4%;">
              <col style="width: 25%;">
              <col style="width: 10%;">
              <?php foreach ($dt_codes as $dt): ?>
                <col style="width: <?= number_format(59.0 / max(1, count($dt_codes)), 2, '.', '') ?>%;">
              <?php endforeach; ?>
              <col style="width: 8%;">
            </colgroup>
            <thead>
              <tr>
                <th colspan="14" class="section-bar bar-downtime">DETAIL DOWNTIME</th>
              </tr>
              <tr>
                <th rowspan="2" style="color: #000000;">NO</th>
                <th rowspan="2" style="color: #000000; text-align: left; padding-left: 8px;">NAMA MESIN</th>
                <th rowspan="2" style="color: #000000;">NO PP</th>
                <?php foreach ($dt_codes as $dt): ?>
                  <th class="th-dt-kode"><?= htmlspecialchars($dt[0]) ?></th>
                <?php endforeach; ?>
                <th rowspan="2" style="color: #000000;">TOTAL DT</th>
              </tr>
              <tr>
                <?php foreach ($dt_codes as $dt): ?>
                  <th class="th-dt-arti"><?= htmlspecialchars($dt[1]) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php
              $m_idx = 1;
              $tot_by_code = array_fill_keys(array_column($dt_codes, 0), 0);
              $grand_tot_dt = 0;
              foreach ($machineRows as $m):
                  $grand_tot_dt += $m['total_dt'];
              ?>
              <tr>
                <td class="text-center"><?= $m_idx++ ?></td>
                <td class="text-left font-bold" style="padding-left: 8px;"><?= htmlspecialchars($m['nama_mesin']) ?></td>
                <td class="text-center"><?= htmlspecialchars($m['pp_display']) ?></td>
                <?php foreach ($dt_codes as $dt):
                    $val = (int)($m['dt_by_code'][$dt[0]] ?? 0);
                    $tot_by_code[$dt[0]] += $val;
                ?>
                  <td class="text-right <?= $val > 0 ? 'font-bold' : '' ?>" style="<?= $val > 0 ? 'color: #000000;' : '' ?>">
                    <?= number_format($val) ?>
                  </td>
                <?php endforeach; ?>
                <td class="text-right font-bold"><?= number_format($m['total_dt']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="row-total">
                <td colspan="3" class="text-center">TOTAL</td>
                <?php foreach ($dt_codes as $dt): ?>
                  <td class="text-right"><?= number_format($tot_by_code[$dt[0]]) ?></td>
                <?php endforeach; ?>
                <td class="text-right font-bold"><?= number_format($grand_tot_dt) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- 3. TABEL DETAIL WASTE -->
        <div class="table-section">
          <table class="tbl-waste">
            <colgroup>
              <col style="width: 3%;">
              <col style="width: 9%;">
              <col style="width: 4%;">
              <col style="width: 4%;">
              <col style="width: 4%;">
              <col style="width: 4%;">
              <?php foreach ($waste_defects as $d): ?>
                <col style="width: <?= number_format(44.0 / max(1, count($waste_defects)), 2, '.', '') ?>%;">
              <?php endforeach; ?>
            </colgroup>
            <thead>
              <tr>
                <th colspan="<?= 6 + count($waste_defects) ?>" class="section-bar bar-waste">DETAIL WASTE</th>
              </tr>
              <tr>
                <th>NO</th>
                <th style="text-align: left; padding-left: 8px;">NAMA MESIN</th>
                <th>NO PP</th>
                <?php foreach ($waste_defects as $d): ?>
                  <th><?= htmlspecialchars($d) ?></th>
                <?php endforeach; ?>
                <th>TOTAL REJECT</th>
                <th>RUSAKAN STELL</th>
                <th>TOTAL WASTE</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $m_idx = 1;
              $tot_stell = 0; $tot_wip = 0; $tot_waste = 0;
              $tot_defects = array_fill_keys($waste_defects, 0);

              foreach ($machineRows as $m):
                  $tot_stell += $m['setup_reject'];
                  $tot_wip += $m['reject_wip'];
                  $tot_waste += $m['total_waste'];

                  $lainnyaQty = 0;
                  foreach ($m['cacat_by_name'] as $cName => $qty) {
                      if (!in_array($cName, $known_defects)) {
                          $lainnyaQty += $qty;
                      }
                  }
              ?>
              <tr>
                <td class="text-center"><?= $m_idx++ ?></td>
                <td class="text-left font-bold" style="padding-left: 8px;"><?= htmlspecialchars($m['nama_mesin']) ?></td>
                <td class="text-center"><?= htmlspecialchars($m['pp_display']) ?></td>
                <?php foreach ($waste_defects as $d):
                    $val = ($d === 'LAINNYA') ? $lainnyaQty : (int)($m['cacat_by_name'][$d] ?? 0);
                    $tot_defects[$d] += $val;
                ?>
                  <td class="text-right <?= $val > 0 ? 'font-bold' : '' ?>">
                    <?= number_format($val) ?>
                  </td>
                <?php endforeach; ?>
                <td class="text-right"><?= number_format($m['reject_wip']) ?></td>
                <td class="text-right"><?= number_format($m['setup_reject']) ?></td>
                <td class="text-right font-bold" style="color: #000000;"><?= number_format($m['total_waste']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="row-total">
                <td colspan="3" class="text-center">TOTAL</td>
                <?php foreach ($waste_defects as $d): ?>
                  <td class="text-right"><?= number_format($tot_defects[$d]) ?></td>
                <?php endforeach; ?>
                <td class="text-right"><?= number_format($tot_wip) ?></td>
                <td class="text-right"><?= number_format($tot_stell) ?></td>
                <td class="text-right font-bold" style="color: #000000;"><?= number_format($tot_waste) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- 4. TANDA TANGAN -->
        <div class="signature-wrapper">
          <div class="signature-section">
            <div class="sig-box">
              <div class="sig-title">DIBUAT</div>
              <div class="sig-name"><?= htmlspecialchars($picName) ?></div>
              <div class="sig-role">PIC</div>
            </div>
            <div class="sig-box">
              <div class="sig-title">DIKETAHUI</div>
              <div class="sig-name">&nbsp;</div>
              <div class="sig-role">COST CONTROL</div>
            </div>
          </div>
        </div>
      </div>

      <!-- 5. FOOTER -->
      <div class="report-footer-wrapper">
        <div class="report-footer">
          <div class="footer-left">PT PURA BARUTAMA UNIT OFFSET</div>
          <div class="footer-center">CONFIDENTIAL</div>
          <div class="footer-right">
            <span>PRO/LKCL-REV_1</span>
            <span class="footer-page-num"></span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Auto-open print dialog for quick printing when requested
    window.addEventListener('load', function() {
      // Check if user came to print directly
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('autoprint') !== '0') {
        setTimeout(function() {
          window.print();
        }, 600);
      }
    });
  </script>
</body>
</html>