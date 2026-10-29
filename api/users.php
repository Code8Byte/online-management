<?php
require_once 'config.php';

// Endpoint khusus manajemen akun - Hanya Administrator yang memiliki hak akses
checkRole(['admin']);

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// 1. GET: Ambil daftar seluruh akun pengguna
if ($method === 'GET' || ($method === 'POST' && $action === 'list')) {
    try {
        $stmt = $pdo->query("
            SELECT 
                u.id, 
                u.username, 
                u.role, 
                u.created_at,
                (SELECT COUNT(*) FROM lkh WHERE lkh.pic = u.username OR lkh.operator = u.username) AS total_lkh,
                (SELECT MAX(created_at) FROM activity_log WHERE activity_log.username = u.username) AS last_active
            FROM users u
            ORDER BY 
                CASE u.role 
                    WHEN 'admin' THEN 1 
                    WHEN 'operator' THEN 2 
                    WHEN 'viewer' THEN 3 
                    ELSE 4 
                END ASC,
                u.created_at DESC, 
                u.id DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse([
            'success' => true,
            'data' => $users,
            'current_user_id' => (int)($_SESSION['user_id'] ?? 0)
        ]);
    } catch (PDOException $e) {
        // Fallback jika subquery activity_log atau kolom bermasalah
        $stmt = $pdo->query("
            SELECT 
                u.id, 
                u.username, 
                u.role, 
                u.created_at,
                (SELECT COUNT(*) FROM lkh WHERE lkh.pic = u.username OR lkh.operator = u.username) AS total_lkh
            FROM users u
            ORDER BY 
                CASE u.role 
                    WHEN 'admin' THEN 1 
                    WHEN 'operator' THEN 2 
                    WHEN 'viewer' THEN 3 
                    ELSE 4 
                END ASC,
                u.id ASC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse([
            'success' => true,
            'data' => $users,
            'current_user_id' => (int)($_SESSION['user_id'] ?? 0)
        ]);
    }
}

// 2. POST: Tambah Akun Baru
if ($method === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');
    $role = trim($data['role'] ?? 'operator');

    if (empty($username)) {
        jsonResponse(['error' => 'Username wajib diisi.'], 400);
    }
    if (strlen($username) < 3 || strlen($username) > 50) {
        jsonResponse(['error' => 'Username harus antara 3 sampai 50 karakter.'], 400);
    }
    if (empty($password)) {
        jsonResponse(['error' => 'Password wajib diisi.'], 400);
    }
    if (strlen($password) < 3) {
        jsonResponse(['error' => 'Password minimal harus 3 karakter.'], 400);
    }
    if (!in_array($role, ['admin', 'operator', 'viewer'])) {
        jsonResponse(['error' => 'Role tidak valid. Pilihan role: admin, operator, viewer.'], 400);
    }

    // Cek duplikasi username (case-insensitive)
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
    $stmtCheck->execute([$username]);
    if ($stmtCheck->fetch()) {
        jsonResponse(['error' => "Username '{$username}' sudah terdaftar di sistem. Silakan gunakan username lain."], 400);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$username, $hash, $role]);
    $newId = (int)$pdo->lastInsertId();

    logActivity($pdo, 'CREATE_USER', "Admin '{$_SESSION['username']}' membuat akun baru: '{$username}' dengan tingkat [{$role}]");

    jsonResponse([
        'success' => true,
        'message' => "Akun '{$username}' dengan role '{$role}' berhasil dibuat.",
        'user' => [
            'id' => $newId,
            'username' => $username,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s'),
            'total_lkh' => 0
        ]
    ]);
}

// 3. POST: Edit Nama / Username Pengguna & Sinkronkan Seluruh Data LKH
if ($method === 'POST' && ($action === 'update_name' || $action === 'update_username')) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $newUsername = trim($data['username'] ?? '');

    if ($id <= 0) {
        jsonResponse(['error' => 'ID akun pengguna tidak valid.'], 400);
    }
    if (empty($newUsername)) {
        jsonResponse(['error' => 'Nama pengguna baru wajib diisi.'], 400);
    }
    if (strlen($newUsername) < 3 || strlen($newUsername) > 50) {
        jsonResponse(['error' => 'Nama pengguna harus antara 3 sampai 50 karakter.'], 400);
    }

    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        jsonResponse(['error' => 'Akun pengguna tidak ditemukan.'], 404);
    }

    $oldUsername = $target['username'];

    if ($oldUsername === $newUsername) {
        jsonResponse([
            'success' => true,
            'message' => "Nama akun tidak berubah ('{$newUsername}').",
            'username' => $newUsername,
            'affected_lkh' => 0
        ]);
    }

    // Cek apakah username baru sudah digunakan oleh akun lain
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id != ?");
    $stmtCheck->execute([$newUsername, $id]);
    if ($stmtCheck->fetch()) {
        jsonResponse(['error' => "Nama / username '{$newUsername}' sudah digunakan oleh akun lain. Silakan pilih nama lain."], 400);
    }

    $pdo->beginTransaction();
    try {
        // 1. Update nama di tabel users
        $stmtUpUser = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmtUpUser->execute([$newUsername, $id]);

        // 2. Hitung jumlah data LKH yang akan diperbarui (berdasarkan pic atau operator)
        $stmtCountLkh = $pdo->prepare("
            SELECT COUNT(*) FROM lkh 
            WHERE pic = ? OR operator = ? OR LOWER(TRIM(pic)) = LOWER(?) OR LOWER(TRIM(operator)) = LOWER(?)
        ");
        $stmtCountLkh->execute([$oldUsername, $oldUsername, $oldUsername, $oldUsername]);
        $affectedLkh = (int)$stmtCountLkh->fetchColumn();

        // 3. Update pic di tabel lkh
        $stmtUpPic = $pdo->prepare("UPDATE lkh SET pic = ? WHERE pic = ? OR LOWER(TRIM(pic)) = LOWER(?)");
        $stmtUpPic->execute([$newUsername, $oldUsername, $oldUsername]);

        // 4. Update operator di tabel lkh
        $stmtUpOp = $pdo->prepare("UPDATE lkh SET operator = ? WHERE operator = ? OR LOWER(TRIM(operator)) = LOWER(?)");
        $stmtUpOp->execute([$newUsername, $oldUsername, $oldUsername]);

        // 5. Update di tabel activity_log
        $stmtUpLogs = $pdo->prepare("UPDATE activity_log SET username = ? WHERE username = ? OR user_id = ?");
        $stmtUpLogs->execute([$newUsername, $oldUsername, $id]);

        // 6. Jika akun yang diedit adalah akun yang sedang login saat ini, perbarui session aktif
        if ((int)$_SESSION['user_id'] === $id) {
            $_SESSION['username'] = $newUsername;
        }

        $pdo->commit();

        logActivity(
            $pdo, 
            'UPDATE_USERNAME', 
            "Admin '{$_SESSION['username']}' mengubah nama akun #{$id} dari '{$oldUsername}' menjadi '{$newUsername}'. {$affectedLkh} riwayat LKH otomatis disinkronkan."
        );

        jsonResponse([
            'success' => true,
            'message' => "Nama akun berhasil diubah dari '{$oldUsername}' menjadi '{$newUsername}'. {$affectedLkh} data LKH otomatis diperbarui mengikuti nama baru.",
            'username' => $newUsername,
            'affected_lkh' => $affectedLkh
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        jsonResponse(['error' => 'Gagal mengubah nama akun: ' . $e->getMessage()], 500);
    }
}

// 4. POST: Eskalasi / Perubahan Tingkat Hak Akses (Role)
if ($method === 'POST' && $action === 'update_role') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $newRole = trim($data['role'] ?? '');

    if ($id <= 0) {
        jsonResponse(['error' => 'ID akun pengguna tidak valid.'], 400);
    }
    if (!in_array($newRole, ['admin', 'operator', 'viewer'])) {
        jsonResponse(['error' => 'Role tujuan tidak valid. Pilihan: admin, operator, viewer.'], 400);
    }

    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        jsonResponse(['error' => 'Akun pengguna tidak ditemukan.'], 404);
    }

    $oldRole = $target['role'];
    if ($oldRole === $newRole) {
        jsonResponse(['success' => true, 'message' => "Akun '{$target['username']}' memang sudah berstatus {$newRole}."]);
    }

    // Proteksi: Jika admin mengubah role akunnya sendiri menjadi non-admin
    if ((int)$_SESSION['user_id'] === $id && $newRole !== 'admin') {
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminCount = (int)$stmtCount->fetchColumn();
        if ($adminCount <= 1) {
            jsonResponse(['error' => 'Tidak dapat mendowngrade akun Anda sendiri karena ini merupakan satu-satunya akun Administrator aktif di sistem.'], 400);
        }
        // Perbarui role di session aktif jika mengubah akun sendiri
        $_SESSION['role'] = $newRole;
    }

    $stmtUp = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmtUp->execute([$newRole, $id]);

    logActivity($pdo, 'ESCALATE_ROLE', "Admin '{$_SESSION['username']}' mengubah tingkat hak akses akun '{$target['username']}' dari [{$oldRole}] menjadi [{$newRole}]");

    jsonResponse([
        'success' => true,
        'message' => "Tingkat hak akses akun '{$target['username']}' berhasil diubah dari [{$oldRole}] menjadi [{$newRole}].",
        'role' => $newRole
    ]);
}

