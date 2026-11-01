<?php
require_once 'config.php';
checkAuth();

$where = ["1=1"];
$params = [];

if (!empty($_GET['start_date'])) { 
    $where[] = "lkh.tanggal >= ?"; 
    $params[] = $_GET['start_date']; 
}
if (!empty($_GET['end_date'])) { 
    $where[] = "lkh.tanggal <= ?"; 
    $params[] = $_GET['end_date']; 
}
if (!empty($_GET['shift'])) { 
    $where[] = "lkh.shift = ?"; 
    $params[] = $_GET['shift']; 
}
if (!empty($_GET['mesin_id'])) { 
    $where[] = "lkh.mesin_id = ?"; 
    $params[] = $_GET['mesin_id']; 
}
if (!empty($_GET['pp_id'])) { 
    $where[] = "lpi.pp_id = ?"; 
    $params[] = $_GET['pp_id']; 
}
if (!empty($_GET['tahap_sortir'])) { 
    $where[] = "lpi.tahap_sortir = ?"; 
    $params[] = $_GET['tahap_sortir']; 
}
if (!empty($_GET['no_pp'])) { 
    $where[] = "pp.no_pp LIKE ?"; 
    $params[] = "%" . trim($_GET['no_pp']) . "%"; 
}
if (!empty($_GET['famili'])) { 
    $where[] = "p.famili = ?"; 
    $params[] = $_GET['famili']; 
}
// Multi-product support: comma-separated string or array
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
if (!empty($_GET['produk_nama'])) { 
    $where[] = "p.nama LIKE ?"; 
    $params[] = "%" . trim($_GET['produk_nama']) . "%"; 
}

function getCategory5M($cat) {
    $c = strtolower(trim($cat ?? ''));
    if (in_array($c, ['man', 'manusia', 'operator', 'orang'])) return 'MAN';
    if (in_array($c, ['mesin', 'machine', 'alat', 'mekanik', 'elektrik'])) return 'MACHINE';
    if (in_array($c, ['material', 'bahan', 'wip', 'kertas', 'tinta'])) return 'MATERIAL';
    if (in_array($c, ['metode', 'method', 'prosedur', 'sop', 'cara kerja'])) return 'METHOD';
    if (in_array($c, ['lingkungan', 'environment', 'env', 'kondisi', 'fasilitas'])) return 'ENVIRONMENT';
    return !empty($cat) ? strtoupper($cat) : 'METHOD';
}

$whereSql = implode(" AND ", $where);

// Dynamic columns from master (+ inactive codes/types still present in filtered rows)
// Diurutkan berdasarkan 5M: MAN, MACHINE, MATERIAL, METHOD, ENVIRONMENT
$kodeSql = "
    SELECT DISTINCT kd.id, kd.kode, kd.arti, kd.kategori, kd.jenis_waste FROM kode_downtime kd
    WHERE kd.aktif = 1
       OR kd.id IN (
            SELECT DISTINCT ld.kode_id FROM lkh_downtime ld
            JOIN lkh_pp_items lpi ON ld.lkh_pp_item_id = lpi.id
            JOIN lkh ON lkh.id = lpi.lkh_id
            JOIN pp ON lpi.pp_id = pp.id
            JOIN produk p ON pp.produk_id = p.id
            JOIN mesin m ON lkh.mesin_id = m.id
            WHERE $whereSql
       )
    ORDER BY 
        CASE 
            WHEN LOWER(kd.kategori) IN ('man', 'manusia', 'operator', 'orang') THEN 1
            WHEN LOWER(kd.kategori) IN ('machine', 'mesin', 'alat', 'mekanik', 'elektrik') THEN 2
            WHEN LOWER(kd.kategori) IN ('material', 'bahan', 'wip', 'kertas', 'tinta') THEN 3
            WHEN LOWER(kd.kategori) IN ('method', 'metode', 'prosedur', 'sop') THEN 4
            WHEN LOWER(kd.kategori) IN ('environment', 'lingkungan', 'env', 'kondisi') THEN 5
            ELSE 6
        END ASC,
        kd.kode ASC
";
$stmtKode = $pdo->prepare($kodeSql);
$stmtKode->execute($params);
$kodeList = $stmtKode->fetchAll(PDO::FETCH_ASSOC);

