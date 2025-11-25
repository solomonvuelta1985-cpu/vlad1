<?php
/**
 * Migration Runner - Add Cashier Role
 * Run once to add cashier role to database
 */

require_once 'includes/config.php';

try {
    $pdo = getPDO();

    echo "Running migration: Add 'cashier' role to users table\n";
    echo "==================================================\n\n";

    // Add cashier to role enum
    $sql = "ALTER TABLE users
            MODIFY COLUMN role ENUM('user', 'admin', 'enforcer', 'cashier')
            NOT NULL DEFAULT 'user'
            COMMENT 'User role: user=read-only, enforcer=field officer, cashier=payment processor, admin=full access'";

    $pdo->exec($sql);
    echo "✓ Cashier role added to enum successfully\n\n";

    // Verify the change
    $stmt = $pdo->query("SELECT COLUMN_TYPE
                         FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_SCHEMA = 'traffic_system'
                           AND TABLE_NAME = 'users'
                           AND COLUMN_NAME = 'role'");

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current role enum values:\n";
    echo $result['COLUMN_TYPE'] . "\n\n";

    echo "✓ Migration completed successfully!\n";
    echo "\nYou can now create cashier users with:\n";
    echo "INSERT INTO users (username, password_hash, full_name, email, role)\n";
    echo "VALUES ('cashier1', '[hash]', 'Cashier Name', 'cashier@example.com', 'cashier');\n";

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
