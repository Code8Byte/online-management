<?php
require_once 'config.php';

// Migration / Repair Script: Recalculate historical OEE, Availability, Performance & Quality in lkh_pp_items
$stmt = $pdo->query("
    SELECT 
        lpi.id,
        lpi.lkh_id,
        lpi.pp_id,
        lpi.waktu_prod_jam,
        lpi.jam_jalan,
        lpi.good,
        lpi.reject,
        lpi.setup_reject,
        lpi.total_proses,
        lpi.setting_menit,
        lpi.5s_menit,
        lpi.total_dt_menit,
        lpi.plan_shift,
        lpi.actual_jph,
        lpi.availability,
        lpi.performance,
        lpi.quality_oee,
        lpi.quality_wip,
        lpi.oee,
        lkh.tanggal,
        lkh.shift,
        p.std_jph,
        pp.status as pp_status
    FROM lkh_pp_items lpi
    JOIN lkh ON lpi.lkh_id = lkh.id
    JOIN pp ON lpi.pp_id = pp.id
    JOIN produk p ON pp.produk_id = p.id
    ORDER BY lpi.id ASC
");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$stmtUp = $pdo->prepare("
    UPDATE lkh_pp_items SET
        total_proses = ?,
        plan_shift = ?,
        actual_jph = ?,
        availability = ?,
        performance = ?,
        quality_oee = ?,
        quality_wip = ?,
        oee = ?
    WHERE id = ?
");

foreach ($items as $it) {
    $good = (int)$it['good'];
    $reject = (int)$it['reject'];
    $setup = (int)$it['setup_reject'];
    $totalProses = $good + $reject + $setup;

    $waktuProd = (float)$it['waktu_prod_jam'];
    $jamJalan = (float)$it['jam_jalan'];
    $stdJph = (float)$it['std_jph'];
    $isPending = ($it['pp_status'] === 'req') || ($stdJph <= 0);

    $planShift = (!$isPending && $jamJalan > 0 && $stdJph > 0) ? (int)round($jamJalan * $stdJph) : 0;
    $actualJph = ($jamJalan > 0) ? round($totalProses / $jamJalan, 2) : 0;

    $avail = ($waktuProd > 0) ? round(($jamJalan / $waktuProd) * 100, 2) : 0;
    $perf = (!$isPending && $planShift > 0) ? round(($totalProses / $planShift) * 100, 2) : 0;
    $qualOee = ($totalProses > 0) ? round((($totalProses - $setup) / $totalProses) * 100, 2) : 0;
    $qualWip = ($totalProses > 0) ? round(($good / $totalProses) * 100, 2) : 0;
    $oee = (!$isPending) ? round(($avail * $perf * $qualOee) / 10000, 2) : 0;

    $stmtUp->execute([
        $totalProses,
        $planShift,
        $actualJph,
        $avail,
        $perf,
        $qualOee,
        $qualWip,
        $oee,
        $it['id']
    ]);
    $updated++;
}

// Update lkh.plan_shift
$pdo->exec("
    UPDATE lkh SET plan_shift = (
        SELECT COALESCE(SUM(plan_shift), 0) FROM lkh_pp_items WHERE lkh_id = lkh.id
    )
");

// Clear dashboard cache if any
$cacheFiles = glob(__DIR__ . '/cache/dashboard_*.json');
if ($cacheFiles) {
    foreach ($cacheFiles as $cf) @unlink($cf);
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'updated_items' => $updated]);
