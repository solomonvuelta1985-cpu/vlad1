<?php
/**
 * Diagnostic Script - Check Payment Status
 */

require_once __DIR__ . '/includes/config.php';

$pdo = getPDO();

echo "<h2>Checking Citations and Payments Status</h2>";
echo "<style>table {border-collapse: collapse;} th, td {border: 1px solid #ddd; padding: 8px;} th {background: #4CAF50; color: white;}</style>";

// Check the specific citations
$tickets = ['06105', '06104', '06103', '06102'];

foreach ($tickets as $ticket) {
    echo "<h3>Ticket: $ticket</h3>";

    // Get citation details
    $sql = "SELECT * FROM citations WHERE ticket_number = :ticket";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':ticket' => $ticket]);
    $citation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($citation) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Citation ID</td><td>{$citation['citation_id']}</td></tr>";
        echo "<tr><td>Status</td><td><strong>{$citation['status']}</strong></td></tr>";
        echo "<tr><td>Total Fine</td><td>₱" . number_format($citation['total_fine'], 2) . "</td></tr>";
        echo "<tr><td>Payment Date</td><td>{$citation['payment_date']}</td></tr>";
        echo "</table>";

        // Check for payments
        $sql = "SELECT * FROM payments WHERE citation_id = :id ORDER BY payment_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $citation['citation_id']]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($payments) {
            echo "<h4>Payments Found:</h4>";
            echo "<table>";
            echo "<tr><th>Receipt #</th><th>Amount</th><th>Method</th><th>Payment Date</th><th>Payment Status</th></tr>";
            foreach ($payments as $p) {
                echo "<tr>";
                echo "<td>{$p['receipt_number']}</td>";
                echo "<td>₱" . number_format($p['amount_paid'], 2) . "</td>";
                echo "<td>{$p['payment_method']}</td>";
                echo "<td>{$p['payment_date']}</td>";
                echo "<td><strong>{$p['status']}</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'><strong>NO PAYMENTS FOUND!</strong></p>";
        }
    } else {
        echo "<p>Citation not found!</p>";
    }

    echo "<hr>";
}

// Check audit log for status changes
echo "<h3>Audit Log - Recent Status Changes</h3>";
$sql = "SELECT * FROM audit_log WHERE table_name = 'citations' AND action = 'status_change' ORDER BY created_at DESC LIMIT 10";
$stmt = $pdo->query($sql);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($logs) {
    echo "<table>";
    echo "<tr><th>Date</th><th>Record ID</th><th>Old Values</th><th>New Values</th></tr>";
    foreach ($logs as $log) {
        echo "<tr>";
        echo "<td>{$log['created_at']}</td>";
        echo "<td>Citation #{$log['record_id']}</td>";
        echo "<td>" . htmlspecialchars($log['old_values']) . "</td>";
        echo "<td>" . htmlspecialchars($log['new_values']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>NO STATUS CHANGES IN AUDIT LOG!</strong></p>";
}
?>
