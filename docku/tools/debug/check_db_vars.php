<?php
require_once 'config/database.php';

echo "PDO defined: " . (isset($pdo) ? 'YES' : 'NO') . "\n";
echo "MySQLi defined: " . (isset($conn) ? 'YES' : 'NO') . "\n";
?>
