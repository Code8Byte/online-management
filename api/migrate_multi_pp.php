<?php
require_once "config.php";

header('Content-Type: text/plain; charset=utf-8');
echo "=== Starting Migration: Multi-PP and Overtime Support ===\n";

try {
    // 1. Create lkh_pp_items table
    echo "1. Creating table lkh_pp_items if not exists...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `lkh_pp_items` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `lkh_id` int(11) NOT NULL,
          `pp_id` int(11) NOT NULL,
          `tahap_sortir` varchar(10) NOT NULL DEFAULT 'S1',
          `jam_mulai` datetime NOT NULL,
          `jam_selesai` datetime NOT NULL,
          `waktu_prod_jam` decimal(10,4) DEFAULT 0.0000,
          `setting_menit` int(11) DEFAULT 0,
          `5s_menit` int(11) DEFAULT 0,
          `total_dt_menit` int(11) DEFAULT 0,
          `jam_jalan` decimal(10,4) DEFAULT 0.0000,
          `good` int(11) DEFAULT 0,
          `reject` int(11) DEFAULT 0,
          `setup_reject` int(11) DEFAULT 0,
          `total_layar` int(11) DEFAULT 0,
          `total_proses` int(11) DEFAULT 0,
          `plan_shift` int(11) DEFAULT 0,
          `actual_jph` decimal(10,2) DEFAULT 0.00,
          `availability` decimal(6,2) DEFAULT 0.00,
          `performance` decimal(6,2) DEFAULT 0.00,
          `quality_oee` decimal(6,2) DEFAULT 0.00,
          `quality_wip` decimal(6,2) DEFAULT 0.00,
          `oee` decimal(6,2) DEFAULT 0.00,
          `urutan` int(11) DEFAULT 1,
          `created_at` timestamp NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `lkh_id` (`lkh_id`),
          KEY `pp_id` (`pp_id`),
          KEY `tahap_sortir` (`tahap_sortir`),
          CONSTRAINT `lkh_pp_items_ibfk_1` FOREIGN KEY (`lkh_id`) REFERENCES `lkh` (`id`) ON DELETE CASCADE,
          CONSTRAINT `lkh_pp_items_ibfk_2` FOREIGN KEY (`pp_id`) REFERENCES `pp` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
    ");
    echo "   -> lkh_pp_items table checked/created successfully.\n";

    // 2. Add lembur columns to lkh table
    echo "2. Checking lembur columns in lkh...\n";
    $colsStmt = $pdo->query("SHOW COLUMNS FROM `lkh` LIKE 'lembur_menit'");
    if (!$colsStmt->fetch()) {
        $pdo->exec("ALTER TABLE `lkh` ADD COLUMN `lembur_menit` int(11) DEFAULT 0 AFTER `5s_menit`");
        echo "   -> Column lembur_menit added to lkh.\n";
    } else {
        echo "   -> Column lembur_menit already exists.\n";
    }

    $colsStmt2 = $pdo->query("SHOW COLUMNS FROM `lkh` LIKE 'keterangan_lembur'");
    if (!$colsStmt2->fetch()) {
        $pdo->exec("ALTER TABLE `lkh` ADD COLUMN `keterangan_lembur` varchar(255) DEFAULT '' AFTER `lembur_menit`");
        echo "   -> Column keterangan_lembur added to lkh.\n";
    } else {
        echo "   -> Column keterangan_lembur already exists.\n";
    }

    // 3. Add lkh_pp_item_id to lkh_downtime
    echo "3. Checking lkh_pp_item_id in lkh_downtime...\n";
    $colsStmt3 = $pdo->query("SHOW COLUMNS FROM `lkh_downtime` LIKE 'lkh_pp_item_id'");
    if (!$colsStmt3->fetch()) {
        $pdo->exec("ALTER TABLE `lkh_downtime` ADD COLUMN `lkh_pp_item_id` int(11) NULL DEFAULT NULL AFTER `lkh_id`");
        $pdo->exec("ALTER TABLE `lkh_downtime` ADD KEY `idx_dt_pp_item` (`lkh_pp_item_id`)");
        echo "   -> Column lkh_pp_item_id added to lkh_downtime.\n";
    } else {
        echo "   -> Column lkh_pp_item_id already exists in lkh_downtime.\n";
    }

    // 4. Add lkh_pp_item_id to lkh_cacat
    echo "4. Checking lkh_pp_item_id in lkh_cacat...\n";
    $colsStmt4 = $pdo->query("SHOW COLUMNS FROM `lkh_cacat` LIKE 'lkh_pp_item_id'");
    if (!$colsStmt4->fetch()) {
        $pdo->exec("ALTER TABLE `lkh_cacat` ADD COLUMN `lkh_pp_item_id` int(11) NULL DEFAULT NULL AFTER `lkh_id`");
        $pdo->exec("ALTER TABLE `lkh_cacat` ADD KEY `idx_cacat_pp_item` (`lkh_pp_item_id`)");
        echo "   -> Column lkh_pp_item_id added to lkh_cacat.\n";
    } else {
        echo "   -> Column lkh_pp_item_id already exists in lkh_cacat.\n";
    }

    // 5. Backfill existing lkh records into lkh_pp_items
    echo "5. Backfilling existing lkh rows into lkh_pp_items...\n";
    $stmtBackfill = $pdo->exec("
        INSERT INTO `lkh_pp_items` (
            lkh_id, pp_id, tahap_sortir, jam_mulai, jam_selesai,
            waktu_prod_jam, setting_menit, `5s_menit`, total_dt_menit, jam_jalan,
            good, reject, setup_reject, total_layar, total_proses,
            plan_shift, urutan
        )
        SELECT 
            id, pp_id, CASE WHEN tahap_sortir IS NULL OR tahap_sortir = '' THEN 'S1' ELSE tahap_sortir END,
            start, finish,
            waktu_prod_jam, setting_menit, `5s_menit`, total_dt_menit, jam_jalan,
            good, reject, setup_reject, total_layar, total_proses,
            plan_shift, 1
        FROM `lkh`
        WHERE id NOT IN (SELECT DISTINCT lkh_id FROM `lkh_pp_items`)
    ");
    echo "   -> Backfilled rows: " . ($stmtBackfill !== false ? $stmtBackfill : 0) . "\n";

    // 6. Link existing lkh_downtime to lkh_pp_items
    echo "6. Linking existing lkh_downtime to lkh_pp_items...\n";
    $stmtLinkDt = $pdo->exec("
        UPDATE `lkh_downtime` ld
        JOIN `lkh_pp_items` lpi ON ld.lkh_id = lpi.lkh_id AND lpi.urutan = 1
        SET ld.lkh_pp_item_id = lpi.id
        WHERE ld.lkh_pp_item_id IS NULL
    ");
    echo "   -> Linked downtime rows: " . ($stmtLinkDt !== false ? $stmtLinkDt : 0) . "\n";

    // 7. Link existing lkh_cacat to lkh_pp_items
    echo "7. Linking existing lkh_cacat to lkh_pp_items...\n";
    $stmtLinkCacat = $pdo->exec("
        UPDATE `lkh_cacat` lc
        JOIN `lkh_pp_items` lpi ON lc.lkh_id = lpi.lkh_id AND lpi.urutan = 1
        SET lc.lkh_pp_item_id = lpi.id
        WHERE lc.lkh_pp_item_id IS NULL
    ");
    echo "   -> Linked defect rows: " . ($stmtLinkCacat !== false ? $stmtLinkCacat : 0) . "\n";

    echo "\n=== MIGRATION COMPLETED SUCCESSFULLY! ===\n";

} catch (Exception $e) {
    echo "ERROR DURING MIGRATION: " . $e->getMessage() . "\n";
}
