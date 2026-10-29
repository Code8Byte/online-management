<?php
require_once 'config.php';
checkAuth();

// Simple cache mechanism for dashboard
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) mkdir($cacheDir);

$filterHash = md5(json_encode($_GET));
$cacheFile = "$cacheDir/dashboard_$filterHash.json";
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 60)) {
    header('Content-Type: application/json');
    echo file_get_contents($cacheFile);
    exit;
}

$where = ["1=1"];
$params = [];

if (!empty($_GET['start_date'])) { $where[] = "lkh.tanggal >= ?"; $params[] = $_GET['start_date']; }
if (!empty($_GET['end_date'])) { $where[] = "lkh.tanggal <= ?"; $params[] = $_GET['end_date']; }
if (!empty($_GET['shift'])) { $where[] = "lkh.shift = ?"; $params[] = $_GET['shift']; }
if (!empty($_GET['mesin_id'])) { $where[] = "lkh.mesin_id = ?"; $params[] = $_GET['mesin_id']; }
if (!empty($_GET['famili'])) { $where[] = "p.famili = ?"; $params[] = $_GET['famili']; }
// Multi-product support
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
if (!empty($_GET['produk_nama'])) { $where[] = "p.nama LIKE ?"; $params[] = "%" . trim($_GET['produk_nama']) . "%"; }
if (!empty($_GET['pp_id'])) { $where[] = "lkh.pp_id = ?"; $params[] = $_GET['pp_id']; }
if (!empty($_GET['tahap_sortir'])) { $where[] = "lkh.tahap_sortir = ?"; $params[] = $_GET['tahap_sortir']; }
if (!empty($_GET['no_pp'])) { $where[] = "pp.no_pp LIKE ?"; $params[] = "%" . trim($_GET['no_pp']) . "%"; }

$whereSql = implode(" AND ", $where);
$joinSql = "FROM lkh JOIN pp ON lkh.pp_id = pp.id JOIN produk p ON pp.produk_id = p.id JOIN mesin m ON lkh.mesin_id = m.id";

$data = [];

// 1. Kartu KPI
$sqlKpi = "SELECT SUM(lkh.total_proses) as sum_proses, SUM(lkh.good) as sum_good, 
           SUM(lkh.reject) as sum_reject, SUM(lkh.setup_reject) as sum_setup,
           SUM(lkh.reject) + SUM(lkh.setup_reject) as sum_ng,
           SUM(lkh.waktu_prod_jam) as sum_waktu_prod, SUM(lkh.jam_jalan) as sum_jam_jalan
           $joinSql WHERE $whereSql";
$stmt = $pdo->prepare($sqlKpi);
$stmt->execute($params);
$kpi = $stmt->fetch();

$availability = $kpi['sum_waktu_prod'] > 0 ? ($kpi['sum_jam_jalan'] / $kpi['sum_waktu_prod']) * 100 : 0;
// Performance needs sum of (jam_jalan * std_jph)
$sqlPerf = "SELECT SUM(lkh.jam_jalan * p.std_jph) as expected_proses $joinSql WHERE $whereSql";
$stmtPerf = $pdo->prepare($sqlPerf);
$stmtPerf->execute($params);
$perfData = $stmtPerf->fetch();

$performance = $perfData['expected_proses'] > 0 ? ($kpi['sum_proses'] / $perfData['expected_proses']) * 100 : 0;

// Quality OEE: dihitung dari rusakan stell (setup_reject)
$sumSortir = (float)($kpi['sum_good'] ?? 0) + (float)($kpi['sum_setup'] ?? 0);
$qualityOee = $sumSortir > 0 ? (($kpi['sum_good'] / $sumSortir) * 100) : ($kpi['sum_proses'] > 0 ? 100 : 0);

// Quality WIP: bahan evaluasi kualitas barang cetakan masuk (good / total WIP cetak)
$sumWip = (float)($kpi['sum_good'] ?? 0) + (float)($kpi['sum_reject'] ?? 0);
$qualityWip = $sumWip > 0 ? (($kpi['sum_good'] / $sumWip) * 100) : 0;

// OEE = Availability * Performance * Quality OEE
$oee = ($availability / 100) * ($performance / 100) * ($qualityOee / 100) * 100;

$data['kpi'] = [
    'total_proses' => (int)($kpi['sum_proses'] ?: 0),
    'good' => (int)($kpi['sum_good'] ?: 0),
    'reject' => (int)($kpi['sum_reject'] ?: 0),
    'setup_reject' => (int)($kpi['sum_setup'] ?: 0),
    'ng_percent' => $kpi['sum_proses'] > 0 ? (($kpi['sum_ng'] / $kpi['sum_proses']) * 100) : 0,
    'availability' => $availability,
    'performance' => $performance,
    'quality' => $qualityOee,
    'quality_oee' => $qualityOee,
    'quality_wip' => $qualityWip,
    'oee' => $oee
];

