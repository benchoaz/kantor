<?php
// add_operator_role.php
// Script to add 'operator' to the ENUM role column
require_once 'config/database.php';

try {
    // 1. Check current column type
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    $column = $stmt->fetch();
    $type = $column['Type'];
    
    echo "Current Type: $type<br>";

    // 2. Alter Table if needed
    if (strpos($type, 'operator') === false) {
        // Append 'operator' to the ENUM list
        // Assuming current is ENUM('admin','user') -> change to ENUM('admin','user','operator')
        $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user', 'operator') DEFAULT 'user'";
        $db->exec($sql);
        echo "SUCCESS: Added 'operator' role to database schema.<br>";
    } else {
        echo "INFO: 'operator' role already exists.<br>";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
