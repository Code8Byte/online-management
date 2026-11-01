<?php
require_once __DIR__ . '/config.php';

try {
    $data = [
        "users" => $pdo->query("SELECT id, username, role, divisi FROM users")->fetchAll(),
        "mesin" => $pdo->query("SELECT * FROM mesin")->fetchAll(),
        "produk" => $pdo->query("SELECT * FROM produk LIMIT 10")->fetchAll(),
        "pp" => $pdo->query("SELECT * FROM pp LIMIT 10")->fetchAll(),
        "lkh_count" => $pdo->query("SELECT COUNT(*) FROM lkh")->fetchColumn(),
    ];
    jsonResponse($data);
} catch (Exception $e) {
    jsonResponse(["error" => $e->getMessage()], 500);
}