// 4. POST: Reset Password oleh Admin
if ($method === 'POST' && $action === 'reset_password') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $newPassword = trim($data['new_password'] ?? '');

    if ($id <= 0) {
        jsonResponse(['error' => 'ID akun pengguna tidak valid.'], 400);
    }
    if (empty($newPassword) || strlen($newPassword) < 3) {
        jsonResponse(['error' => 'Password baru minimal harus 3 karakter.'], 400);
    }

    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        jsonResponse(['error' => 'Akun pengguna tidak ditemukan.'], 404);
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmtUp = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmtUp->execute([$hash, $id]);

    logActivity($pdo, 'RESET_PASSWORD', "Admin '{$_SESSION['username']}' mereset password untuk akun '{$target['username']}'");

    jsonResponse([
        'success' => true,
        'message' => "Password baru untuk akun '{$target['username']}' berhasil diperbarui! Beritahukan password baru ini kepada pemilik akun."
    ]);
}

// 5. POST: Hapus Akun Pengguna
if ($method === 'POST' && $action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['error' => 'ID akun pengguna tidak valid.'], 400);
    }
    if ((int)$_SESSION['user_id'] === $id) {
        jsonResponse(['error' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan saat ini.'], 400);
    }

    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) {
        jsonResponse(['error' => 'Akun pengguna tidak ditemukan.'], 404);
    }

    // Proteksi: cegah penghapusan admin terakhir
    if ($target['role'] === 'admin') {
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminCount = (int)$stmtCount->fetchColumn();
        if ($adminCount <= 1) {
            jsonResponse(['error' => 'Tidak dapat menghapus akun Administrator terakhir di sistem.'], 400);
        }
    }

    $stmtDel = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmtDel->execute([$id]);

    logActivity($pdo, 'DELETE_USER', "Admin '{$_SESSION['username']}' menghapus akun '{$target['username']}' (role: {$target['role']})");

    jsonResponse([
        'success' => true,
        'message' => "Akun '{$target['username']}' berhasil dihapus dari sistem."
    ]);
}

jsonResponse(['error' => 'Endpoint tidak ditemukan.'], 404);
