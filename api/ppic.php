<?php
require_once __DIR__ . '/config.php';
checkAuth();

$action = $_GET['action'] ?? 'list_pp';
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($action === 'list_pp' && $method === 'GET') {
        $stmt = $pdo->query("
            SELECT pp.*, p.nama AS produk_nama, p.famili, p.std_jph
            FROM pp
            LEFT JOIN produk p ON pp.produk_id = p.id
            ORDER BY pp.id DESC
        ");
        $pps = $stmt->fetchAll();

        // Ambil semua stages untuk tiap PP
        $stmtStages = $pdo->query("
            SELECT * FROM pp_stages ORDER BY pp_id ASC, stage_num ASC, id ASC
        ");
        $allStages = $stmtStages->fetchAll();
        $stagesByPp = [];
        foreach ($allStages as $st) {
            $stagesByPp[$st['pp_id']][] = $st;
        }

        foreach ($pps as &$pp) {
            $pp['stages'] = $stagesByPp[$pp['id']] ?? [];
            $pp['is_extended'] = (bool)($pp['is_extended'] ?? 0);
        }

        jsonResponse($pps);
    }

    if ($action === 'gantt' && $method === 'GET') {
        $stmt = $pdo->query("
            SELECT 
                st.id,
                st.pp_id,
                pp.no_pp,
                p.nama AS produk_nama,
                st.stage,
                st.stage_num,
                st.mesin_id,
                st.mesin_nama,
                st.operator,
                st.opsi,
                st.unit,
                st.plan_qty,
                st.actual_output,
                st.good,
                st.reject,
                st.progress,
                st.status,
                DATE_FORMAT(st.start_datetime, '%Y-%m-%d') AS start_date,
                DATE_FORMAT(st.start_datetime, '%Y-%m-%d %H:%i') AS start_datetime,
                DATE_FORMAT(st.planned_end_datetime, '%Y-%m-%d') AS planned_end_date,
                DATE_FORMAT(st.planned_end_datetime, '%Y-%m-%d %H:%i') AS planned_end_datetime,
                DATE_FORMAT(st.actual_end_datetime, '%Y-%m-%d %H:%i') AS actual_end_datetime,
                st.duration_minutes,
                DATE_FORMAT(pp.deadline_datetime, '%Y-%m-%d %H:%i') AS deadline_datetime,
                pp.oplaag_pcs,
                pp.up_per_plano,
                pp.total_plano AS plan_plano,
                pp.muatan_koli,
                pp.muatan_pallet,
                pp.armada,
                pp.ritase,
                pp.is_extended
            FROM pp_stages st
            JOIN pp ON st.pp_id = pp.id
            LEFT JOIN produk p ON pp.produk_id = p.id
            ORDER BY st.start_datetime ASC, st.stage_num ASC
        ");
        jsonResponse($stmt->fetchAll());
    }

    if ($action === 'progress' && $method === 'GET') {
        $stmt = $pdo->query("
            SELECT pp.*, p.nama AS produk_nama, p.famili, p.std_jph
            FROM pp
            LEFT JOIN produk p ON pp.produk_id = p.id
            ORDER BY pp.id DESC
        ");
        $pps = $stmt->fetchAll();

        $stmtStages = $pdo->query("SELECT * FROM pp_stages ORDER BY pp_id ASC, stage_num ASC");
        $allStages = $stmtStages->fetchAll();
        $stagesByPp = [];
        foreach ($allStages as $st) {
            $stagesByPp[$st['pp_id']][$st['stage']] = $st;
        }

        $result = [];
        foreach ($pps as $pp) {
            $stg = $stagesByPp[$pp['id']] ?? [];
            
            // Format 5 stages: cetak, completing, cabut, sortir, lipat
            $c = $stg['cetak'] ?? null;
            $cmp = $stg['completing'] ?? null;
            $cbt = $stg['cabut'] ?? null;
            $srt = $stg['sortir'] ?? null;
            $lpt = $stg['lipat'] ?? null;

            $item = [
                'id' => (int)$pp['id'],
                'no_pp' => $pp['no_pp'],
                'produk_nama' => $pp['produk_nama'] ?: 'Packaging Item',
                'status' => $pp['status'] ?: 'berjalan',
                'plan_total' => (int)$pp['plan_total'],
                'plan_unit' => 'plano',
                'up_per_plano' => (int)($pp['up_per_plano'] ?: 4),
                'oplaag_pcs' => (int)($pp['oplaag_pcs'] ?: 100000),
                'alur_tipe' => $pp['alur_tipe'] ?: 'standar',
                'alur_label' => $pp['alur_label'] ?: 'Standar',
                'created_at' => substr($pp['created_at'], 0, 10),
                'last_update' => substr($pp['created_at'], 0, 16),
                'cetak' => [
                    'stage' => 'Cetak',
                    'tag' => 'CTK',
                    'mesin' => $c['mesin_nama'] ?? 'SM 23 (Heidelberg)',
                    'operator' => $c['operator'] ?? 'Tito',
                    'opsi' => $c['opsi'] ?? 'Druk Offset',
                    'status' => $c['status'] ?? 'antri',
                    'unit' => 'plano',
                    'plan' => (int)($c['plan_qty'] ?? $pp['total_plano']),
                    'output' => (int)($c['actual_output'] ?? 0),
                    'good' => (int)($c['good'] ?? 0),
                    'reject' => (int)($c['reject'] ?? 0),
                    'progress' => (float)($c['progress'] ?? 0)
                ],
                'completing' => [
                    'stage' => 'Completing',
                    'tag' => 'CMP',
                    'mesin' => $cmp['mesin_nama'] ?? 'Hot Stamping Foil 1 & UV Gloss',
                    'operator' => $cmp['operator'] ?? 'Nico',
                    'opsi' => $cmp['opsi'] ?? 'Varnish UV Gloss',
                    'status' => $cmp['status'] ?? 'antri',
                    'unit' => 'plano',
                    'plan' => (int)($cmp['plan_qty'] ?? $c['good'] ?? $pp['total_plano']),
                    'output' => (int)($cmp['actual_output'] ?? 0),
                    'good' => (int)($cmp['good'] ?? 0),
                    'reject' => (int)($cmp['reject'] ?? 0),
                    'progress' => (float)($cmp['progress'] ?? 0)
                ],
                'cabut' => [
                    'stage' => 'Cabut',
                    'tag' => 'CBT',
                    'mesin' => $cbt['mesin_nama'] ?? 'Bobst MS 1 (Die Plat 4 Up)',
                    'operator' => $cbt['operator'] ?? 'Haryanto',
                    'opsi' => $cbt['opsi'] ?? 'Pond Potong (Konversi Plano ➜ Pcs)',
                    'status' => $cbt['status'] ?? 'antri',
                    'unit' => 'plano',
                    'plan' => (int)($cbt['plan_qty'] ?? $cmp['good'] ?? $pp['total_plano']),
                    'output' => (int)($cbt['actual_output'] ?? 0),
                    'good' => (int)($cbt['good'] ?? 0),
                    'reject' => (int)($cbt['reject'] ?? 0),
                    'progress' => (float)($cbt['progress'] ?? 0)
                ],
                'sortir' => [
                    'stage' => 'Sortir',
                    'tag' => 'SRT',
                    'mesin' => $srt['mesin_nama'] ?? 'Focusight FS-500 Shark',
                    'operator' => $srt['operator'] ?? 'Reno',
                    'opsi' => $srt['opsi'] ?? 'Kamera QC & Meja Manual',
                    'status' => $srt['status'] ?? 'antri',
                    'unit' => 'pcs',
                    'plan' => (int)($srt['plan_qty'] ?? (($cbt['good'] ?: 25000) * ($pp['up_per_plano'] ?: 4))),
                    'output' => (int)($srt['actual_output'] ?? 0),
                    'good' => (int)($srt['good'] ?? 0),
                    'reject' => (int)($srt['reject'] ?? 0),
                    'progress' => (float)($srt['progress'] ?? 0),
                    's1' => ['good' => (int)($srt['s1_good'] ?? $srt['good'] ?? 0), 'reject' => (int)($srt['s1_reject'] ?? $srt['reject'] ?? 0)],
                    's2' => ['good' => (int)($srt['s2_good'] ?? 0), 'reject' => (int)($srt['s2_reject'] ?? 0)],
                    's3' => ['good' => (int)($srt['s3_good'] ?? 0), 'reject' => (int)($srt['s3_reject'] ?? 0)]
                ],
                'lipat' => [
                    'stage' => 'Lipat',
                    'tag' => 'LPT',
                    'mesin' => $lpt['mesin_nama'] ?? 'Herui Speed Gluer',
                    'operator' => $lpt['operator'] ?? 'Jevon',
                    'opsi' => $lpt['opsi'] ?? 'Lipat Lem Folder Gluer',
                    'status' => $lpt['status'] ?? 'antri',
                    'unit' => 'pcs',
                    'plan' => (int)($lpt['plan_qty'] ?? $srt['good'] ?? 100000),
                    'output' => (int)($lpt['actual_output'] ?? 0),
                    'good' => (int)($lpt['good'] ?? 0),
                    'reject' => (int)($lpt['reject'] ?? 0),
                    'progress' => (float)($lpt['progress'] ?? 0)
                ],
                'kirim' => [
                    'status' => ($lpt['progress'] >= 99.0 || $pp['status'] === 'selesai') ? 'siap' : 'antri',
                    'tanggal' => substr($pp['deadline_datetime'] ?: '2026-11-03', 0, 10),
                    'surat_jalan' => 'SJ-2026/' . substr($pp['no_pp'], -4),
                    'armada' => $pp['armada'] ?: 'Colt Diesel Double (CDD)',
                    'sopir' => 'Anto / Logistik',
                    'catatan' => 'Muatan: ' . ($pp['muatan_koli'] ?: 200) . ' koli (' . ($pp['muatan_pallet'] ?: 10) . ' pallet)'
                ]
            ];
            $result[] = $item;
        }

        jsonResponse($result);
    }

    if ($action === 'create_pp' && ($method === 'POST')) {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['no_pp']) || empty($data['produk_nama'])) {
            jsonResponse(['error' => 'Nomor PP dan Nama Produk wajib diisi!'], 400);
        }

        $noPp = trim($data['no_pp']);
        $produkNama = trim($data['produk_nama']);
        $oplaagPcs = (int)($data['oplaag_pcs'] ?? 100000);
        $upPerPlano = max(1, (int)($data['up_per_plano'] ?? 4));
        $totalPlano = (int)($data['total_plano'] ?? ceil($oplaagPcs / $upPerPlano));
        $startDatetime = $data['start_datetime'] ?: date('Y-m-d H:i:s');
        $deadlineDatetime = $data['deadline_datetime'] ?: date('Y-m-d H:i:s', strtotime('+2 days 17:00'));
        $muatanKoli = (int)($data['muatan_koli'] ?? 200);
        $muatanPallet = (int)($data['muatan_pallet'] ?? 10);
        $armada = $data['armada'] ?: 'Colt Diesel Double (CDD)';
        $ritase = max(1, (int)($data['ritase'] ?? 1));
        $alurTipe = $data['alur_tipe'] ?: 'standar';
        $alurLabel = $data['alur_label'] ?: 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Lipat ➜ Kirim)';

        // 1. Cek atau buat produk
        $stmtP = $pdo->prepare("SELECT id FROM produk WHERE LOWER(nama) = LOWER(?) LIMIT 1");
        $stmtP->execute([$produkNama]);
        $pRow = $stmtP->fetch();
        if ($pRow) {
            $pId = (int)$pRow['id'];
        } else {
            $stmtInsP = $pdo->prepare("INSERT INTO produk (nama, famili, std_jph, aktif) VALUES (?, 'General Packaging', 30000, 1)");
            $stmtInsP->execute([$produkNama]);
            $pId = (int)$pdo->lastInsertId();
        }

        // 2. Simpan PP
        $stmtPp = $pdo->prepare("SELECT id FROM pp WHERE no_pp = ? LIMIT 1");
        $stmtPp->execute([$noPp]);
        $ppRow = $stmtPp->fetch();

        if ($ppRow) {
            $ppId = (int)$ppRow['id'];
            $pdo->prepare("
                UPDATE pp SET 
                    produk_id = ?, plan_total = ?, oplaag_pcs = ?, up_per_plano = ?, total_plano = ?,
                    status = 'berjalan', start_datetime = ?, deadline_datetime = ?, muatan_koli = ?,
                    muatan_pallet = ?, armada = ?, ritase = ?, alur_tipe = ?, alur_label = ?
                WHERE id = ?
            ")->execute([
                $pId, $totalPlano, $oplaagPcs, $upPerPlano, $totalPlano,
                $startDatetime, $deadlineDatetime, $muatanKoli, $muatanPallet, $armada, $ritase,
                $alurTipe, $alurLabel, $ppId
            ]);
        } else {
            $pdo->prepare("
                INSERT INTO pp (
                    no_pp, produk_id, plan_total, oplaag_pcs, up_per_plano, total_plano,
                    status, start_datetime, deadline_datetime, muatan_koli, muatan_pallet,
                    armada, ritase, alur_tipe, alur_label, is_extended
                ) VALUES (?, ?, ?, ?, ?, ?, 'berjalan', ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ")->execute([
                $noPp, $pId, $totalPlano, $oplaagPcs, $upPerPlano, $totalPlano,
                $startDatetime, $deadlineDatetime, $muatanKoli, $muatanPallet,
                $armada, $ritase, $alurTipe, $alurLabel
            ]);
            $ppId = (int)$pdo->lastInsertId();
        }

        // 3. Simpan stages jika disediakan
        if (!empty($data['stages']) && is_array($data['stages'])) {
            $pdo->prepare("DELETE FROM pp_stages WHERE pp_id = ?")->execute([$ppId]);
            $stIns = $pdo->prepare("
                INSERT INTO pp_stages (
                    pp_id, stage, stage_num, mesin_id, mesin_nama, operator, opsi, unit,
                    plan_qty, actual_output, good, reject, progress, status,
                    start_datetime, planned_end_datetime, actual_end_datetime, duration_minutes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($data['stages'] as $idx => $st) {
                $stIns->execute([
                    $ppId, $st['stage'], $idx + 1, $st['mesin_id'] ?? null, $st['mesin_nama'] ?? $st['stage'],
                    $st['operator'] ?? 'Tim Produksi', $st['opsi'] ?? '-', $st['unit'] ?? 'plano',
                    $st['plan_qty'] ?? $totalPlano, $st['actual_output'] ?? 0, $st['good'] ?? 0, $st['reject'] ?? 0,
                    $st['progress'] ?? 0.0, $st['status'] ?? 'antri',
                    $st['start_datetime'] ?? $startDatetime, $st['planned_end_datetime'] ?? $deadlineDatetime,
                    $st['actual_end_datetime'] ?? null, $st['duration_minutes'] ?? 120
                ]);
            }
        }

        logActivity($pdo, 'CREATE_PP', "Job Order baru {$noPp} ({$produkNama}) berhasil disimpan ke database");
        jsonResponse(['success' => true, 'pp_id' => $ppId, 'message' => "Job Order {$noPp} berhasil disimpan ke database"]);
    }

    if ($action === 'update_pp' && ($method === 'POST' || $method === 'PUT')) {
        $data = json_decode(file_get_contents('php://input'), true);
        $noPp = trim($data['no_pp'] ?? '');
        if (empty($noPp)) {
            jsonResponse(['error' => 'Nomor PP wajib disertakan!'], 400);
        }

        $stmt = $pdo->prepare("SELECT id FROM pp WHERE no_pp = ? LIMIT 1");
        $stmt->execute([$noPp]);
        $pp = $stmt->fetch();
        if (!$pp) {
            jsonResponse(['error' => "Job Order {$noPp} tidak ditemukan di database"], 404);
        }
        $ppId = (int)$pp['id'];

        $fields = [];
        $params = [];
        if (isset($data['status'])) { $fields[] = "status = ?"; $params[] = $data['status']; }
        if (isset($data['oplaag_pcs'])) { $fields[] = "oplaag_pcs = ?"; $params[] = (int)$data['oplaag_pcs']; }
        if (isset($data['up_per_plano'])) { $fields[] = "up_per_plano = ?"; $params[] = (int)$data['up_per_plano']; }
        if (isset($data['total_plano'])) { $fields[] = "total_plano = ?"; $params[] = (int)$data['total_plano']; }
        if (isset($data['start_datetime'])) { $fields[] = "start_datetime = ?"; $params[] = $data['start_datetime']; }
        if (isset($data['deadline_datetime'])) { $fields[] = "deadline_datetime = ?"; $params[] = $data['deadline_datetime']; }
        if (isset($data['muatan_koli'])) { $fields[] = "muatan_koli = ?"; $params[] = (int)$data['muatan_koli']; }
        if (isset($data['muatan_pallet'])) { $fields[] = "muatan_pallet = ?"; $params[] = (int)$data['muatan_pallet']; }
        if (isset($data['armada'])) { $fields[] = "armada = ?"; $params[] = $data['armada']; }
        if (isset($data['ritase'])) { $fields[] = "ritase = ?"; $params[] = (int)$data['ritase']; }
        if (isset($data['is_extended'])) { $fields[] = "is_extended = ?"; $params[] = (int)$data['is_extended']; }

        if (!empty($fields)) {
            $params[] = $ppId;
            $pdo->prepare("UPDATE pp SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        }

        logActivity($pdo, 'UPDATE_PP', "Job Order {$noPp} berhasil diperbarui di database");
        jsonResponse(['success' => true, 'message' => "Job Order {$noPp} berhasil diperbarui"]);
    }

    jsonResponse(['error' => 'Aksi tidak didukung'], 400);
} catch (Throwable $e) {
    http_response_code(500);
    jsonResponse(['error' => $e->getMessage()], 500);
}
