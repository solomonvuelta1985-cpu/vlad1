<?php
/**
 * Migration Runner - Add 'pending_print' Payment Status
 * Run once to add pending_print status to payments table
 */

require_once 'includes/config.php';

try {
    $pdo = getPDO();

    echo "Running migration: Add 'pending_print' status to payments table\n";
    echo "================================================================\n\n";

    // Add pending_print and voided to payment status enum
    $sql = "ALTER TABLE payments
            MODIFY COLUMN status ENUM('completed', 'pending', 'pending_print', 'failed', 'refunded', 'cancelled', 'voided')
            DEFAULT 'completed'
            COMMENT 'Payment status'";

    $pdo->exec($sql);
    echo "✓ Payment status enum updated successfully\n\n";

    // Verify the change
    $stmt = $pdo->query("SHOW COLUMNS FROM payments LIKE 'status'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Current payment status enum values:\n";
    echo $result['Type'] . "\n\n";

    echo "✓ Migration completed successfully!\n";
    echo "\nStatus values explanation:\n";
    echo "- completed     : Payment finalized, receipt printed successfully\n";
    echo "- pending       : Payment not yet processed\n";
    echo "- pending_print : Payment recorded, waiting for print confirmation\n";
    echo "- failed        : Payment processing failed\n";
    echo "- refunded      : Payment was refunded\n";
    echo "- cancelled     : Payment was cancelled\n";
    echo "- voided        : Payment voided due to printer issues or errors\n";

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
