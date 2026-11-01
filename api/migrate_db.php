<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    // 1. ALTER TABLE mesin
    $colsMesin = $pdo->query("SHOW COLUMNS FROM `mesin`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('divisi', $colsMesin)) {
        $pdo->exec("ALTER TABLE `mesin` ADD COLUMN `divisi` varchar(50) DEFAULT 'sortir'");
    }
    if (!in_array('speed_jph', $colsMesin)) {
        $pdo->exec("ALTER TABLE `mesin` ADD COLUMN `speed_jph` int(11) DEFAULT 30000");
    }
    if (!in_array('unit', $colsMesin)) {
        $pdo->exec("ALTER TABLE `mesin` ADD COLUMN `unit` varchar(20) DEFAULT 'pcs'");
    }

    // 2. ALTER TABLE lkh
    $colsLkh = $pdo->query("SHOW COLUMNS FROM `lkh`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('divisi', $colsLkh)) {
        $pdo->exec("ALTER TABLE `lkh` ADD COLUMN `divisi` varchar(50) DEFAULT 'sortir'");
    }

    // 3. ALTER TABLE pp
    $colsPp = $pdo->query("SHOW COLUMNS FROM `pp`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('oplaag_pcs', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `oplaag_pcs` int(11) DEFAULT 100000");
    }
    if (!in_array('up_per_plano', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `up_per_plano` int(11) DEFAULT 4");
    }
    if (!in_array('total_plano', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `total_plano` int(11) DEFAULT 25000");
    }
    if (!in_array('start_datetime', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `start_datetime` datetime DEFAULT NULL");
    }
    if (!in_array('deadline_datetime', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `deadline_datetime` datetime DEFAULT NULL");
    }
    if (!in_array('muatan_koli', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `muatan_koli` int(11) DEFAULT 200");
    }
    if (!in_array('muatan_pallet', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `muatan_pallet` int(11) DEFAULT 10");
    }
    if (!in_array('armada', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `armada` varchar(100) DEFAULT 'Colt Diesel Double (CDD)'");
    }
    if (!in_array('ritase', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `ritase` int(11) DEFAULT 1");
    }
    if (!in_array('alur_tipe', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `alur_tipe` varchar(50) DEFAULT 'standar'");
    }
    if (!in_array('alur_label', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `alur_label` varchar(255) DEFAULT 'Standar (Cetak ➜ Completing ➜ Cabut ➜ Sortir ➜ Lipat ➜ Kirim)'");
    }
    if (!in_array('is_extended', $colsPp)) {
        $pdo->exec("ALTER TABLE `pp` ADD COLUMN `is_extended` tinyint(1) DEFAULT 0");
    }

    // 4. CREATE TABLE pp_stages
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pp_stages` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `pp_id` int(11) NOT NULL,
          `stage` varchar(50) NOT NULL,
          `stage_num` int(11) NOT NULL DEFAULT 1,
          `mesin_id` varchar(50) DEFAULT NULL,
          `mesin_nama` varchar(100) DEFAULT NULL,
          `operator` varchar(100) DEFAULT NULL,
          `opsi` varchar(255) DEFAULT NULL,
          `unit` varchar(20) DEFAULT 'plano',
          `plan_qty` int(11) DEFAULT 0,
          `actual_output` int(11) DEFAULT 0,
          `good` int(11) DEFAULT 0,
          `reject` int(11) DEFAULT 0,
          `progress` decimal(6,2) DEFAULT 0.00,
          `status` varchar(20) DEFAULT 'antri',
          `start_datetime` datetime DEFAULT NULL,
          `planned_end_datetime` datetime DEFAULT NULL,
          `actual_end_datetime` datetime DEFAULT NULL,
          `duration_minutes` int(11) DEFAULT 0,
          `s1_good` int(11) DEFAULT 0,
          `s1_reject` int(11) DEFAULT 0,
          `s2_good` int(11) DEFAULT 0,
          `s2_reject` int(11) DEFAULT 0,
          `s3_good` int(11) DEFAULT 0,
          `s3_reject` int(11) DEFAULT 0,
          `created_at` timestamp NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `pp_id` (`pp_id`),
          KEY `stage` (`stage`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    echo json_encode(["status" => "success", "message" => "Schema migrated successfully!"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "error" => $e->getMessage()]);
}
