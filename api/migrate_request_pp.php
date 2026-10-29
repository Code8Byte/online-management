<?php
require_once "config.php";
header('Content-Type: text/plain; charset=utf-8');

echo "=== Starting Migration: Support PP status 'req' ===\n";

try {
    echo "1. Checking status column in table pp...\n";
    $pdo->exec("ALTER TABLE `pp` MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'aktif'");
    echo "   -> Column status in table pp is now VARCHAR(20).\n";
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>