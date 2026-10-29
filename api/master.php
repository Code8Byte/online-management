<?php
require_once 'config.php';
checkAuth();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Request PP diizinkan untuk Operator dan Admin (Authenticated Users)
if ($action === 'request_pp' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $no_pp = trim($data['no_pp'] ?? '');
    $produk_nama = trim($data['produk_nama'] ?? '');
    $famili = trim($data['famili'] ?? '');

    if (empty($no_pp) || empty($produk_nama)) {
        jsonResponse(['error' => 'Nomor PP dan Nama Produk wajib diisi.'], 400);
    }

    // 1. Cek atau buat produk
    $stmtP = $pdo->prepare("SELECT id, nama, famili, std_jph FROM produk WHERE LOWER(TRIM(nama)) = LOWER(?) LIMIT 1");
    $stmtP->execute([$produk_nama]);
    $prod = $stmtP->fetch(PDO::FETCH_ASSOC);

    if ($prod) {
        $produk_id = (int)$prod['id'];
        if ((empty($prod['famili']) || $prod['famili'] === '-') && !empty($famili)) {
            $pdo->prepare("UPDATE produk SET famili = ? WHERE id = ?")->execute([$famili, $produk_id]);
        }
    } else {
        $stmtInsP = $pdo->prepare("INSERT INTO produk (nama, famili, std_jph, aktif) VALUES (?, ?, 0, 1)");
        $stmtInsP->execute([$produk_nama, $famili ?: '-']);
        $produk_id = (int)$pdo->lastInsertId();
    }

    // 2. Cek apakah PP sudah ada
    $stmtPp = $pdo->prepare("SELECT pp.*, p.nama as produk_nama, p.famili, p.std_jph FROM pp JOIN produk p ON pp.produk_id = p.id WHERE LOWER(TRIM(pp.no_pp)) = LOWER(?) LIMIT 1");
    $stmtPp->execute([$no_pp]);
    $existingPp = $stmtPp->fetch(PDO::FETCH_ASSOC);

    if ($existingPp) {
        jsonResponse([
            'success' => true,
            'pp' => $existingPp,
            'message' => "Nomor PP {$no_pp} sudah terdaftar di sistem."
        ]);
    }

    // 3. Masukkan PP baru dengan status 'req' dan plan_total 0 (menunggu ACC admin)
    $stmtInsPp = $pdo->prepare("INSERT INTO pp (no_pp, produk_id, plan_total, status) VALUES (?, ?, 0, 'req')");
    $stmtInsPp->execute([$no_pp, $produk_id]);
    $newPpId = (int)$pdo->lastInsertId();

    $stmtNewPp = $pdo->prepare("SELECT pp.*, p.nama as produk_nama, p.famili, p.std_jph FROM pp JOIN produk p ON pp.produk_id = p.id WHERE pp.id = ?");
    $stmtNewPp->execute([$newPpId]);
    $newPp = $stmtNewPp->fetch(PDO::FETCH_ASSOC);

    $curUser = $_SESSION['username'] ?? 'Operator';
    logActivity($pdo, 'REQUEST_PP', "User/Operator '{$curUser}' mengajukan Request PP baru: {$no_pp} ({$produk_nama}) [Status: req]");

    jsonResponse([
        'success' => true,
        'pp' => $newPp,
        'message' => "Request PP '{$no_pp}' berhasil dikirim. Menunggu verifikasi standar JPH & cetakan dari Admin."
    ]);
}

// Hanya admin yang diizinkan mengubah Master Data selain request_pp
if ($method !== 'GET') {
    checkRole(['admin']);
}