// 2. Pareto Downtime (dengan frekuensi kejadian & kumulatif)
$sqlDt = "SELECT kd.id, kd.kode, kd.arti, kd.kategori, kd.jenis_waste,
                 SUM(ld.menit) as total_menit,
                 COUNT(ld.id) as frekuensi
          $joinSql JOIN lkh_downtime ld ON lkh.id = ld.lkh_id JOIN kode_downtime kd ON ld.kode_id = kd.id 
          WHERE $whereSql GROUP BY kd.id ORDER BY total_menit DESC";
$stmt = $pdo->prepare($sqlDt); $stmt->execute($params);
$rawDt = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch semua raw catatan downtime untuk drill-down dan top 3 keterangan per kode
$sqlDtDetail = "SELECT ld.kode_id, ld.menit, COALESCE(ld.keterangan, '') as keterangan
                $joinSql JOIN lkh_downtime ld ON lkh.id = ld.lkh_id 
                WHERE $whereSql";
$stmtDtDetail = $pdo->prepare($sqlDtDetail);
$stmtDtDetail->execute($params);
$rawDtRows = $stmtDtDetail->fetchAll(PDO::FETCH_ASSOC);

// Normalisasi & grouping keterangan per kode_id
// Normalisasi: trim, lowercase, hapus spasi ganda
$drilldownByKode = [];
foreach ($rawDtRows as $dr) {
    $kId = (int)$dr['kode_id'];
    $rawKet = trim($dr['keterangan']);
    // Normalisasi: regex spasi ganda, lowercase
    $norm = preg_replace('/\s+/', ' ', mb_strtolower($rawKet, 'UTF-8'));
    if ($norm === '' || $norm === '-') {
        $displayKet = '(Tanpa Keterangan)';
        $normKey = '__empty__';
    } else {
        // Tampilkan kapital kata pertama / original rapi
        $displayKet = ucfirst($norm);
        $normKey = $norm;
    }
    
    if (!isset($drilldownByKode[$kId])) {
        $drilldownByKode[$kId] = [];
    }
    if (!isset($drilldownByKode[$kId][$normKey])) {
        $drilldownByKode[$kId][$normKey] = [
            'keterangan' => $displayKet,
            'total_menit' => 0,
            'frekuensi' => 0
        ];
    }
    $drilldownByKode[$kId][$normKey]['total_menit'] += (int)$dr['menit'];
    $drilldownByKode[$kId][$normKey]['frekuensi'] += 1;
}

$totalAllDtMenit = array_sum(array_column($rawDt, 'total_menit'));
$runningDt = 0;
$processedDt = [];
$drilldownOutput = [];

foreach ($rawDt as $row) {
    $kId = (int)$row['id'];
    $m = (int)$row['total_menit'];
    $runningDt += $m;
    $cumPct = $totalAllDtMenit > 0 ? round(($runningDt / $totalAllDtMenit) * 100, 1) : 0;
    $pct = $totalAllDtMenit > 0 ? round(($m / $totalAllDtMenit) * 100, 1) : 0;
    
    // Sort drilldown keterangan by total_menit DESC
    $kList = isset($drilldownByKode[$kId]) ? array_values($drilldownByKode[$kId]) : [];
    usort($kList, function($a, $b) {
        return $b['total_menit'] <=> $a['total_menit'];
    });
    
    // Tambahkan persentase terhadap kode tersebut
    foreach ($kList as &$kItem) {
        $kItem['persentase'] = $m > 0 ? round(($kItem['total_menit'] / $m) * 100, 1) : 0;
    }
    unset($kItem);

    // Ambil top 3 keterangan untuk tooltip
    $top3Ket = array_slice($kList, 0, 3);

    $processedDt[] = [
        'id' => $kId,
        'kode' => $row['kode'],
        'arti' => $row['arti'],
        'kategori' => $row['kategori'],
        'jenis_waste' => $row['jenis_waste'],
        'total_menit' => $m,
        'frekuensi' => (int)$row['frekuensi'],
        'persentase' => $pct,
        'kumulatif_persen' => $cumPct,
        'top_keterangan' => $top3Ket
    ];

    $drilldownOutput[$kId] = $kList;
}

$data['pareto_downtime'] = $processedDt;
$data['downtime_drilldown'] = $drilldownOutput;

// 3. Pareto Cacat (Defect WIP Cetak) dengan kumulatif persen
$sqlCacat = "SELECT jc.id, jc.nama, jc.nama AS nama_cacat, SUM(lc.qty) as total_qty, COUNT(lc.id) as frekuensi
             $joinSql JOIN lkh_cacat lc ON lkh.id = lc.lkh_id JOIN jenis_cacat jc ON lc.jenis_cacat_id = jc.id 
             WHERE $whereSql AND lc.kategori = 'reject' GROUP BY jc.id, jc.nama ORDER BY total_qty DESC";