// Grouping kode downtime berdasarkan 5M
$dtGroups = [];
foreach ($kodeList as $kd) {
    $cat5m = getCategory5M($kd['kategori']);
    $dtGroups[$cat5m][] = $kd;
}

$cacatSql = "
    SELECT DISTINCT jc.id, jc.nama, jc.urutan FROM jenis_cacat jc
    WHERE jc.aktif = 1
       OR jc.id IN (
            SELECT DISTINCT lc.jenis_cacat_id FROM lkh_cacat lc
            JOIN lkh_pp_items lpi ON lc.lkh_pp_item_id = lpi.id
            JOIN lkh ON lkh.id = lpi.lkh_id
            JOIN pp ON lpi.pp_id = pp.id
            JOIN produk p ON pp.produk_id = p.id
            JOIN mesin m ON lkh.mesin_id = m.id
            WHERE $whereSql
       )
    ORDER BY jc.urutan ASC, jc.nama ASC
";
$stmtCacat = $pdo->prepare($cacatSql);
$stmtCacat->execute($params);
$cacatList = $stmtCacat->fetchAll(PDO::FETCH_ASSOC);

// Fetch LKH rows and per-PP items
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
            lpi.setting_menit,
            lpi.5s_menit,
            lpi.total_dt_menit,
            lpi.jam_jalan,
            lpi.waktu_prod_jam,
            lpi.plan_shift,
            lpi.actual_jph,
            lpi.availability,
            lpi.performance,
            lpi.quality_oee,
            lpi.quality_wip,
            lpi.oee,
            lpi.urutan,
            lkh.tanggal,
            lkh.shift,
            lkh.mesin_id,
            lkh.operator,
            lkh.pic,
            lkh.start AS shift_start,
            lkh.finish AS shift_finish,
            lkh.problem,
            lkh.root_cause,
            lkh.tindakan_sementara,
            lkh.tindakan_perbaikan,
            lkh.lembur_menit,
            lkh.keterangan_lembur,
            pp.no_pp,
            pp.status AS pp_status,
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
        ORDER BY lkh.tanggal ASC, lkh.shift ASC, m.nama ASC, lpi.urutan ASC, lpi.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ppItemIds = array_column($rows, 'pp_item_id');
$dtByItem = [];
$cacatByItem = [];

