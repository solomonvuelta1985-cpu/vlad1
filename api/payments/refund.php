<?php
/**
 * Refund Payment API Endpoint
 *
 * Processes payment refunds and reverts citation status
 *
 * @package TrafficCitationSystem
 * @subpackage API
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/PaymentService.php';

// Set JSON header
header('Content-Type: application/json');

// Require authentication
require_login();

// Require admin privileges only
if (!is_admin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only administrators can process refunds.'
    ]);
    exit;
}

// Only POST requests allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

try {
    // Get POST data
    $paymentId = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
    $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
    $userId = $_SESSION['user_id'];

    // Validate required fields
    if (!$paymentId || !$reason) {
        throw new Exception('Missing required fields: payment_id and reason are required');
    }

    // Validate reason length
    if (strlen(trim($reason)) < 10) {
        throw new Exception('Refund reason must be at least 10 characters long');
    }

    // Initialize PaymentService
    $paymentService = new PaymentService(getPDO());

    // Process refund
    $result = $paymentService->refundPayment($paymentId, $reason, $userId);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
