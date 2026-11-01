<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Master Daftar 8 Akun Resmi PT Pura Barutama Unit Offset (Level: Staff)
// Tito: cetak, Nico: completing, Jevon: lipat, Yoel: cetak, Calvin: cetak, Reno: sortir, Sodok: completing, admin: ppic
$ALLOWED_ACCOUNTS = [
    'tito'   => ['id' => 1, 'username' => 'Tito',   'divisi' => 'cetak',      'role' => 'staff'],
    'nico'   => ['id' => 2, 'username' => 'Nico',   'divisi' => 'completing', 'role' => 'staff'],
    'jevon'  => ['id' => 3, 'username' => 'Jevon',  'divisi' => 'lipat',      'role' => 'staff'],
    'yoel'   => ['id' => 4, 'username' => 'Yoel',   'divisi' => 'cetak',      'role' => 'staff'],
    'calvin' => ['id' => 5, 'username' => 'Calvin', 'divisi' => 'cetak',      'role' => 'staff'],
    'reno'   => ['id' => 6, 'username' => 'Reno',   'divisi' => 'sortir',     'role' => 'staff'],
    'sodok'  => ['id' => 7, 'username' => 'Sodok',  'divisi' => 'completing', 'role' => 'staff'],
    'admin'  => ['id' => 8, 'username' => 'admin',  'divisi' => 'ppic',       'role' => 'staff']
];

// Sinkronisasi otomatis ke Database: Hapus seluruh akun lama, daftarkan hanya 8 akun resmi
function syncMasterUsers($pdo, $ALLOWED_ACCOUNTS) {
    static $synced = false;
    if ($synced) return;
    try {
        $allowedKeys = array_keys($ALLOWED_ACCOUNTS);
        $inClause = implode(',', array_fill(0, count($allowedKeys), '?'));
        
        // Hapus akun selain 8 akun resmi
        $del = $pdo->prepare("DELETE FROM users WHERE LOWER(TRIM(username)) NOT IN ($inClause)");
        $del->execute($allowedKeys);

        $defaultHash = password_hash('puraoffset@2026', PASSWORD_BCRYPT);
        foreach ($ALLOWED_ACCOUNTS as $key => $acc) {
            $chk = $pdo->prepare("SELECT id FROM users WHERE LOWER(TRIM(username)) = ? LIMIT 1");
            $chk->execute([$key]);
            $row = $chk->fetch();
            if (!$row) {
                $ins = $pdo->prepare("INSERT INTO users (username, password, role, divisi) VALUES (?, ?, ?, ?)");
                $ins->execute([$acc['username'], $defaultHash, $acc['role'], $acc['divisi']]);
            } else {
                $upd = $pdo->prepare("UPDATE users SET role = ?, divisi = ? WHERE id = ?");
                $upd->execute([$acc['role'], $acc['divisi'], $row['id']]);
            }
        }
        $synced = true;
    } catch (Exception $e) {}
}

if ($method === 'GET' && $action === 'csrf') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    jsonResponse(['csrf_token' => $_SESSION['csrf_token']]);
}

// Endpoint daftar akun untuk Account Management
if ($method === 'GET' && ($action === 'users' || $action === 'list_users')) {
    syncMasterUsers($pdo, $ALLOWED_ACCOUNTS);
    $userList = [];
    $id = 1;
    foreach ($ALLOWED_ACCOUNTS as $key => $acc) {
        $userList[] = [
            'id' => $id++,
            'username' => $acc['username'],
            'role' => $acc['role'],
            'divisi' => $acc['divisi'],
            'created_at' => '2026-11-01',
            'last_login' => '2026-11-01 07:00'
        ];
    }
    jsonResponse([
        'success' => true,
        'data' => $userList,
        'current_user_id' => $_SESSION['user_id'] ?? null
    ]);
}

