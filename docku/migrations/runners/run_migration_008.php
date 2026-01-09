<?php
require_once __DIR__ . '/config/database.php';

// Simple migration runner for 008
$sqlFile = __DIR__ . '/migrations/008_add_integration_module.sql';

if (!file_exists($sqlFile)) {
    die("Migration file not found: $sqlFile");
}

$sql = file_get_contents($sqlFile);

try {
    // Assuming $conn or $pdo is defined in config/database.php
    // Let's check common names like $conn, $pdo, $pdo_conn
    
    $db = null;
    if (isset($conn) && $conn instanceof mysqli) {
        $db = $conn;
        if ($db->multi_query($sql)) {
            do {
                if ($result = $db->store_result()) {
                    $result->free();
                }
            } while ($db->more_results() && $db->next_result());
            echo "Migration 008 executed successfully via MySQLi.\n";
        } else {
            echo "Error executing migration: " . $db->error . "\n";
        }
    } elseif (isset($pdo) && $pdo instanceof PDO) {
        $db = $pdo;
        $db->exec($sql);
        echo "Migration 008 executed successfully via PDO.\n";
    } else {
        // Try global connection variables if not in local scope
        global $conn, $pdo;
        if (isset($conn)) {
             $db = $conn;
             $db->multi_query($sql);
             echo "Migration 008 executed successfully via Global MySQLi.\n";
        } elseif (isset($pdo)) {
             $db = $pdo;
             $db->exec($sql);
             echo "Migration 008 executed successfully via Global PDO.\n";
        } else {
            die("Database connection variable not found ($conn or $pdo). Check config/database.php");
        }
    }
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
