<?php
require_once 'includes/config.php';

try {
    $pdo = getPDO();
    echo "✓ Database connection: OK\n\n";

    // Check if payments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
    $result = $stmt->fetch();
    echo ($result ? "✓" : "✗") . " Payments table: " . ($result ? "EXISTS" : "NOT FOUND") . "\n";

    if ($result) {
        // Check payment count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments");
        $count = $stmt->fetch();
        echo "→ Payment records: " . $count['count'] . "\n\n";

        // Check table structure
        echo "Table structure:\n";
        $stmt = $pdo->query("DESCRIBE payments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        echo "\n";

        // Get sample payment
        if ($count['count'] > 0) {
            echo "Sample payment:\n";
            $stmt = $pdo->query("SELECT * FROM payments LIMIT 1");
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            print_r($payment);
        }
    }

    // Check receipts table
    $stmt = $pdo->query("SHOW TABLES LIKE 'receipts'");
    $result = $stmt->fetch();
    echo "\n" . ($result ? "✓" : "✗") . " Receipts table: " . ($result ? "EXISTS" : "NOT FOUND") . "\n";

    // Check receipt_sequence table
    $stmt = $pdo->query("SHOW TABLES LIKE 'receipt_sequence'");
    $result = $stmt->fetch();
    echo ($result ? "✓" : "✗") . " Receipt sequence table: " . ($result ? "EXISTS" : "NOT FOUND") . "\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
