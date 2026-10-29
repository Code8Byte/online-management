<?php
require_once 'config.php';
checkRole(['admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(5, min(100, (int)$_GET['limit'])) : 25;
    $offset = ($page - 1) * $limit;

    $where = ["1=1"];
    $params = [];

    if (!empty($_GET['start_date'])) {
        $where[] = "DATE(created_at) >= ?";
        $params[] = $_GET['start_date'];
    }
    if (!empty($_GET['end_date'])) {
        $where[] = "DATE(created_at) <= ?";
        $params[] = $_GET['end_date'];
    }
    if (!empty($_GET['username'])) {
        $where[] = "username = ?";
        $params[] = trim($_GET['username']);
    }
    if (!empty($_GET['role'])) {
        $where[] = "role = ?";
        $params[] = trim($_GET['role']);
    }
    if (!empty($_GET['action'])) {
        $where[] = "action = ?";
        $params[] = trim($_GET['action']);
    }
    if (!empty($_GET['q'])) {
        $q = "%" . trim($_GET['q']) . "%";
        $where[] = "(keterangan LIKE ? OR username LIKE ? OR action LIKE ? OR ip_address LIKE ?)";
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
        $params[] = $q;
    }

    $whereSql = implode(" AND ", $where);

    // Total count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM activity_log WHERE $whereSql");
    $stmtCount->execute($params);
    $total = (int)$stmtCount->fetchColumn();

    // Data rows
    $sql = "SELECT id, user_id, username, role, action, keterangan, ip_address, user_agent, created_at 
            FROM activity_log 
            WHERE $whereSql 
            ORDER BY created_at DESC, id DESC 
            LIMIT $limit OFFSET $offset";
    $stmtData = $pdo->prepare($sql);
    $stmtData->execute($params);
    $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    // Format timestamps for display
    foreach ($data as &$row) {
        $ts = strtotime($row['created_at']);
        $row['formatted_time'] = date('H:i:s', $ts) . ' WIB';
        $row['formatted_date'] = date('d/m/Y', $ts);
        $row['formatted_datetime'] = date('d/m/Y H:i:s', $ts) . ' WIB';
    }
    unset($row);

    // Summary counters for today
    $today = date('Y-m-d');
    $summary = [
        'total_today' => 0,
        'isi_today' => 0,
        'edit_today' => 0,
        'export_today' => 0
    ];
    try {
        $stmtSum = $pdo->query("
            SELECT 
                COUNT(*) as total_today,
                SUM(CASE WHEN action = 'ISI_LKH' THEN 1 ELSE 0 END) as isi_today,
                SUM(CASE WHEN action = 'EDIT_LKH' THEN 1 ELSE 0 END) as edit_today,
                SUM(CASE WHEN action LIKE 'EXPORT%' THEN 1 ELSE 0 END) as export_today
            FROM activity_log
            WHERE DATE(created_at) = CURDATE()
        ");
        $sumRow = $stmtSum->fetch(PDO::FETCH_ASSOC);
        if ($sumRow) {
            $summary['total_today'] = (int)($sumRow['total_today'] ?? 0);
            $summary['isi_today'] = (int)($sumRow['isi_today'] ?? 0);
            $summary['edit_today'] = (int)($sumRow['edit_today'] ?? 0);
            $summary['export_today'] = (int)($sumRow['export_today'] ?? 0);
        }
    } catch (Exception $e) {}

    // Distinct list of users for filter dropdown
    $usersList = [];
    try {
        $stmtUsers = $pdo->query("SELECT DISTINCT username, role FROM activity_log ORDER BY username ASC");
        $usersList = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    jsonResponse([
        'total' => $total,
        'data' => $data,
        'page' => $page,
        'limit' => $limit,
        'summary' => $summary,
        'users' => $usersList
    ]);
}

jsonResponse(['error' => 'Method tidak diizinkan'], 405);
