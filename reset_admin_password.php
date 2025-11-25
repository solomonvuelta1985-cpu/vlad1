<?php
/**
 * Admin Password Reset Script
 * Run this once to reset the admin password, then DELETE this file!
 */

require_once 'includes/config.php';

// New password (change this to whatever you want)
$newPassword = 'admin123';

try {
    $pdo = getPDO();

    // Hash the new password
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update admin password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$passwordHash]);

    echo "✅ Admin password has been reset!\n\n";
    echo "Login credentials:\n";
    echo "==================\n";
    echo "Username: admin\n";
    echo "Password: $newPassword\n\n";
    echo "⚠️  IMPORTANT: Delete this file immediately for security!\n";
    echo "   Run: del " . __FILE__ . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
