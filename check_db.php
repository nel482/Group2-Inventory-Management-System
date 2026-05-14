<?php
require_once 'config/database.php';
$db = (new Database())->conn;

try {
    $result = $db->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() > 0) {
        echo "Users table exists.\n";
        $columns = $db->query("DESCRIBE users");
        echo "Columns:\n";
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ") " . ($col['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . " " . ($col['Default'] ? "DEFAULT " . $col['Default'] : '') . "\n";
        }
    } else {
        echo "Users table does not exist.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>