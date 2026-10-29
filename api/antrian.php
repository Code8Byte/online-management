<?php
require_once "config.php";
checkAuth();

$action = $_GET["action"] ?? "";
$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET" && $action === "list") {
    $stmt = $pdo->query("SELECT a.*, p.no_pp, pr.nama as produk_nama FROM antrian_pallet a JOIN pp p ON a.pp_id = p.id JOIN produk pr ON p.produk_id = pr.id ORDER BY a.tanggal DESC, a.shift DESC, a.id DESC");
    jsonResponse($stmt->fetchAll());
} elseif ($method === "GET" && $action === "get_by_lkh") {
    $pp_id = $_GET["pp_id"] ?? 0;
    $exclude_lkh_id = $_GET["exclude_lkh_id"] ?? 0;
    
    $stmt = $pdo->prepare("SELECT * FROM antrian_pallet WHERE pp_id = ? ORDER BY id ASC");
    $stmt->execute([$pp_id]);
    $antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sqlLkh = "SELECT id, detail_pallet FROM lkh WHERE pp_id = ?";
    $paramsLkh = [$pp_id];
    if ($exclude_lkh_id) {
        $sqlLkh .= " AND id != ?";
        $paramsLkh[] = $exclude_lkh_id;
    }
    
    $stmtLkh = $pdo->prepare($sqlLkh);
    $stmtLkh->execute($paramsLkh);
    $lkhList = $stmtLkh->fetchAll(PDO::FETCH_ASSOC);
    
    $pemakaian = [];
    foreach ($lkhList as $l) {
        if (!empty($l['detail_pallet'])) {
            $pallets = json_decode($l['detail_pallet'], true);
            if (is_array($pallets)) {
                foreach ($pallets as $p) {
                    $pid = $p['id'] ?? 0;
                    $qty_proses = isset($p['qty_proses']) ? (int)$p['qty_proses'] : (int)($p['qty'] ?? 0);
                    if (!isset($pemakaian[$pid])) $pemakaian[$pid] = 0;
                    $pemakaian[$pid] += $qty_proses;
                }
            }
        }
    }
    
    $result = [];
    foreach ($antrian as $a) {
        $id = $a['id'];
        $dipakai = $pemakaian[$id] ?? 0;
        $sisa = $a['qty'] - $dipakai;
        if ($sisa > 0) {
            $a['qty_awal'] = $a['qty'];
            $a['qty'] = $sisa;
            $result[] = $a;
        }
    }
    
    jsonResponse($result);
} elseif ($method === "POST" && $action === "save") {
    $data = json_decode(file_get_contents("php://input"), true);
    $pp_id = $data["pp_id"] ?? 0;
    $tanggal = !empty($data["tanggal"]) ? $data["tanggal"] : date("Y-m-d");
    $shift = !empty($data["shift"]) ? $data["shift"] : 1;
    $pallets = $data["pallets"] ?? [];
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO antrian_pallet (pp_id, tanggal, shift, nomor_pallet, qty) VALUES (?, ?, ?, ?, ?)");
        foreach ($pallets as $p) {
            $stmt->execute([$pp_id, $tanggal, $shift, $p["nomor"], $p["qty"]]);
        }
        $pdo->commit();
        jsonResponse(["success" => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(["error" => $e->getMessage()], 500);
    }
} elseif ($method === "DELETE" && $action === "delete") {
    $id = $_GET["id"] ?? 0;
    $pdo->prepare("DELETE FROM antrian_pallet WHERE id = ?")->execute([$id]);
    jsonResponse(["success" => true]);
} else {
    jsonResponse(["error" => "Invalid endpoint"], 404);
}
?>