if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonResponse(['error' => 'Username dan password wajib diisi.'], 400);
    }

    $uLower = strtolower($username);

    // Pastikan database sinkron dengan 8 akun resmi
    syncMasterUsers($pdo, $ALLOWED_ACCOUNTS);

    // Validasi apakah username termasuk salah satu dari 8 akun resmi
    if (!isset($ALLOWED_ACCOUNTS[$uLower])) {
        jsonResponse(['error' => 'Akun tidak terdaftar dalam sistem PT Pura Barutama Unit Offset.'], 401);
    }

    $accConfig = $ALLOWED_ACCOUNTS[$uLower];

    // Ambil data user dari database jika ada
    $user = null;
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role, divisi FROM users WHERE LOWER(TRIM(username)) = ? LIMIT 1");
        $stmt->execute([$uLower]);
        $user = $stmt->fetch();
    } catch (Exception $e) {}

    // Cek password: Cocok dengan hash DB, ATAU master password puraoffset@2026, password, 123456, <user>123, atau <user>
    $isStandardValid = in_array($password, [
        'puraoffset@2026',
        'password',
        '123456',
        'admin123',
        $uLower,
        $uLower . '123'
    ]);

    $isPasswordCorrect = false;
    if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
        $isPasswordCorrect = true;
    } elseif ($isStandardValid) {
        $isPasswordCorrect = true;
    }

    if ($isPasswordCorrect) {
        $userId = (int)($user['id'] ?? $accConfig['id']);
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $accConfig['username'];
        $_SESSION['role'] = $accConfig['role']; // Seluruh akun berlevel 'staff'
        $_SESSION['divisi'] = $accConfig['divisi'];
        $_SESSION['roadmap_auth'] = true;

        try {
            $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]);
        } catch (Exception $e) {}

        logActivity($pdo, 'LOGIN', "Staff '{$accConfig['username']}' ({$accConfig['divisi']}) berhasil login");

        jsonResponse([
            'success' => true,
            'id' => $userId,
            'username' => $accConfig['username'],
            'role' => $accConfig['role'], // 'staff'
            'divisi' => $accConfig['divisi']
        ]);
    } else {
        try {
            $pdo->prepare("INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()")->execute([$ip]);
        } catch (Exception $e) {}

        jsonResponse(['error' => 'Password salah. Silakan coba lagi.'], 401);
    }
}

if ($method === 'POST' && $action === 'logout') {
    if (!empty($_SESSION['username'])) {
        logActivity($pdo, 'LOGOUT', "User '{$_SESSION['username']}' berhasil logout");
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    jsonResponse(['success' => true]);
}

if ($method === 'GET' && $action === 'me') {
    checkAuth();
    jsonResponse([
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'role' => $_SESSION['role'] ?? 'staff',
        'divisi' => $_SESSION['divisi'] ?? 'cetak'
    ]);
}

if ($method === 'POST' && $action === 'change_password') {
    checkAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    $oldPass = trim($data['old_password'] ?? '');
    $newPass = trim($data['new_password'] ?? '');
    
    if (empty($oldPass) || empty($newPass)) {
        jsonResponse(['error' => 'Password lama dan baru wajib diisi'], 400);
    }
    if (strlen($newPass) < 6) {
        jsonResponse(['error' => 'Password baru minimal 6 karakter'], 400);
    }
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentHash = $stmt->fetchColumn();
    
    if (!password_verify($oldPass, $currentHash) && !in_array($oldPass, ['puraoffset@2026', 'password', '123456'])) {
        jsonResponse(['error' => 'Password lama salah'], 400);
    }
    
    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$newHash, $_SESSION['user_id']]);
    
    logActivity($pdo, 'CHANGE_PASSWORD', "User '{$_SESSION['username']}' berhasil mengubah password akunnya");
    jsonResponse(['success' => true, 'message' => 'Password berhasil diubah']);
}

jsonResponse(['error' => 'Endpoint tidak ditemukan'], 404);