if (!empty($ppItemIds)) {
    $placeholders = implode(',', array_fill(0, count($ppItemIds), '?'));

    $stmtDt = $pdo->prepare("
        SELECT lkh_pp_item_id, kode_id, SUM(menit) AS menit
        FROM lkh_downtime
        WHERE lkh_pp_item_id IN ($placeholders)
        GROUP BY lkh_pp_item_id, kode_id
    ");
    $stmtDt->execute($ppItemIds);
    while ($dt = $stmtDt->fetch(PDO::FETCH_ASSOC)) {
        $dtByItem[$dt['lkh_pp_item_id']][$dt['kode_id']] = (int)$dt['menit'];
    }

    $stmtCacat = $pdo->prepare("
        SELECT lkh_pp_item_id, jenis_cacat_id, kategori, SUM(qty) AS qty
        FROM lkh_cacat
        WHERE lkh_pp_item_id IN ($placeholders)
        GROUP BY lkh_pp_item_id, jenis_cacat_id, kategori
    ");
    $stmtCacat->execute($ppItemIds);
    while ($c = $stmtCacat->fetch(PDO::FETCH_ASSOC)) {
        $cacatByItem[$c['lkh_pp_item_id']][$c['kategori']][$c['jenis_cacat_id']] = (int)$c['qty'];
    }
}

$format = strtolower($_GET['format'] ?? 'excel');

// Catat Log Aktivitas Export
$actLog = ($format === 'csv') ? 'EXPORT_CSV' : 'EXPORT_EXCEL';
$periodeLog = (!empty($_GET['start_date']) ? $_GET['start_date'] : 'Semua') . ' s/d ' . (!empty($_GET['end_date']) ? $_GET['end_date'] : 'Semua');
$shiftLog = !empty($_GET['shift']) ? "Shift " . $_GET['shift'] : "Semua Shift";
$pLog = !empty($rawPids) ? "Produk: " . (is_array($rawPids) ? implode(',', $rawPids) : $rawPids) : "Semua Produk";
logActivity($pdo, $actLog, "Export Data LKH ({$format}) (Periode: {$periodeLog}, {$shiftLog}, {$pLog}, Total: " . count($rows) . " baris)");

// If explicitly requested as CSV:
if ($format === 'csv') {
    $baseHeaders = [
        'No', 'Tanggal', 'Shift', 'Mesin', 'No PP', 'Tahap', 'Produk', 'Famili', 'Operator', 'Start', 'Finish',
        'Good', 'Total Reject WIP', 'Rusakan Stell (Setup)', 'Total Proses', 'Target Shift',
        '5S & Laporan (Menit)', 'Waktu Prod (Jam)', 'Persiapan (Menit)', 'Total DT (Menit)', 'Jam Jalan (Jam)',
        'Actual JPH', 'Availability (%)', 'Performance (%)', 'Quality OEE (Stell %)', 'Quality WIP (Eval %)', 'OEE (%)'
    ];
    $baseCount = count($baseHeaders);
    $headerRow1 = array_fill(0, $baseCount, '');
    $headerRow2 = array_fill(0, $baseCount, '');
    $headerRow3 = $baseHeaders;

    $nDt = count($kodeList);
    $nCacat = count($cacatList);

    if ($nDt > 0) {
        $headerRow1[] = 'DOWNTIME (menit)';
        for ($i = 1; $i < $nDt; $i++) { $headerRow1[] = ''; }

        foreach ($dtGroups as $cat => $codes) {
            $headerRow2[] = $cat;
            for ($i = 1; $i < count($codes); $i++) { $headerRow2[] = ''; }
        }

        foreach ($kodeList as $kd) { 
            $cat5m = getCategory5M($kd['kategori']);
            $headerRow3[] = "[$cat5m] {$kd['kode']}"; 
        }
    }

    if ($nCacat > 0) {
        $headerRow1[] = 'DETAIL REJECT WIP (pcs)';
        for ($i = 1; $i < $nCacat; $i++) { $headerRow1[] = ''; }

        $headerRow2[] = 'REJECT WIP CETAK';
        for ($i = 1; $i < $nCacat; $i++) { $headerRow2[] = ''; }

        foreach ($cacatList as $jc) { $headerRow3[] = $jc['nama']; }
    }

    $noteHeaders = ['Problem', 'Root Cause', 'Tindakan Sementara', 'Tindakan Perbaikan', 'PIC'];
    foreach ($noteHeaders as $nh) {
        $headerRow1[] = '';
        $headerRow2[] = '';
        $headerRow3[] = $nh;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=LKH_Sortir_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, "\xEF\xBB\xBF");
    fputcsv($output, $headerRow1);
    fputcsv($output, $headerRow2);
    fputcsv($output, $headerRow3);

    $no = 1;
    foreach ($rows as $row) {
        $itemId = $row['pp_item_id'];
        $waktuProd = (float)($row['waktu_prod_jam'] ?? 0);
        $jamJalan = (float)($row['jam_jalan'] ?? 0);
        $totalProses = (int)($row['total_proses'] ?? 0);
        $good = (int)($row['good'] ?? 0);
        $reject = (int)($row['reject'] ?? 0);
        $setup = (int)($row['setup_reject'] ?? 0);
        $stdJph = (float)($row['std_jph'] ?? 0);
        if ($totalProses <= 0 && ($good > 0 || $reject > 0 || $setup > 0)) {
            $totalProses = $good + $reject + $setup;
        }

        $isPending = (($row['pp_status'] ?? '') === 'req') || ($stdJph <= 0);

        $planShift = (int)($row['plan_shift'] ?? 0);
        if (!$isPending && $stdJph > 0 && $jamJalan > 0 && $planShift <= 0) {
            $planShift = (int)round($jamJalan * $stdJph);
        }

        $avail = (float)($row['availability'] ?? 0);
        if ($avail <= 0 && $waktuProd > 0) {
            $avail = round(($jamJalan / $waktuProd) * 100, 2);
        }

        $qualOee = (float)($row['quality_oee'] ?? 0);
        if ($qualOee <= 0 && $totalProses > 0) {
            $qualOee = round((($totalProses - $setup) / $totalProses) * 100, 2);
        }

        $qualWip = (float)($row['quality_wip'] ?? 0);
        if ($qualWip <= 0 && $totalProses > 0) {
            $qualWip = round(($good / $totalProses) * 100, 2);
        }

        $perf = (float)($row['performance'] ?? 0);
        if (!$isPending && $perf <= 0 && $planShift > 0) {
            $perf = round(($totalProses / $planShift) * 100, 2);
        }

        $jph = (float)($row['actual_jph'] ?? 0);
        if ($jph <= 0 && $jamJalan > 0) {
            $jph = round($totalProses / $jamJalan, 2);
        }

        $oee = (float)($row['oee'] ?? 0);
        if (!$isPending && $oee <= 0 && $avail > 0 && $perf > 0 && $qualOee > 0) {
            $oee = round(($avail * $perf * $qualOee) / 10000, 2);
        }

        $planShiftDisplay = $isPending ? 'Pending' : $planShift;
        $perfDisplay = $isPending ? 'Pending' : $perf;
        $oeeDisplay = $isPending ? 'Pending' : $oee;

        $tahap = !empty($row['tahap_sortir']) ? $row['tahap_sortir'] : 'S1';
        $line = [
            $no++,
            $row['tanggal'],
            $row['shift'],
            $row['mesin'],
            $row['no_pp'],
            $tahap,
            $row['produk'],
            $row['famili'],
            $row['operator'],
            substr($row['jam_mulai'], 0, 16),
            substr($row['jam_selesai'], 0, 16),
            $good,
            $reject,
            $setup,
            $totalProses,
            $planShiftDisplay,
            (int)($row['5s_menit'] ?? 0),
            $waktuProd,
            (int)($row['setting_menit'] ?? 0),
            (int)($row['total_dt_menit'] ?? 0),
            $jamJalan,
            $jph,
            $avail,
            $perfDisplay,
            $qualOee,
            $qualWip,
            $oeeDisplay
        ];

        foreach ($kodeList as $kd) {
            $line[] = $dtByItem[$itemId][$kd['id']] ?? 0;
        }
        foreach ($cacatList as $jc) {
            $line[] = $cacatByItem[$itemId]['reject'][$jc['id']] ?? 0;
        }

        $line[] = $row['problem'] ?? '';
        $line[] = $row['root_cause'] ?? '';
        $line[] = $row['tindakan_sementara'] ?? '';
        $line[] = $row['tindakan_perbaikan'] ?? '';
        $line[] = $row['pic'] ?? '';

        fputcsv($output, $line);
    }
    fclose($output);
    exit;
}

// Default: Native OpenXML Spreadsheet (.xlsx)
require_once __DIR__ . '/SimpleXlsxWriter.php';

$xlsx = new SimpleXlsxWriter();

$nDt = count($kodeList);
$nCacat = count($cacatList);

// Column Widths
$widths = [
    1 => 6,   // No
    2 => 12,  // Tanggal
    3 => 8,   // Shift
    4 => 15,  // Mesin
    5 => 15,  // No PP
    6 => 10,  // Tahap
    7 => 24,  // Produk
    8 => 12,  // Famili
    9 => 15,  // Operator
    10 => 16, // Start
    11 => 16, // Finish
    12 => 13, // Good
    13 => 13, // Total Reject WIP
    14 => 14, // Rusakan Stell
    15 => 14, // Total Proses
    16 => 13, // Target Shift
    17 => 14, // 5S & Laporan
    18 => 13, // Waktu Prod
    19 => 13, // Persiapan
    20 => 13, // Total DT
    21 => 13, // Jam Jalan
    22 => 12, // Actual JPH
    23 => 12, // Availability
    24 => 12, // Performance
    25 => 12, // Quality OEE
    26 => 12, // Quality WIP
    27 => 12  // OEE
];

$colIdx = 28;
for ($i = 0; $i < $nDt; $i++) {
    $widths[$colIdx++] = 11;
}
for ($i = 0; $i < $nCacat; $i++) {
    $widths[$colIdx++] = 14;
}
for ($i = 0; $i < 5; $i++) {
    $widths[$colIdx++] = 20; // Problem, Root Cause, Tindakan Sementara, Tindakan Perbaikan, PIC
}
$xlsx->setColWidths($widths);

// Row 1: Title
$xlsx->writeRow(1, [1 => ['value' => 'LAPORAN KERJA HARIAN (LKH) SORTIR', 'style' => 22]], 26);
// Row 2: Subtitle
$xlsx->writeRow(2, [1 => ['value' => 'Tanggal Export: ' . date('d/m/Y H:i:s') . ' | Total Data: ' . count($rows) . ' baris', 'style' => 23]], 18);
// Row 3: Blank separator
$xlsx->writeRow(3, [], 10);

// Base Header definitions: [label, style]
$baseHeaders = [
    1 => ['No', 5],
    2 => ['Tanggal', 5],
    3 => ['Shift', 5],
    4 => ['Mesin', 5],
    5 => ['No PP', 5],
    6 => ['Tahap', 5],
    7 => ['Produk', 5],
    8 => ['Famili', 5],
    9 => ['Operator', 5],
    10 => ['Start', 5],
    11 => ['Finish', 5],
    12 => ['Good (pcs)', 5],
    13 => ['Total Reject WIP', 5],
    14 => ["Rusakan Stell\n(Setup)", 8],
    15 => ['Total Proses (pcs)', 5],
    16 => ['Target Shift (pcs)', 5],
    17 => ["5S & Laporan (Menit)\n(Wajib/Budaya)", 9],
    18 => ["Waktu Prod (Jam)\n(Shift - 5S)", 5],
    19 => ["Persiapan (Menit)\n(Setting Awal)", 10],
    20 => ["Total DT (Menit)\n(Downtime)", 6],
    21 => ["Jam Jalan (Jam)\n(Waktu Efektif)", 5],
    22 => ['Actual JPH', 5],
    23 => ['Availability', 5],
    24 => ['Performance', 5],
    25 => ["Quality OEE\n(Stell)", 11],
    26 => ["Quality WIP\n(Eval)", 12],
    27 => ['OEE', 13]
];

$r4 = [];
$r5 = [];
$r6 = [];

foreach ($baseHeaders as $col => $info) {
    $r4[$col] = ['value' => $info[0], 'style' => $info[1]];
    $r5[$col] = ['style' => $info[1]];
    $r6[$col] = ['style' => $info[1]];
    $let = $xlsx->colLetter($col);
    $xlsx->addMerge($let . '4:' . $let . '6');
}

$curCol = 28;

// Downtime columns
if ($nDt > 0) {
    $dtStartCol = $curCol;
    $dtEndCol = $curCol + $nDt - 1;
    
    // Row 4 bar
    $r4[$dtStartCol] = ['value' => 'DOWNTIME (MENIT)', 'style' => 6];
    for ($c = $dtStartCol + 1; $c <= $dtEndCol; $c++) {
        $r4[$c] = ['style' => 6];
    }
    $xlsx->addMerge($xlsx->colLetter($dtStartCol) . '4:' . $xlsx->colLetter($dtEndCol) . '4');

    // Row 5: 5M Categories
    $catCol = $dtStartCol;
    foreach ($dtGroups as $cat => $codes) {
        $cnt = count($codes);
        $catEnd = $catCol + $cnt - 1;
        $catStyle = match($cat) {
            'MAN' => 17,
            'MACHINE' => 18,
            'MATERIAL' => 19,
            'METHOD' => 20,
            'ENVIRONMENT' => 21,
            default => 37
        };
        $r5[$catCol] = ['value' => $cat, 'style' => $catStyle];
        for ($c = $catCol + 1; $c <= $catEnd; $c++) {
            $r5[$c] = ['style' => $catStyle];
        }
        if ($cnt > 1) {
            $xlsx->addMerge($xlsx->colLetter($catCol) . '5:' . $xlsx->colLetter($catEnd) . '5');
        }
        $catCol += $cnt;
    }

    // Row 6: Individual Kode & Arti
    foreach ($kodeList as $kd) {
        $r6[$curCol++] = ['value' => $kd['kode'] . "\n" . $kd['arti'], 'style' => 15];
    }
}

// Reject columns
if ($nCacat > 0) {
    $rejStartCol = $curCol;
    $rejEndCol = $curCol + $nCacat - 1;

    // Row 4 & 5 bar
    $r4[$rejStartCol] = ['value' => 'DETAIL REJECT WIP (PCS)', 'style' => 7];
    for ($c = $rejStartCol + 1; $c <= $rejEndCol; $c++) {
        $r4[$c] = ['style' => 7];
    }
    for ($c = $rejStartCol; $c <= $rejEndCol; $c++) {
        $r5[$c] = ['style' => 7];
    }
    $xlsx->addMerge($xlsx->colLetter($rejStartCol) . '4:' . $xlsx->colLetter($rejEndCol) . '5');

    // Row 6: Defect names
    foreach ($cacatList as $jc) {
        $r6[$curCol++] = ['value' => $jc['nama'], 'style' => 16];
    }
}

// Note columns (5 columns)
$notes = ['Problem', 'Root Cause', 'Tindakan Sementara', 'Tindakan Perbaikan', 'PIC'];
foreach ($notes as $noteLabel) {
    $r4[$curCol] = ['value' => $noteLabel, 'style' => 14];
    $r5[$curCol] = ['style' => 14];
    $r6[$curCol] = ['style' => 14];
    $let = $xlsx->colLetter($curCol);
    $xlsx->addMerge($let . '4:' . $let . '6');
    $curCol++;
}

$xlsx->writeRow(4, $r4, 25);
$xlsx->writeRow(5, $r5, 20);
$xlsx->writeRow(6, $r6, 30);

// Data Rows & Sums
$rowNum = 7;
$no = 1;
$sumGood = 0;
$sumReject = 0;
$sumSetup = 0;
$sumProses = 0;
$sumPlan = 0;
$sumWaktuProd = 0;
$sumDt = 0;
$sumSetting = 0;
$sum5s = 0;
$sumJamJalan = 0;

$sumDtCols = array_fill_keys(array_column($kodeList, 'id'), 0);
$sumRejectCols = array_fill_keys(array_column($cacatList, 'id'), 0);

foreach ($rows as $row) {
    $itemId = $row['pp_item_id'];
    $waktuProd = (float)($row['waktu_prod_jam'] ?? 0);
    $jamJalan = (float)($row['jam_jalan'] ?? 0);
    $totalProses = (int)($row['total_proses'] ?? 0);
    $good = (int)($row['good'] ?? 0);
    $reject = (int)($row['reject'] ?? 0);
    $setup = (int)($row['setup_reject'] ?? 0);
    $stdJph = (float)($row['std_jph'] ?? 0);
    if ($totalProses <= 0 && ($good > 0 || $reject > 0 || $setup > 0)) {
        $totalProses = $good + $reject + $setup;
    }

    $isPending = (($row['pp_status'] ?? '') === 'req') || ($stdJph <= 0);

    $planShift = (int)($row['plan_shift'] ?? 0);
    if (!$isPending && $stdJph > 0 && $jamJalan > 0 && $planShift <= 0) {
        $planShift = (int)round($jamJalan * $stdJph);
    }

    $availRaw = (float)($row['availability'] ?? 0);
    if ($availRaw <= 0 && $waktuProd > 0) {
        $availRaw = round(($jamJalan / $waktuProd) * 100, 2);
    }

    $qualOeeRaw = (float)($row['quality_oee'] ?? 0);
    if ($qualOeeRaw <= 0 && $totalProses > 0) {
        $qualOeeRaw = round((($totalProses - $setup) / $totalProses) * 100, 2);
    }

    $qualWipRaw = (float)($row['quality_wip'] ?? 0);
    if ($qualWipRaw <= 0 && $totalProses > 0) {
        $qualWipRaw = round(($good / $totalProses) * 100, 2);
    }

    $perfRaw = (float)($row['performance'] ?? 0);
    if (!$isPending && $perfRaw <= 0 && $planShift > 0) {
        $perfRaw = round(($totalProses / $planShift) * 100, 2);
    }

    $jph = (float)($row['actual_jph'] ?? 0);
    if ($jph <= 0 && $jamJalan > 0) {
        $jph = round($totalProses / $jamJalan, 2);
    }

    $oeeRaw = (float)($row['oee'] ?? 0);
    if (!$isPending && $oeeRaw <= 0 && $availRaw > 0 && $perfRaw > 0 && $qualOeeRaw > 0) {
        $oeeRaw = round(($availRaw * $perfRaw * $qualOeeRaw) / 10000, 2);
    }

    $totalDt = (int)($row['total_dt_menit'] ?? 0);
    $setting = (int)($row['setting_menit'] ?? 0);
    $limaS = (int)($row['5s_menit'] ?? 0);

    $sumGood += $good;
    $sumReject += $reject;
    $sumSetup += $setup;
    $sumProses += $totalProses;
    if (!$isPending) {
        $sumPlan += $planShift;
    }
    $sumWaktuProd += $waktuProd;
    $sumDt += $totalDt;
    $sumSetting += $setting;
    $sum5s += $limaS;
    $sumJamJalan += $jamJalan;

    $avail = $availRaw / 100;
    $perf = $perfRaw / 100;
    $qualOee = $qualOeeRaw / 100;
    $qualWip = $qualWipRaw / 100;
    $oee = $oeeRaw / 100;

    $tahap = !empty($row['tahap_sortir']) ? $row['tahap_sortir'] : 'S1';

    $c = 1;
    $dataRow = [
        $c++ => ['value' => $no++, 'style' => 1],
        $c++ => ['value' => $row['tanggal'], 'style' => 1],
        $c++ => ['value' => $row['shift'], 'style' => 1],
        $c++ => ['value' => $row['mesin'], 'style' => 0],
        $c++ => ['value' => $row['no_pp'], 'style' => 0],
        $c++ => ['value' => $tahap, 'style' => 1],
        $c++ => ['value' => $row['produk'], 'style' => 0],
        $c++ => ['value' => $row['famili'], 'style' => 0],
        $c++ => ['value' => $row['operator'], 'style' => 0],
        $c++ => ['value' => substr($row['jam_mulai'], 0, 16), 'style' => 1],
        $c++ => ['value' => substr($row['jam_selesai'], 0, 16), 'style' => 1],
        $c++ => ['value' => $good, 'type' => 'number', 'style' => 2],
        $c++ => ['value' => $reject, 'type' => 'number', 'style' => 2],
        $c++ => ['value' => $setup, 'type' => 'number', 'style' => 2],
        $c++ => ['value' => $totalProses, 'type' => 'number', 'style' => 28],
        $c++ => $isPending ? ['value' => 'Pending', 'style' => 1] : ['value' => $planShift, 'type' => 'number', 'style' => 2],
        $c++ => ['value' => $limaS, 'type' => 'number', 'style' => 29],
        $c++ => ['value' => $waktuProd, 'type' => 'number', 'style' => 3],
        $c++ => ['value' => $setting, 'type' => 'number', 'style' => 29],
        $c++ => ['value' => $totalDt, 'type' => 'number', 'style' => 30],
        $c++ => ['value' => $jamJalan, 'type' => 'number', 'style' => 31],
        $c++ => ['value' => $jph, 'type' => 'number', 'style' => 2],
        $c++ => ['value' => $avail, 'type' => 'number', 'style' => 4],
        $c++ => $isPending ? ['value' => 'Pending', 'style' => 1] : ['value' => $perf, 'type' => 'number', 'style' => 4],
        $c++ => ['value' => $qualOee, 'type' => 'number', 'style' => 4],
        $c++ => ['value' => $qualWip, 'type' => 'number', 'style' => 4],
        $c++ => $isPending ? ['value' => 'Pending', 'style' => 1] : ['value' => $oee, 'type' => 'number', 'style' => 32]
    ];

    // Downtime values
    foreach ($kodeList as $kd) {
        $val = (int)($dtByItem[$itemId][$kd['id']] ?? 0);
        $sumDtCols[$kd['id']] += $val;
        if ($val > 0) {
            $dataRow[$c++] = ['value' => $val, 'type' => 'number', 'style' => 30];
        } else {
            $dataRow[$c++] = ['value' => '-', 'style' => 1];
        }
    }

    // Reject values
    foreach ($cacatList as $jc) {
        $val = (int)($cacatByItem[$itemId]['reject'][$jc['id']] ?? 0);
        $sumRejectCols[$jc['id']] += $val;
        if ($val > 0) {
            $dataRow[$c++] = ['value' => $val, 'type' => 'number', 'style' => 33];
        } else {
            $dataRow[$c++] = ['value' => '-', 'style' => 1];
        }
    }

    // Notes
    $dataRow[$c++] = ['value' => $row['problem'] ?? '', 'style' => 0];
    $dataRow[$c++] = ['value' => $row['root_cause'] ?? '', 'style' => 0];
    $dataRow[$c++] = ['value' => $row['tindakan_sementara'] ?? '', 'style' => 0];
    $dataRow[$c++] = ['value' => $row['tindakan_perbaikan'] ?? '', 'style' => 0];
    $dataRow[$c++] = ['value' => $row['pic'] ?? '', 'style' => 0];

    $xlsx->writeRow($rowNum++, $dataRow, 20);
}

// Total / Summary Row
if (!empty($rows)) {
    $avgAvail = $sumWaktuProd > 0 ? ($sumJamJalan / $sumWaktuProd) : 0;
    $avgPerf = $sumPlan > 0 ? ($sumProses / $sumPlan) : 0;
    $sumSortirTotal = $sumGood + $sumSetup;
    $avgQualOee = $sumSortirTotal > 0 ? ($sumGood / $sumSortirTotal) : ($sumProses > 0 ? 1 : 0);
    $sumWipTotal = $sumGood + $sumReject;
    $avgQualWip = $sumWipTotal > 0 ? ($sumGood / $sumWipTotal) : 0;
    $avgOee = $avgAvail * $avgPerf * $avgQualOee;

    $totRow = [
        1 => ['value' => 'TOTAL / RATA-RATA', 'style' => 24]
    ];
    for ($i = 2; $i <= 11; $i++) {
        $totRow[$i] = ['style' => 24];
    }
    $xlsx->addMerge('A' . $rowNum . ':K' . $rowNum);

    $totRow[12] = ['value' => $sumGood, 'type' => 'number', 'style' => 25];
    $totRow[13] = ['value' => $sumReject, 'type' => 'number', 'style' => 25];
    $totRow[14] = ['value' => $sumSetup, 'type' => 'number', 'style' => 25];
    $totRow[15] = ['value' => $sumProses, 'type' => 'number', 'style' => 25];
    $totRow[16] = ['value' => $sumPlan, 'type' => 'number', 'style' => 25];
    $totRow[17] = ['value' => $sum5s, 'type' => 'number', 'style' => 34];
    $totRow[18] = ['value' => $sumWaktuProd, 'type' => 'number', 'style' => 26];
    $totRow[19] = ['value' => $sumSetting, 'type' => 'number', 'style' => 34];
    $totRow[20] = ['value' => $sumDt, 'type' => 'number', 'style' => 35];
    $totRow[21] = ['value' => $sumJamJalan, 'type' => 'number', 'style' => 26];
    $totRow[22] = ['value' => '-', 'style' => 24];
    $totRow[23] = ['value' => $avgAvail, 'type' => 'number', 'style' => 27];
    $totRow[24] = ['value' => $avgPerf, 'type' => 'number', 'style' => 27];
    $totRow[25] = ['value' => $avgQualOee, 'type' => 'number', 'style' => 27];
    $totRow[26] = ['value' => $avgQualWip, 'type' => 'number', 'style' => 27];
    $totRow[27] = ['value' => $avgOee, 'type' => 'number', 'style' => 36];

    $curCol = 28;
    foreach ($kodeList as $kd) {
        $val = (int)($sumDtCols[$kd['id']] ?? 0);
        $totRow[$curCol++] = ['value' => $val, 'type' => 'number', 'style' => 25];
    }
    foreach ($cacatList as $jc) {
        $val = (int)($sumRejectCols[$jc['id']] ?? 0);
        $totRow[$curCol++] = ['value' => $val, 'type' => 'number', 'style' => 25];
    }
    for ($i = 0; $i < 5; $i++) {
        $totRow[$curCol++] = ['style' => 24];
    }

    $xlsx->writeRow($rowNum, $totRow, 22);
}

$filename = 'Laporan_LKH_Sortir_' . date('Ymd_His') . '.xlsx';
$xlsx->output($filename);
exit;