$stmt = $pdo->prepare($sqlCacat); $stmt->execute($params);
$rawCacat = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalAllCacat = array_sum(array_column($rawCacat, 'total_qty'));
$runningCacat = 0;
$processedCacat = [];
foreach ($rawCacat as $c) {
    $qty = (int)$c['total_qty'];
    $runningCacat += $qty;
    $cumPct = $totalAllCacat > 0 ? round(($runningCacat / $totalAllCacat) * 100, 1) : 0;
    $pct = $totalAllCacat > 0 ? round(($qty / $totalAllCacat) * 100, 1) : 0;
    $processedCacat[] = [
        'id' => (int)$c['id'],
        'nama' => $c['nama'],
        'nama_cacat' => $c['nama'],
        'total_qty' => $qty,
        'frekuensi' => (int)$c['frekuensi'],
        'persentase' => $pct,
        'kumulatif_persen' => $cumPct
    ];
}
$data['pareto_cacat'] = $processedCacat;

// 5. Tren Harian / Timeline (OEE & Performance Trends)
$sqlTrend = "SELECT lkh.tanggal,
                    SUM(lkh.total_proses) as total_proses,
                    SUM(lkh.good) as good,
                    SUM(lkh.reject) as reject,
                    SUM(lkh.setup_reject) as setup_reject,
                    SUM(lkh.waktu_prod_jam) as waktu_prod,
                    SUM(lkh.jam_jalan) as jam_jalan,
                    SUM(lkh.jam_jalan * p.std_jph) as expected_proses
             $joinSql WHERE $whereSql
             GROUP BY lkh.tanggal
             ORDER BY lkh.tanggal ASC";
$stmtTrend = $pdo->prepare($sqlTrend);
$stmtTrend->execute($params);
$rawTrend = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

$trendTimeline = [];
foreach ($rawTrend as $t) {
    $wProd = (float)$t['waktu_prod'];
    $jJalan = (float)$t['jam_jalan'];
    $tProses = (int)$t['total_proses'];
    $expProses = (float)$t['expected_proses'];
    $tGood = (int)$t['good'];
    $tSetup = (int)$t['setup_reject'];

    $tAvail = $wProd > 0 ? min(100, round(($jJalan / $wProd) * 100, 1)) : 0;
    $tPerf = $expProses > 0 ? min(150, round(($tProses / $expProses) * 100, 1)) : 0;
    
    $tSortir = $tGood + $tSetup;
    $tQual = $tSortir > 0 ? round(($tGood / $tSortir) * 100, 1) : ($tProses > 0 ? 100 : 0);
    $tOee = round(($tAvail * $tPerf * $tQual) / 10000, 1);
    $tActualJph = $jJalan > 0 ? round($tProses / $jJalan, 0) : 0;
    $tTargetJph = $jJalan > 0 ? round($expProses / $jJalan, 0) : 0;

    $trendTimeline[] = [
        'tanggal' => $t['tanggal'],
        'availability' => $tAvail,
        'performance' => $tPerf,
        'quality' => $tQual,
        'oee' => $tOee,
        'target_oee' => 85.0, // Standar World-Class OEE
        'actual_jph' => $tActualJph,
        'target_jph' => $tTargetJph > 0 ? $tTargetJph : 45000, // target speed
        'total_proses' => $tProses
    ];
}
$data['trend_timeline'] = $trendTimeline;

// 4. Waste Summary
$sqlWaste = "SELECT kd.jenis_waste, SUM(ld.menit) as total_menit 
             $joinSql JOIN lkh_downtime ld ON lkh.id = ld.lkh_id JOIN kode_downtime kd ON ld.kode_id = kd.id 
             WHERE $whereSql GROUP BY kd.jenis_waste ORDER BY total_menit DESC";
$stmt = $pdo->prepare($sqlWaste); $stmt->execute($params);
$wasteDt = $stmt->fetchAll();

// Setting is part of Waiting waste
$sqlSetting = "SELECT SUM(lkh.setting_menit) as setting_menit $joinSql WHERE $whereSql";
$stmt = $pdo->prepare($sqlSetting); $stmt->execute($params);
$setting = $stmt->fetchColumn() ?: 0;
foreach ($wasteDt as &$w) {
    if ($w['jenis_waste'] === 'Waiting') $w['total_menit'] += $setting;
}

$data['waste_summary'] = $wasteDt;

// Save cache
file_put_contents($cacheFile, json_encode($data));
jsonResponse($data);
