<?php
// Example Database Configuration Template
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Koneksi database gagal."]);
    exit;
}

// Session configuration for secure cookies
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

session_start();

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(["error" => "Unauthorized"], 401);
    }
}

function checkRole($allowedRoles = ['admin']) {
    checkAuth();
    if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
        jsonResponse(["error" => "Akses ditolak: role Anda tidak memiliki izin untuk tindakan ini"], 403);
    }
}

function verifyCsrfToken() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonResponse(["error" => "CSRF token tidak valid"], 403);
    }
}

function logActivity($action, $detail = '') {
    global $pdo;
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO log_aktivitas (user_id, aksi, detail, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $detail, $ip]);
    } catch (Exception $e) {
        // Silently continue
    }
}
