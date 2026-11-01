<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

try {
    // Hapus seluruh dummy data Job Order & LKH dari database sebelum uji coba
    $pdo->exec("DELETE FROM lkh_cacat");
    $pdo->exec("DELETE FROM lkh_downtime");
    $pdo->exec("DELETE FROM lkh_pp_items");
    $pdo->exec("DELETE FROM lkh");
    $pdo->exec("DELETE FROM pp_stages");
    $pdo->exec("DELETE FROM antrian_pallet");
    $pdo->exec("DELETE FROM pp");

    // Reset AUTO_INCREMENT
    $pdo->exec("ALTER TABLE pp AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE pp_stages AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE lkh AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE lkh_pp_items AUTO_INCREMENT = 1");

    echo json_encode([
        "status" => "success",
        "message" => "Seluruh dummy data Job Order (PP) dan LKH sebelum uji coba telah berhasil dibersihkan dari database MySQL!",
        "counts" => [
            "pp" => $pdo->query("SELECT COUNT(*) FROM pp")->fetchColumn(),
            "pp_stages" => $pdo->query("SELECT COUNT(*) FROM pp_stages")->fetchColumn(),
            "lkh" => $pdo->query("SELECT COUNT(*) FROM lkh")->fetchColumn(),
            "mesin" => $pdo->query("SELECT COUNT(*) FROM mesin")->fetchColumn(),
            "users" => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "error" => $e->getMessage()]);
}