function crud($pdo, $table, $method, $columns) {
    try {
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM $table");
            jsonResponse($stmt->fetchAll());
        } elseif ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $fields = [];
            $values = [];
            $placeholders = [];
            foreach ($columns as $col) {
                if (isset($data[$col])) {
                    $fields[] = $col;
                    $values[] = $data[$col];
                    $placeholders[] = "?";
                }
            }
            $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
            $pdo->prepare($sql)->execute($values);
            jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        } elseif ($method === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!is_array($data)) {
                jsonResponse(['error' => 'Payload tidak valid'], 400);
            }
            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['error' => 'ID tidak valid'], 400);
            }
            $set = [];
            $values = [];
            foreach ($columns as $col) {
                if (array_key_exists($col, $data)) {
                    $set[] = "$col = ?";
                    $values[] = $data[$col];
                }
            }
            if (empty($set)) {
                jsonResponse(['error' => 'Tidak ada field yang diupdate'], 400);
            }
            $values[] = $id;
            $sql = "UPDATE $table SET " . implode(',', $set) . " WHERE id = ?";
            $pdo->prepare($sql)->execute($values);

            // Jika update produk dan ada perubahan std_jph, perbarui juga plan_shift LKH yang terkait
            if ($table === 'produk' && isset($data['std_jph']) && is_numeric($data['std_jph'])) {
                $newStd = (float)$data['std_jph'];
                $pdo->prepare("
                    UPDATE lkh 
                    JOIN pp ON lkh.pp_id = pp.id 
                    SET lkh.plan_shift = ROUND(lkh.jam_jalan * ?) 
                    WHERE pp.produk_id = ? AND lkh.jam_jalan > 0
                ")->execute([$newStd, $id]);

                // Update juga lkh_pp_items
                $pdo->prepare("
                    UPDATE lkh_pp_items lpi
                    JOIN pp ON lpi.pp_id = pp.id
                    SET lpi.plan_shift = ROUND(lpi.jam_jalan * ?),
                        lpi.performance = CASE WHEN lpi.jam_jalan > 0 AND ? > 0 THEN ROUND((lpi.total_proses / ROUND(lpi.jam_jalan * ?)) * 100, 2) ELSE 0 END,
                        lpi.oee = CASE WHEN lpi.jam_jalan > 0 AND ? > 0 THEN ROUND((lpi.availability * ROUND((lpi.total_proses / ROUND(lpi.jam_jalan * ?)) * 100, 2) * lpi.quality_oee) / 10000, 2) ELSE 0 END
                    WHERE pp.produk_id = ? AND lpi.jam_jalan > 0
                ")->execute([$newStd, $newStd, $newStd, $newStd, $newStd, $id]);

                // Hapus cache dashboard agar KPI OEE/Performance langsung terefresh
                $cacheFiles = glob(__DIR__ . '/cache/dashboard_*.json');
                if ($cacheFiles) {
                    foreach ($cacheFiles as $cf) @unlink($cf);
                }
            }

            jsonResponse(['success' => true]);
        } elseif ($method === 'DELETE') {
            $id = $_GET['id'] ?? 0;
            $hard = isset($_GET['hard']) && $_GET['hard'] == '1';
            
            // Khusus untuk produk, jika hard delete, hapus juga PP yang belum punya LKH
            if ($table === 'produk' && $hard) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM lkh JOIN pp ON lkh.pp_id = pp.id WHERE pp.produk_id = ?");
                $stmt->execute([$id]);
                $lkhCount = $stmt->fetchColumn();
                
                if ($lkhCount > 0) {
                    jsonResponse(['error' => "Gagal: Produk ini sudah memiliki Job Order yang ada data LKH-nya."], 500);
                } else {
                    $pdo->prepare("DELETE FROM pp WHERE produk_id = ?")->execute([$id]);
                }
            }

            // Khusus untuk PP / Job Order
            if ($table === 'pp') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM lkh WHERE pp_id = ?");
                $stmt->execute([$id]);
                $lkhCount = $stmt->fetchColumn();
                if ($lkhCount > 0) {
                    jsonResponse(['error' => "Gagal menghapus: Job Order (PP) ini sudah memiliki riwayat data LKH."], 400);
                }
                try {
                    $pdo->prepare("DELETE FROM antrian_pallet WHERE pp_id = ?")->execute([$id]);
                } catch (Exception $e) {}
            }

            // Check if table has 'aktif' column
            $stmt = $pdo->query("SHOW COLUMNS FROM $table LIKE 'aktif'");
            $hasAktif = $stmt->fetch();
            
            if ($hasAktif && !$hard) {
                $pdo->prepare("UPDATE $table SET aktif = 0 WHERE id = ?")->execute([$id]);
            } else {
                $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
            }
            jsonResponse(['success' => true]);
        }
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Integrity constraint violation: 1451') !== false || strpos($msg, 'foreign key constraint fails') !== false) {
            jsonResponse(['error' => "Gagal menghapus permanen: Data ini masih digunakan (referensi) di bagian lain sistem (misal: Job Order)."], 500);
        }
        jsonResponse(['error' => $msg], 500);
    }
}

