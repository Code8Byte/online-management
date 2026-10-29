<?php
require_once "config.php";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables:\n";
print_r($tables);

foreach (['lkh_downtime', 'lkh_cacat', 'lkh_pp_items'] as $t) {
    if (in_array($t, $tables)) {
        echo "\n$t:\n";
        $stmt = $pdo->query("SHOW CREATE TABLE $t");
        print_r($stmt->fetch());
    } else {
        echo "\n$t: DOES NOT EXIST\n";
    }
}
?>
