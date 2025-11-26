<?php
/**
 * Receipt System Diagnostic Test
 * Tests if receipt system is working properly
 */

echo "<!DOCTYPE html><html><head><title>Receipt Test</title></head><body>";
echo "<h1>Receipt System Diagnostic</h1>";

// Test 1: PHP Version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Status: " . (version_compare(PHP_VERSION, '7.4.0') >= 0 ? '✅ OK' : '❌ Too old') . "<br><br>";

// Test 2: Session
echo "<h2>2. Session Test</h2>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Status: ✅ Sessions working<br><br>";

// Test 3: File Paths
echo "<h2>3. File Paths</h2>";
echo "Current file: " . __FILE__ . "<br>";
echo "Root path: " . dirname(__DIR__) . "<br>";
$configPath = __DIR__ . '/../includes/config.php';
echo "Config exists: " . (file_exists($configPath) ? '✅ Yes' : '❌ No') . "<br>";
$vendorPath = __DIR__ . '/../vendor/autoload.php';
echo "Vendor autoload exists: " . (file_exists($vendorPath) ? '✅ Yes' : '❌ No') . "<br><br>";

// Test 4: Database Connection
echo "<h2>4. Database Connection</h2>";
try {
    require_once __DIR__ . '/../includes/config.php';
    $pdo = getPDO();
    if ($pdo) {
        echo "Database connection: ✅ Connected<br>";

        // Test query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM payments LIMIT 1");
        $result = $stmt->fetch();
        echo "Payments table: ✅ Accessible<br>";
    } else {
        echo "Database connection: ❌ Failed<br>";
    }
} catch (Exception $e) {
    echo "Database error: ❌ " . htmlspecialchars($e->getMessage()) . "<br>";
}
echo "<br>";

// Test 5: TCPDF Library
echo "<h2>5. TCPDF Library</h2>";
try {
    if (file_exists($vendorPath)) {
        require_once $vendorPath;
        echo "Autoload: ✅ Loaded<br>";

        if (class_exists('TCPDF')) {
            echo "TCPDF class: ✅ Available<br>";
        } else {
            echo "TCPDF class: ❌ Not found<br>";
        }
    } else {
        echo "Vendor autoload: ❌ Not found<br>";
    }
} catch (Exception $e) {
    echo "TCPDF error: ❌ " . htmlspecialchars($e->getMessage()) . "<br>";
}
echo "<br>";

// Test 6: Receipt.php file
echo "<h2>6. Receipt.php File</h2>";
$receiptPath = __DIR__ . '/receipt.php';
echo "Receipt file exists: " . (file_exists($receiptPath) ? '✅ Yes' : '❌ No') . "<br>";
echo "Receipt file readable: " . (is_readable($receiptPath) ? '✅ Yes' : '❌ No') . "<br>";

// Test 7: Try to get a receipt
echo "<h2>7. Sample Receipt Query</h2>";
try {
    if ($pdo) {
        $stmt = $pdo->query("SELECT receipt_number FROM payments WHERE status = 'completed' LIMIT 1");
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($payment) {
            $receiptNum = $payment['receipt_number'];
            echo "Sample receipt found: <strong>" . htmlspecialchars($receiptNum) . "</strong><br>";
            echo "Test URL: <a href='receipt.php?receipt=" . urlencode($receiptNum) . "' target='_blank'>Click to test</a><br>";
        } else {
            echo "No completed payments found in database<br>";
        }
    }
} catch (Exception $e) {
    echo "Query error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><hr>";
echo "<h2>Summary</h2>";
echo "<p>If all tests show ✅, the receipt system should work properly.</p>";
echo "<p>If you see ❌, fix those issues first.</p>";

echo "</body></html>";
?>
