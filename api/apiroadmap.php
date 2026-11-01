<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Pastikan tabel roadmap_tasks tersedia dan terisi seluruh task ID
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS roadmap_tasks (
        task_id VARCHAR(50) PRIMARY KEY,
        status VARCHAR(50) NOT NULL DEFAULT 'Belum mulai',
        updated_by VARCHAR(50) DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $allTasks = ['t1','t2','t3','t4','t5','t6','t7','t8','t9','t10','t11','t12','t13','t14','t15','t16','t17','t17b','t17c','t18','t19','t20','t21','t22','t23','t24','t25','t26','t27','t28','t29','t30'];
    $stmtCheck = $pdo->prepare("INSERT IGNORE INTO roadmap_tasks (task_id, status, updated_by, updated_at) VALUES (?, 'Belum mulai', 'system', NOW())");
    foreach ($allTasks as $tid) {
        $stmtCheck->execute([$tid]);
    }
} catch (Exception $e) {}

$action = $_GET['action'] ?? 'status';
$method = $_SERVER['REQUEST_METHOD'];

// Helper: Cek apakah user berhak mengedit roadmap
function isRoadmapEditor($pdo = null, $data = []) {
    if (!empty($_SESSION['roadmap_auth']) || !empty($_SESSION['user_id']) || !empty($_SESSION['username'])) {
        return true;
    }
    // Cek auth_user dari parameter atau header
    $authUser = $data['auth_user'] ?? ($_SERVER['HTTP_X_AUTH_USER'] ?? '');
    if (!empty($authUser) && $pdo) {
        try {
            $stmtU = $pdo->prepare("SELECT id, username, role, divisi FROM users WHERE LOWER(username) = LOWER(?)");
            $stmtU->execute([$authUser]);
            $u = $stmtU->fetch(PDO::FETCH_ASSOC);
            if ($u) {
                $_SESSION['roadmap_auth'] = true;
                $_SESSION['user_id'] = (int)$u['id'];
                $_SESSION['username'] = $u['username'];
                $_SESSION['role'] = $u['role'];
                $_SESSION['divisi'] = $u['divisi'] ?? $u['role'];
                return true;
            }
        } catch (Exception $e) {}
    }
    return false;
}

// 1. GET: Ambil status tasks roadmap dan perizinan edit langsung dari database
if ($action === 'status' && $method === 'GET') {
    $canEdit = isRoadmapEditor($pdo);
    $user = $canEdit ? [
        'id' => (int)($_SESSION['user_id'] ?? 0),
        'username' => $_SESSION['username'] ?? 'Editor Roadmap',
        'role' => $_SESSION['role'] ?? 'editor',
        'divisi' => $_SESSION['divisi'] ?? 'lintas'
    ] : null;

    $tasks = [];
    try {
        $stmt = $pdo->query("SELECT task_id, status, updated_by, updated_at FROM roadmap_tasks");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[$row['task_id']] = [
                'status' => $row['status'],
                'updated_by' => $row['updated_by'],
                'updated_at' => $row['updated_at']
            ];
        }
    } catch (Exception $e) {}

    jsonResponse([
        'success' => true,
        'is_logged_in' => $canEdit,
        'can_edit' => $canEdit,
        'user' => $user,
        'tasks' => $tasks
    ]);
}

// 2. POST: Login khusus Roadmap Editor dari database users
if ($action === 'login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($username) || empty($password)) {
        jsonResponse(['error' => 'Username dan password wajib diisi.'], 400);
    }

    // Ambil data user dari database
    $stmt = $pdo->prepare("SELECT id, username, password, role, divisi FROM users WHERE LOWER(username) = LOWER(?)");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Berikan sesi editor roadmap
        $_SESSION['roadmap_auth'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['divisi'] = $user['divisi'] ?? $user['role'];

        logActivity($pdo, 'LOGIN_ROADMAP', "User '{$user['username']}' berhasil login ke Editor Roadmap");

        jsonResponse([
            'success' => true,
            'message' => 'Login editor roadmap berhasil.',
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'divisi' => $user['divisi'] ?? $user['role']
        ]);
    } else {
        jsonResponse(['error' => 'Username atau password editor roadmap salah.'], 401);
    }
}

// 3. POST: Logout Editor Roadmap
if ($action === 'logout' && $method === 'POST') {
    $username = $_SESSION['username'] ?? 'User';
    logActivity($pdo, 'LOGOUT_ROADMAP', "User '{$username}' logout dari Editor Roadmap");
    unset($_SESSION['roadmap_auth']);
    jsonResponse(['success' => true, 'message' => 'Berhasil keluar dari mode editor roadmap.']);
}

// 4. POST: Update status task (wajib login / otentikasi)
if ($action === 'update' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $taskId = trim($data['task_id'] ?? '');
    $status = trim($data['status'] ?? '');
    $validStatuses = ['Belum mulai', 'Berjalan', 'Terkendala', 'Selesai'];

    if (empty($taskId) || !in_array($status, $validStatuses)) {
        jsonResponse(['error' => 'ID task atau nilai status tidak valid.'], 400);
    }

    // Dukung otentikasi inline jika disertakan dalam body
    if (!empty($data['username']) && !empty($data['password'])) {
        $uName = trim($data['username']);
        $pWord = trim($data['password']);
        $stmtU = $pdo->prepare("SELECT id, username, password, role, divisi FROM users WHERE LOWER(username) = LOWER(?)");
        $stmtU->execute([$uName]);
        $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($uData && password_verify($pWord, $uData['password'])) {
            $_SESSION['roadmap_auth'] = true;
            $_SESSION['user_id'] = $uData['id'];
            $_SESSION['username'] = $uData['username'];
            $_SESSION['role'] = $uData['role'];
            $_SESSION['divisi'] = $uData['divisi'] ?? $uData['role'];
        } else {
            jsonResponse(['error' => 'Username atau password editor roadmap salah.'], 401);
        }
    }

    if (!isRoadmapEditor($pdo, $data)) {
        jsonResponse(['error' => 'Akses ditolak. Silakan login ke Roadmap Editor terlebih dahulu untuk mengubah status task.'], 401);
    }

    $username = $_SESSION['username'] ?? 'Editor';

    try {
        $stmt = $pdo->prepare("INSERT INTO roadmap_tasks (task_id, status, updated_by, updated_at) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE status = VALUES(status), updated_by = VALUES(updated_by), updated_at = NOW()");
        $stmt->execute([$taskId, $status, $username]);

        logActivity($pdo, 'UPDATE_ROADMAP', "User '{$username}' mengubah status task [{$taskId}] menjadi '{$status}'");

        jsonResponse([
            'success' => true,
            'message' => 'Status task berhasil diperbarui ke database.',
            'task_id' => $taskId,
            'status' => $status,
            'updated_by' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
            'user' => [
                'username' => $username,
                'role' => $_SESSION['role'] ?? 'editor'
            ]
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Gagal memperbarui database roadmap: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['error' => 'Action tidak didukung.'], 404);
