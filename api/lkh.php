<?php
require_once 'config.php';
checkAuth();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && $action === 'list') {
    // Pagination and filters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    
    $where = ["1=1"];
    $params = [];
    
    if (!empty($_GET['tanggal'])) {
        $where[] = "lkh.tanggal = ?";
        $params[] = $_GET['tanggal'];
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
    if (!empty($_GET['produk_id'])) {
        $where[] = "pp.produk_id = ?";
        $params[] = $_GET['produk_id'];
    }
    if (!empty($_GET['produk_nama'])) {
        $where[] = "p.nama LIKE ?";
        $params[] = "%" . trim($_GET['produk_nama']) . "%";
    }
    
    $whereSql = implode(" AND ", $where);
    
    $countSql = "SELECT COUNT(*) FROM lkh_pp_items lpi
                 JOIN lkh ON lpi.lkh_id = lkh.id
                 JOIN pp ON lpi.pp_id = pp.id 
                 JOIN produk p ON pp.produk_id = p.id 
                 JOIN mesin m ON lkh.mesin_id = m.id 
                 WHERE $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
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
                lpi.total_dt_menit AS total_dt_menit,
                lpi.jam_jalan AS jam_jalan,
                lpi.waktu_prod_jam AS waktu_prod_jam,
                lpi.plan_shift AS plan_shift,
                lpi.actual_jph AS actual_jph,
                lpi.availability AS availability,
                lpi.performance AS performance,
                lpi.quality_oee AS quality_oee,
                lpi.quality_wip AS quality_wip,
                lpi.oee AS oee,
                lpi.urutan,
                lkh.id,
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
                lkh.keterangan_lembur,
                lkh.problem,
                lkh.root_cause,
                lkh.tindakan_sementara,
                lkh.tindakan_perbaikan,
                pp.no_pp,
                pp.status AS pp_status,
                p.std_jph,
                p.nama as produk_nama,
                p.famili,
                m.nama as mesin_nama,
                (SELECT COUNT(*) FROM lkh_pp_items WHERE lkh_id = lkh.id) AS pp_count
            FROM lkh_pp_items lpi
            JOIN lkh ON lpi.lkh_id = lkh.id
            JOIN pp ON lpi.pp_id = pp.id 
            JOIN produk p ON pp.produk_id = p.id
            JOIN mesin m ON lkh.mesin_id = m.id
            WHERE $whereSql 
            ORDER BY lkh.tanggal DESC, lkh.shift DESC, m.nama ASC, lpi.urutan ASC, lpi.id ASC 
            LIMIT $limit OFFSET $offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    foreach ($data as &$row) {
        $std_jph = (float)($row['std_jph'] ?? 0);
        $jam_jalan = (float)($row['jam_jalan'] ?? 0);
        $pp_status = $row['pp_status'] ?? 'aktif';

        if ($pp_status === 'req' || $std_jph <= 0) {
            $row['is_pending'] = true;
            $row['plan_shift'] = null;
            $row['performance'] = null;
            $row['oee'] = null;
        } else {
            $row['is_pending'] = false;
            $waktuProd = (float)($row['waktu_prod_jam'] ?? 0);
            $totProses = (int)($row['total_proses'] ?? 0);
            $good = (int)($row['good'] ?? 0);
            $setup = (int)($row['setup_reject'] ?? 0);

            $avail = (float)($row['availability'] ?? 0);
            if ($avail <= 0 && $waktuProd > 0) {
                $avail = round(($jam_jalan / $waktuProd) * 100, 2);
                $row['availability'] = $avail;
            }

            $qualOee = (float)($row['quality_oee'] ?? 0);
            if ($qualOee <= 0 && $totProses > 0) {
                $qualOee = round((($totProses - $setup) / $totProses) * 100, 2);
                $row['quality_oee'] = $qualOee;
            }

            $qualWip = (float)($row['quality_wip'] ?? 0);
            if ($qualWip <= 0 && $totProses > 0) {
                $qualWip = round(($good / $totProses) * 100, 2);
                $row['quality_wip'] = $qualWip;
            }

            if ($std_jph > 0 && $jam_jalan > 0) {
                $row['plan_shift'] = (int)round($jam_jalan * $std_jph);
                $planShift = $row['plan_shift'];
                $perf = ($planShift > 0) ? round(($totProses / $planShift) * 100, 2) : 0;
                $row['performance'] = $perf;
                $row['oee'] = round(($avail * $perf * $qualOee) / 10000, 2);
            }
        }
        if (empty($row['tahap_sortir'])) {
            $row['tahap_sortir'] = 'S1';
        }
        $row['start'] = $row['jam_mulai'];
        $row['finish'] = $row['jam_selesai'];
    }
    unset($row);
    
    jsonResponse(['total' => $total, 'data' => $data, 'page' => $page, 'limit' => $limit]);
}

if ($method === 'GET' && $action === 'detail') {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("
        SELECT lkh.*, pp.no_pp, pp.status AS pp_status, p.std_jph, p.nama AS produk_nama, p.famili, m.nama AS mesin_nama 
        FROM lkh 
        JOIN pp ON lkh.pp_id = pp.id 
        JOIN produk p ON pp.produk_id = p.id 
        JOIN mesin m ON lkh.mesin_id = m.id 
        WHERE lkh.id = ?
    ");
    $stmt->execute([$id]);
    $lkh = $stmt->fetch();
    
    if (!$lkh) jsonResponse(['error' => 'Data tidak ditemukan'], 404);

    // Hitung ulang target shift dinamis berdasarkan std_jph produk terbaru jika ada
    $std_jph = (float)($lkh['std_jph'] ?? 0);
    $jam_jalan = (float)($lkh['jam_jalan'] ?? 0);
    $pp_status = $lkh['pp_status'] ?? 'aktif';

    if ($pp_status === 'req' || $std_jph <= 0) {
        $lkh['is_pending'] = true;
        $lkh['plan_shift'] = null;
    } else {
        $lkh['is_pending'] = false;
        if ($std_jph > 0 && $jam_jalan > 0) {
            $lkh['plan_shift'] = (int)round($jam_jalan * $std_jph);
        }
    }
    if (empty($lkh['tahap_sortir'])) {
        $lkh['tahap_sortir'] = 'S1';
    }

    // Ambil daftar multi-PP items dari lkh_pp_items
    $stmtPpItems = $pdo->prepare("
        SELECT lpi.*, pp.no_pp, pp.status AS pp_status, p.std_jph, p.nama AS produk_nama, p.famili
        FROM lkh_pp_items lpi
        JOIN pp ON lpi.pp_id = pp.id
        JOIN produk p ON pp.produk_id = p.id
        WHERE lpi.lkh_id = ?
        ORDER BY lpi.urutan ASC, lpi.id ASC
    ");
    $stmtPpItems->execute([$id]);
    $ppItems = $stmtPpItems->fetchAll();

    $stmtDtItem = $pdo->prepare("
        SELECT ld.*, kd.kode, kd.arti, kd.kategori, kd.jenis_waste
        FROM lkh_downtime ld
        LEFT JOIN kode_downtime kd ON ld.kode_id = kd.id
        WHERE ld.lkh_pp_item_id = ?
        ORDER BY ld.id ASC
    ");

    $stmtCacatItem = $pdo->prepare("
        SELECT lc.*, jc.nama AS jenis_cacat_nama
        FROM lkh_cacat lc
        LEFT JOIN jenis_cacat jc ON lc.jenis_cacat_id = jc.id
        WHERE lc.lkh_pp_item_id = ?
        ORDER BY lc.kategori ASC, jc.urutan ASC, jc.nama ASC
    ");

    foreach ($ppItems as &$item) {
        $itemStd = (float)($item['std_jph'] ?? 0);
        $itemPpStatus = $item['pp_status'] ?? 'aktif';
        if ($itemPpStatus === 'req' || $itemStd <= 0) {
            $item['is_pending'] = true;
            $item['plan_shift'] = null;
            $item['performance'] = null;
            $item['oee'] = null;
        } else {
            $item['is_pending'] = false;
        }

        $stmtDtItem->execute([$item['id']]);
        $item['downtime'] = $stmtDtItem->fetchAll();

        $stmtCacatItem->execute([$item['id']]);
        $item['cacat'] = $stmtCacatItem->fetchAll();
    }
    unset($item);

    $lkh['pp_items'] = $ppItems;
    
    // Fallback Downtime & Cacat level shift untuk backward compatibility
    $stmtDt = $pdo->prepare("
        SELECT ld.*, kd.kode, kd.arti, kd.kategori, kd.jenis_waste
        FROM lkh_downtime ld
        LEFT JOIN kode_downtime kd ON ld.kode_id = kd.id
        WHERE ld.lkh_id = ?
        ORDER BY ld.id ASC
    ");
    $stmtDt->execute([$id]);
    $lkh['downtime'] = $stmtDt->fetchAll();
    
    $stmtCacat = $pdo->prepare("
        SELECT lc.*, jc.nama AS jenis_cacat_nama
        FROM lkh_cacat lc
        LEFT JOIN jenis_cacat jc ON lc.jenis_cacat_id = jc.id
        WHERE lc.lkh_id = ?
        ORDER BY lc.kategori ASC, jc.urutan ASC, jc.nama ASC
    ");
    $stmtCacat->execute([$id]);
    $lkh['cacat'] = $stmtCacat->fetchAll();
    
    jsonResponse($lkh);
}

if (($method === 'POST' || $method === 'PUT') && in_array($action, ['save', 'create', 'update'])) {
    if (($_SESSION['role'] ?? '') === 'viewer') {
        jsonResponse(['error' => 'Akses ditolak: Viewer hanya dapat melihat data LKH.'], 403);
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        jsonResponse(['error' => 'Data payload tidak valid'], 400);
    }
    
    try {
        $pdo->beginTransaction();
        
        $shift_start = new DateTime($data['start']);
        $shift_finish = new DateTime($data['finish']);
        $shift_start_ts = $shift_start->getTimestamp();
        $shift_finish_ts = $shift_finish->getTimestamp();
        if ($shift_finish_ts <= $shift_start_ts) {
            $shift_finish_ts += 86400; // overnight handling
        }
        $shift_duration_jam = ($shift_finish_ts - $shift_start_ts) / 3600;

        $setting_menit = (int)($data['setting_menit'] ?? 0);
        $lima_s_menit = (int)($data['5s_menit'] ?? 0);
        $lembur_menit = (int)($data['lembur_menit'] ?? 0);
        $keterangan_lembur = trim($data['keterangan_lembur'] ?? '');

        // Standardize pp_items
        $rawPpItems = $data['pp_items'] ?? [];
        if (!is_array($rawPpItems) || empty($rawPpItems)) {
            $rawPpItems = [[
                'pp_id' => $data['pp_id'] ?? null,
                'tahap_sortir' => $data['tahap_sortir'] ?? 'S1',
                'jam_mulai' => $data['start'],
                'good' => (int)($data['good'] ?? 0),
                'reject' => (int)($data['reject'] ?? 0),
                'setup_reject' => (int)($data['setup_reject'] ?? 0),
                'total_layar' => (int)($data['total_layar'] ?? 0),
                'downtime' => $data['downtime'] ?? [],
                'cacat' => $data['cacat'] ?? []
            ]];
        }

        $num_pp = count($rawPpItems);
        $calculated_items = [];
        $total_good = 0;
        $total_reject = 0;
        $total_setup_reject = 0;
        $total_proses = 0;
        $total_layar = 0;
        $total_dt_menit = 0;
        $total_jam_jalan = 0;
        $total_plan_shift = 0;

        // Fetch std_jph & status cache for all PPs involved
        $ppIds = array_filter(array_map('intval', array_column($rawPpItems, 'pp_id')));
        $stdJphMap = [];
        $ppStatusMap = [];
        if (!empty($ppIds)) {
            $inPp = implode(',', array_fill(0, count($ppIds), '?'));
            $stmtPpMap = $pdo->prepare("SELECT pp.id, pp.status, p.std_jph FROM pp JOIN produk p ON pp.produk_id = p.id WHERE pp.id IN ($inPp)");
            $stmtPpMap->execute(array_values($ppIds));
            while ($pRow = $stmtPpMap->fetch(PDO::FETCH_ASSOC)) {
                $stdJphMap[$pRow['id']] = (float)$pRow['std_jph'];
                $ppStatusMap[$pRow['id']] = $pRow['status'] ?? 'aktif';
            }
        }

        for ($i = 0; $i < $num_pp; $i++) {
            $item = $rawPpItems[$i];
            $pp_id = (int)($item['pp_id'] ?? 0);
            if ($pp_id <= 0) {
                throw new Exception("Nomor PP pada Job Order #" . ($i + 1) . " wajib dipilih.");
            }
            $tahap_sortir = in_array($item['tahap_sortir'] ?? '', ['S1', 'S2', 'S3']) ? $item['tahap_sortir'] : 'S1';

            // Boundary:
            // PP 0 mulai dari shift start.
            // PP i (i > 0) mulai dari jam_mulai (Jam Pindah).
            $item_start_str = ($i === 0) ? $data['start'] : ($item['jam_mulai'] ?? $data['start']);

            // PP i (i < num_pp - 1) selesai pada jam_mulai PP berikutnya (batas kerja PP).
            // PP terakhir selesai pada shift finish.
            $item_finish_str = ($i < $num_pp - 1) ? ($rawPpItems[$i + 1]['jam_mulai'] ?? $data['finish']) : $data['finish'];

            $s_ts = strtotime($item_start_str);
            $f_ts = strtotime($item_finish_str);
            if ($f_ts <= $s_ts) {
                $f_ts += 86400; // overnight handling
            }
            $segment_durasi_jam = ($f_ts - $s_ts) / 3600;

            // Setup / Setting pengurang: dialokasikan ke PP pertama
            $item_setting = ($i === 0) ? $setting_menit : 0;
            // 5S pengurang: dialokasikan ke PP terakhir
            $item_5s = ($i === $num_pp - 1) ? $lima_s_menit : 0;

            // Item Downtime
            $item_dt_menit = 0;
            $clean_dt = [];
            if (!empty($item['downtime']) && is_array($item['downtime'])) {
                foreach ($item['downtime'] as $dt) {
                    $m = (int)($dt['menit'] ?? 0);
                    $kId = (int)($dt['kode_id'] ?? 0);
                    if ($kId > 0 && $m > 0) {
                        $item_dt_menit += $m;
                        $clean_dt[] = [
                            'kode_id' => $kId,
                            'menit' => $m,
                            'keterangan' => trim($dt['keterangan'] ?? '')
                        ];
                    }
                }
            }

            // Calculations
            $item_waktu_prod_jam = max(0, $segment_durasi_jam - ($item_5s / 60));
            $item_jam_jalan = max(0, $item_waktu_prod_jam - (($item_setting + $item_dt_menit) / 60));

            $item_good = (int)($item['good'] ?? 0);
            $item_reject = (int)($item['reject'] ?? 0);
            $item_setup_reject = (int)($item['setup_reject'] ?? 0);
            $item_total_proses = $item_good + $item_reject + $item_setup_reject;
            $item_total_layar = (int)($item['total_layar'] ?? $item_total_proses);

            $stdJph = $stdJphMap[$pp_id] ?? 0;
            $isPpReq = (($ppStatusMap[$pp_id] ?? '') === 'req') || ($stdJph <= 0);

            $item_plan_shift = (!$isPpReq && $item_jam_jalan > 0 && $stdJph > 0) ? (int)round($item_jam_jalan * $stdJph) : 0;
            $item_actual_jph = ($item_jam_jalan > 0) ? round($item_total_proses / $item_jam_jalan, 2) : 0;

            $item_avail = ($item_waktu_prod_jam > 0) ? round(($item_jam_jalan / $item_waktu_prod_jam) * 100, 2) : 0;
            $item_perf = (!$isPpReq && $item_plan_shift > 0) ? round(($item_total_proses / $item_plan_shift) * 100, 2) : 0;
            $item_qual_oee = ($item_total_proses > 0) ? round((($item_total_proses - $item_setup_reject) / $item_total_proses) * 100, 2) : 0;
            $item_qual_wip = ($item_total_proses > 0) ? round(($item_good / $item_total_proses) * 100, 2) : 0;
            $item_oee = (!$isPpReq) ? round(($item_avail * $item_perf * $item_qual_oee) / 10000, 2) : 0;

            // Clean Cacat
            $clean_cacat = [];
            if (!empty($item['cacat']) && is_array($item['cacat'])) {
                foreach ($item['cacat'] as $c) {
                    $q = (int)($c['qty'] ?? 0);
                    $jId = (int)($c['jenis_cacat_id'] ?? 0);
                    if ($jId > 0 && $q > 0) {
                        $clean_cacat[] = [
                            'jenis_cacat_id' => $jId,
                            'kategori' => in_array($c['kategori'] ?? '', ['reject', 'setup']) ? $c['kategori'] : 'reject',
                            'qty' => $q
                        ];
                    }
                }
            }

            $calculated_items[] = [
                'pp_id' => $pp_id,
                'tahap_sortir' => $tahap_sortir,
                'jam_mulai' => date('Y-m-d H:i:s', $s_ts),
                'jam_selesai' => date('Y-m-d H:i:s', $f_ts),
                'waktu_prod_jam' => $item_waktu_prod_jam,
                'setting_menit' => $item_setting,
                '5s_menit' => $item_5s,
                'total_dt_menit' => $item_dt_menit,
                'jam_jalan' => $item_jam_jalan,
                'good' => $item_good,
                'reject' => $item_reject,
                'setup_reject' => $item_setup_reject,
                'total_layar' => $item_total_layar,
                'total_proses' => $item_total_proses,
                'plan_shift' => $item_plan_shift,
                'actual_jph' => $item_actual_jph,
                'availability' => $item_avail,
                'performance' => $item_perf,
                'quality_oee' => $item_qual_oee,
                'quality_wip' => $item_qual_wip,
                'oee' => $item_oee,
                'urutan' => $i + 1,
                'downtime' => $clean_dt,
                'cacat' => $clean_cacat
            ];

            $total_good += $item_good;
            $total_reject += $item_reject;
            $total_setup_reject += $item_setup_reject;
            $total_proses += $item_total_proses;
            $total_layar += $item_total_layar;
            $total_dt_menit += $item_dt_menit;
            $total_jam_jalan += $item_jam_jalan;
            $total_plan_shift += $item_plan_shift;
        }

        $waktu_prod_jam = max(0, $shift_duration_jam - ($lima_s_menit / 60));
        $firstItem = $calculated_items[0];

        $detail_pallet = json_encode($data['pallets'] ?? []);

        $id = !empty($data['id']) ? (int)$data['id'] : (!empty($_GET['id']) ? (int)$_GET['id'] : null);
        $userRole = $_SESSION['role'] ?? '';
        $currentUsername = $_SESSION['username'] ?? '';
        $existingRow = null;

        if ($id) {
            // Check existing LKH row
            $stmtEx = $pdo->prepare("
                SELECT lkh.*, pp.no_pp, m.nama AS mesin_nama 
                FROM lkh 
                JOIN pp ON lkh.pp_id = pp.id 
                JOIN mesin m ON lkh.mesin_id = m.id 
                WHERE lkh.id = ?
            ");
            $stmtEx->execute([$id]);
            $existingRow = $stmtEx->fetch(PDO::FETCH_ASSOC);
            if (!$existingRow) {
                jsonResponse(['error' => 'Data LKH tidak ditemukan.'], 404);
            }

            if ($userRole !== 'admin') {
                if (empty($existingRow['pic']) || strcasecmp(trim($existingRow['pic']), trim($currentUsername)) !== 0) {
                    jsonResponse([
                        'error' => "Akses ditolak: Anda hanya dapat mengubah data LKH yang PIC-nya sesuai dengan nama login akun Anda ('{$currentUsername}'). Data ini dibuat oleh PIC: " . ($existingRow['pic'] ?: '(Kosong)')
                    ], 403);
                }
            }

            $pic = !empty($existingRow['pic']) ? $existingRow['pic'] : $currentUsername;
        } else {
            $pic = $currentUsername;
        }

        $lkhData = [
            $firstItem['pp_id'], $firstItem['tahap_sortir'], $data['tanggal'], $data['shift'], $data['mesin_id'], 
            $data['operator'], $data['start'], $data['finish'],
            $total_good, $total_reject, $total_setup_reject, $total_layar,
            $setting_menit, $lima_s_menit, $lembur_menit, $keterangan_lembur,
            $data['problem'] ?? '', $data['root_cause'] ?? '',
            $data['tindakan_sementara'] ?? '', $data['tindakan_perbaikan'] ?? '',
            $pic, $total_proses, $waktu_prod_jam, $total_dt_menit, $total_jam_jalan,
            $detail_pallet, $total_plan_shift
        ];

        if ($id) {
            $lkhData[] = $id;
            $sql = "UPDATE lkh SET 
                pp_id=?, tahap_sortir=?, tanggal=?, shift=?, mesin_id=?, operator=?, start=?, finish=?,
                good=?, reject=?, setup_reject=?, total_layar=?, `setting_menit`=?, `5s_menit`=?,
                `lembur_menit`=?, `keterangan_lembur`=?,
                problem=?, root_cause=?, tindakan_sementara=?, tindakan_perbaikan=?,
                pic=?, total_proses=?, waktu_prod_jam=?, total_dt_menit=?, jam_jalan=?,
                detail_pallet=?, plan_shift=?
                WHERE id=?";
            $pdo->prepare($sql)->execute($lkhData);
            
            // Hapus detail lama untuk digantikan
            $pdo->prepare("DELETE FROM lkh_downtime WHERE lkh_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM lkh_cacat WHERE lkh_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM lkh_pp_items WHERE lkh_id = ?")->execute([$id]);
        } else {
            $sql = "INSERT INTO lkh (
                pp_id, tahap_sortir, tanggal, shift, mesin_id, operator, start, finish,
                good, reject, setup_reject, total_layar, `setting_menit`, `5s_menit`,
                `lembur_menit`, `keterangan_lembur`,
                problem, root_cause, tindakan_sementara, tindakan_perbaikan,
                pic, total_proses, waktu_prod_jam, total_dt_menit, jam_jalan,
                detail_pallet, plan_shift
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute($lkhData);
            $id = $pdo->lastInsertId();
        }

        // Insert per-PP items into lkh_pp_items
        $stmtInsertItem = $pdo->prepare("
            INSERT INTO lkh_pp_items (
                lkh_id, pp_id, tahap_sortir, jam_mulai, jam_selesai,
                waktu_prod_jam, setting_menit, `5s_menit`, total_dt_menit, jam_jalan,
                good, reject, setup_reject, total_layar, total_proses,
                plan_shift, actual_jph, availability, performance, quality_oee, quality_wip, oee, urutan
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmtDt = $pdo->prepare("INSERT INTO lkh_downtime (lkh_id, lkh_pp_item_id, kode_id, menit, keterangan) VALUES (?, ?, ?, ?, ?)");
        $stmtCacat = $pdo->prepare("INSERT INTO lkh_cacat (lkh_id, lkh_pp_item_id, jenis_cacat_id, kategori, qty) VALUES (?, ?, ?, ?, ?)");

        foreach ($calculated_items as $cItem) {
            $stmtInsertItem->execute([
                $id, $cItem['pp_id'], $cItem['tahap_sortir'], $cItem['jam_mulai'], $cItem['jam_selesai'],
                $cItem['waktu_prod_jam'], $cItem['setting_menit'], $cItem['5s_menit'], $cItem['total_dt_menit'], $cItem['jam_jalan'],
                $cItem['good'], $cItem['reject'], $cItem['setup_reject'], $cItem['total_layar'], $cItem['total_proses'],
                $cItem['plan_shift'], $cItem['actual_jph'], $cItem['availability'], $cItem['performance'], $cItem['quality_oee'], $cItem['quality_wip'], $cItem['oee'],
                $cItem['urutan']
            ]);
            $ppItemId = $pdo->lastInsertId();

            // Insert Downtime linked to this PP item
            foreach ($cItem['downtime'] as $dt) {
                $stmtDt->execute([$id, $ppItemId, $dt['kode_id'], $dt['menit'], $dt['keterangan']]);
            }

            // Insert Cacat linked to this PP item
            foreach ($cItem['cacat'] as $c) {
                $stmtCacat->execute([$id, $ppItemId, $c['jenis_cacat_id'], $c['kategori'], $c['qty']]);
            }
        }
        
        $pdo->commit();

        // Flush dashboard cache
        $cacheFiles = glob(__DIR__ . '/cache/dashboard_*.json');
        if ($cacheFiles) {
            foreach ($cacheFiles as $cf) @unlink($cf);
        }

        // Catat Log Aktivitas
        $ppDisplayList = implode(', ', array_map(function($ci) {
            return "PP #{$ci['urutan']}: {$ci['pp_id']}";
        }, $calculated_items));

        if ($existingRow) {
            logActivity($pdo, 'EDIT_LKH', "Mengubah LKH #{$id} ({$ppDisplayList}, Mesin ID: {$data['mesin_id']}, Shift: {$data['shift']}, Total Good: {$total_good}, Total Reject: {$total_reject}, PIC: {$pic})");
        } else {
            logActivity($pdo, 'ISI_LKH', "Mengisi LKH baru #{$id} ({$ppDisplayList}, Mesin ID: {$data['mesin_id']}, Shift: {$data['shift']}, Total Good: {$total_good}, Total Reject: {$total_reject}, PIC: {$pic})");
        }

        jsonResponse(['success' => true, 'id' => $id]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['error' => $e->getMessage() . ' at line ' . $e->getLine()], 500);
    }
}

if ($method === 'DELETE' && $action === 'delete') {
    checkRole(['admin']);
    $id = (int)($_GET['id'] ?? 0);
    $stmtEx = $pdo->prepare("
        SELECT lkh.*, pp.no_pp, m.nama AS mesin_nama 
        FROM lkh 
        JOIN pp ON lkh.pp_id = pp.id 
        JOIN mesin m ON lkh.mesin_id = m.id 
        WHERE lkh.id = ?
    ");
    $stmtEx->execute([$id]);
    $delRow = $stmtEx->fetch(PDO::FETCH_ASSOC);
    $noPp = $delRow['no_pp'] ?? '-';
    $mesin = $delRow['mesin_nama'] ?? '-';

    $pdo->prepare("DELETE FROM lkh_downtime WHERE lkh_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM lkh_cacat WHERE lkh_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM lkh_pp_items WHERE lkh_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM lkh WHERE id = ?")->execute([$id]);

    logActivity($pdo, 'DELETE_LKH', "Menghapus data LKH #{$id} (No PP: {$noPp}, Mesin: {$mesin})");
    jsonResponse(['success' => true]);
}

if ($method === 'GET' && $action === 'progress_pp') {
    checkRole(['admin', 'viewer']);
    $stmt = $pdo->query("
        SELECT 
            pp.id,
            pp.no_pp,
            pp.plan_total,
            pp.status,
            p.id AS produk_id,
            p.nama AS produk_nama,
            p.famili,
            p.std_jph,
            COALESCE(SUM(CASE WHEN l.tahap_sortir = 'S1' OR l.tahap_sortir IS NULL OR l.tahap_sortir = '' THEN l.total_proses ELSE 0 END), 0) AS total_s1,
            COALESCE(SUM(CASE WHEN l.tahap_sortir = 'S1' OR l.tahap_sortir IS NULL OR l.tahap_sortir = '' THEN l.good ELSE 0 END), 0) AS good_s1,
            COALESCE(SUM(CASE WHEN l.tahap_sortir = 'S1' OR l.tahap_sortir IS NULL OR l.tahap_sortir = '' THEN l.reject ELSE 0 END), 0) AS reject_s1,
            COALESCE(SUM(CASE WHEN l.tahap_sortir = 'S2' THEN l.total_proses ELSE 0 END), 0) AS total_s2,
            COALESCE(SUM(CASE WHEN l.tahap_sortir = 'S2' THEN l.good ELSE 0 END), 0) AS good_s2,
            COALESCE(SUM(CASE WHEN l.tahap_sortir = 'S2' THEN l.reject ELSE 0 END), 0) AS reject_s2,
            COALESCE(SUM(CASE WHEN l.tahap_sortir = 'S3' THEN l.total_proses ELSE 0 END), 0) AS total_s3,
            COALESCE(SUM(CASE WHEN l.tahap_sortir = 'S3' THEN l.good ELSE 0 END), 0) AS good_s3,
            COALESCE(SUM(CASE WHEN l.tahap_sortir = 'S3' THEN l.reject ELSE 0 END), 0) AS reject_s3,
            COALESCE(SUM(l.total_proses), 0) AS total_semua,
            MAX(lkh_m.tanggal) AS last_sortir_date,
            COUNT(l.id) AS total_lkh_count
        FROM pp
        JOIN produk p ON pp.produk_id = p.id
        LEFT JOIN lkh_pp_items l ON l.pp_id = pp.id
        LEFT JOIN lkh lkh_m ON l.lkh_id = lkh_m.id
        GROUP BY pp.id
        ORDER BY 
            CASE WHEN pp.status = 'req' THEN 0 WHEN pp.status = 'aktif' THEN 1 ELSE 2 END ASC, 
            pp.id DESC
    ");
    $data = $stmt->fetchAll();
    jsonResponse($data);
}

if ($method === 'GET' && $action === 'akumulasi_pp') {
    $pp_id = $_GET['pp_id'] ?? 0;
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN tahap_sortir = 'S1' OR tahap_sortir IS NULL OR tahap_sortir = '' THEN total_proses ELSE 0 END), 0) AS total_s1,
            COALESCE(SUM(CASE WHEN tahap_sortir = 'S2' THEN total_proses ELSE 0 END), 0) AS total_s2,
            COALESCE(SUM(CASE WHEN tahap_sortir = 'S3' THEN total_proses ELSE 0 END), 0) AS total_s3,
            COALESCE(SUM(total_proses), 0) AS total_semua
        FROM lkh_pp_items 
        WHERE pp_id = ?
    ");
    $stmt->execute([$pp_id]);
    $res = $stmt->fetch();
    jsonResponse([
        'total_tersortir' => (int)($res['total_s1'] ?? 0),
        'total_proses' => (int)($res['total_s1'] ?? 0),
        'total_s1' => (int)($res['total_s1'] ?? 0),
        'total_s2' => (int)($res['total_s2'] ?? 0),
        'total_s3' => (int)($res['total_s3'] ?? 0),
        'total_semua' => (int)($res['total_semua'] ?? 0)
    ]);
}

jsonResponse(['error' => 'Endpoint tidak ditemukan'], 404);
