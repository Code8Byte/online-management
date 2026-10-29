<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && $action === 'csrf') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    jsonResponse(['csrf_token' => $_SESSION['csrf_token']]);
}

if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Cleanup old attempts
    $pdo->exec("DELETE FROM login_attempts WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    
    $stmt = $pdo->prepare("SELECT attempts FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
    $attempt = $stmt->fetch();
    
    if ($attempt && $attempt['attempts'] >= 5) {
        jsonResponse(['error' => 'Terlalu banyak percobaan. Coba lagi dalam 10 menit.'], 429);
    }
    
    $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];
        $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
        logActivity($pdo, 'LOGIN', "User '{$username}' berhasil login ({$user['role']})");
        jsonResponse(['success' => true, 'role' => $user['role']]);
    } else {
        $pdo->prepare("INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()")->execute([$ip]);
        jsonResponse(['error' => 'Username atau password salah.'], 401);
    }
}

if ($method === 'POST' && $action === 'logout') {
    if (!empty($_SESSION['username'])) {
        logActivity($pdo, 'LOGOUT', "User '{$_SESSION['username']}' berhasil logout");
    }
    session_destroy();
    jsonResponse(['success' => true]);
}

if ($method === 'GET' && $action === 'me') {
    checkAuth();
    jsonResponse([
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'role' => $_SESSION['role'] ?? ''
    ]);
}

if ($method === 'POST' && $action === 'change_password') {
    checkAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    $old_password = trim($data['old_password'] ?? '');
    $new_password = trim($data['new_password'] ?? '');
    
    if (empty($old_password)) {
        jsonResponse(['error' => 'Password saat ini (lama) wajib diisi.'], 400);
    }
    if (empty($new_password)) {
        jsonResponse(['error' => 'Password baru wajib diisi.'], 400);
    }
    if (strlen($new_password) < 3) {
        jsonResponse(['error' => 'Password baru minimal 3 karakter.'], 400);
    }
    
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['error' => 'Pengguna tidak ditemukan.'], 404);
    }
    
    if (!password_verify($old_password, $user['password'])) {
        jsonResponse(['error' => 'Password saat ini (lama) yang Anda masukkan salah.'], 400);
    }
    
    $hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmtUpdate = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmtUpdate->execute([$hash, $_SESSION['user_id']]);
    
    logActivity($pdo, 'CHANGE_PASSWORD', "User '{$_SESSION['username']}' berhasil mengubah password akunnya");

    jsonResponse([
        'success' => true,
        'message' => 'Password Anda berhasil diperbarui! Gunakan password baru ini saat login selanjutnya.'
    ]);
}

jsonResponse(['error' => 'Endpoint tidak ditemukan'], 404);