switch ($action) {
    case 'mesin':
        crud($pdo, 'mesin', $method, ['nama', 'aktif']);
        break;
    case 'produk':
        crud($pdo, 'produk', $method, ['nama', 'famili', 'std_jph', 'aktif']);
        break;
    case 'pp':
        if ($method === 'GET') {
            $stmt = $pdo->query("
                SELECT pp.*, p.nama as produk_nama, p.famili, p.std_jph 
                FROM pp 
                JOIN produk p ON pp.produk_id = p.id 
                ORDER BY 
                    CASE WHEN pp.status = 'req' THEN 0 WHEN pp.status = 'aktif' THEN 1 ELSE 2 END ASC, 
                    pp.id DESC
            ");
            jsonResponse($stmt->fetchAll());
        } else {
            crud($pdo, 'pp', $method, ['no_pp', 'produk_id', 'plan_total', 'status']);
        }
        break;
    case 'acc_pp':
        if ($method === 'POST' || $method === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $pp_id = (int)($data['id'] ?? 0);
            $std_jph = (float)($data['std_jph'] ?? 0);
            $plan_total = (int)($data['plan_total'] ?? 0);
            $famili = trim($data['famili'] ?? '');

            if ($pp_id <= 0 || $std_jph <= 0 || $plan_total <= 0) {
                jsonResponse(['error' => 'Standar JPH dan Total Dicetak wajib diisi dengan nilai lebih dari 0.'], 400);
            }

            $stmtPp = $pdo->prepare("SELECT pp.*, p.id as p_id, p.nama as p_nama FROM pp JOIN produk p ON pp.produk_id = p.id WHERE pp.id = ?");
            $stmtPp->execute([$pp_id]);
            $currPp = $stmtPp->fetch(PDO::FETCH_ASSOC);
            if (!$currPp) {
                jsonResponse(['error' => 'Data PP tidak ditemukan.'], 404);
            }

            // 1. Update produk: std_jph & famili
            $updateProdSql = "UPDATE produk SET std_jph = ?";
            $updateProdParams = [$std_jph];
            if (!empty($famili)) {
                $updateProdSql .= ", famili = ?";
                $updateProdParams[] = $famili;
            }
            $updateProdSql .= " WHERE id = ?";
            $updateProdParams[] = $currPp['produk_id'];
            $pdo->prepare($updateProdSql)->execute($updateProdParams);

            // 2. Update pp: plan_total & status = 'aktif'
            $pdo->prepare("UPDATE pp SET plan_total = ?, status = 'aktif' WHERE id = ?")->execute([$plan_total, $pp_id]);

            // 3. Recalculate OEE & plan_shift pada lkh_pp_items yang terkait dengan pp_id ini
            $stmtItems = $pdo->prepare("SELECT * FROM lkh_pp_items WHERE pp_id = ?");
            $stmtItems->execute([$pp_id]);
            $itemsToUpdate = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            $stmtUpdateItem = $pdo->prepare("
                UPDATE lkh_pp_items SET 
                    plan_shift = ?, 
                    performance = ?, 
                    oee = ? 
                WHERE id = ?
            ");

            $affectedLkhIds = [];
            foreach ($itemsToUpdate as $it) {
                $jamJalan = (float)$it['jam_jalan'];
                $totProses = (int)$it['total_proses'];
                $avail = (float)$it['availability'];
                $qualOee = (float)$it['quality_oee'];

                $planShift = ($jamJalan > 0 && $std_jph > 0) ? (int)round($jamJalan * $std_jph) : 0;
                $perf = ($planShift > 0) ? round(($totProses / $planShift) * 100, 2) : 0;
                $oee = round(($avail * $perf * $qualOee) / 10000, 2);

                $stmtUpdateItem->execute([$planShift, $perf, $oee, $it['id']]);
                $affectedLkhIds[$it['lkh_id']] = true;
            }

            // Update total plan_shift di tabel lkh induk
            if (!empty($affectedLkhIds)) {
                $stmtSumLkh = $pdo->prepare("
                    UPDATE lkh SET plan_shift = (
                        SELECT COALESCE(SUM(plan_shift), 0) FROM lkh_pp_items WHERE lkh_id = lkh.id
                    ) WHERE id = ?
                ");
                foreach (array_keys($affectedLkhIds) as $lId) {
                    $stmtSumLkh->execute([$lId]);
                }
            }

            // Hapus cache dashboard agar langsung update
            $cacheFiles = glob(__DIR__ . '/cache/dashboard_*.json');
            if ($cacheFiles) {
                foreach ($cacheFiles as $cf) @unlink($cf);
            }

            logActivity($pdo, 'ACC_PP', "Admin menyetujui (ACC) Job Order PP: {$currPp['no_pp']} ({$currPp['p_nama']}), Std Speed: {$std_jph} JPH, Kuota: {$plan_total} pcs");

            jsonResponse(['success' => true, 'message' => "Job Order {$currPp['no_pp']} berhasil disetujui (ACC). Perhitungan OEE sekarang aktif."]);
        }
        break;
    case 'end_pp':
        if ($method === 'POST') {
            if (($_SESSION['role'] ?? '') !== 'admin') {
                jsonResponse(['error' => 'Akses ditolak. Hanya Admin yang dapat menutup Job Order (End PP).'], 403);
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $ppId = (int)($data['id'] ?? 0);
            if ($ppId > 0) {
                $stmt = $pdo->prepare("SELECT pp.no_pp, p.nama AS produk_nama FROM pp JOIN produk p ON pp.produk_id = p.id WHERE pp.id = ?");
                $stmt->execute([$ppId]);
                $currPp = $stmt->fetch(PDO::FETCH_ASSOC);

                $pdo->prepare("UPDATE pp SET status = 'selesai' WHERE id = ?")->execute([$ppId]);

                $curUser = $_SESSION['username'] ?? 'Admin';
                $noPpStr = $currPp ? "{$currPp['no_pp']} ({$currPp['produk_nama']})" : "ID {$ppId}";
                logActivity($pdo, 'END_PP', "Admin '{$curUser}' menutup Job Order (End Job Order): {$noPpStr} [Status -> selesai]");

                $cacheFiles = glob(__DIR__ . '/cache/dashboard_*.json');
                if ($cacheFiles) {
                    foreach ($cacheFiles as $cf) @unlink($cf);
                }

                jsonResponse(['success' => true, 'message' => "Job Order {$noPpStr} berhasil ditutup (selesai)."]);
            } else {
                jsonResponse(['error' => 'ID Job Order tidak valid.'], 400);
            }
        }
        break;
    case 'jenis_cacat':
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM jenis_cacat ORDER BY urutan ASC, id ASC");
            jsonResponse($stmt->fetchAll());
        } else {
            crud($pdo, 'jenis_cacat', $method, ['nama', 'aktif', 'urutan']);
        }
        break;
    case 'kode_downtime':
        crud($pdo, 'kode_downtime', $method, ['kode', 'arti', 'kategori', 'jenis_waste', 'aktif']);
        break;
    case 'shift_jadwal':
        crud($pdo, 'shift_jadwal', $method, ['shift', 'jam_mulai', 'jam_selesai']);
        break;
    default:
        jsonResponse(['error' => 'Endpoint tidak ditemukan'], 404);
}
