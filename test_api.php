<?php
/**
 * Test API endpoint directly
 */

// Simulate session
session_start();
$_SESSION['user_id'] = 1; // Simulate logged-in user
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';

// Simulate GET request
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture output
ob_start();

// Include the API file
require_once 'api/payment_list.php';

// Get the output
$output = ob_get_clean();

// Display raw output
echo "API Response:\n";
echo "=============\n";
echo $output;
echo "\n\n";

// Try to decode as JSON
echo "Decoded JSON:\n";
echo "=============\n";
$data = json_decode($output, true);
if ($data) {
    print_r($data);
} else {
    echo "Failed to decode JSON\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}
