<?php
/**
 * Payment Processing API Endpoint
 *
 * Handles recording new payments for citations
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

// Require cashier or admin privileges to process payments
if (!can_process_payment()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only cashiers can process payments.'
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
    $citationId = filter_input(INPUT_POST, 'citation_id', FILTER_VALIDATE_INT);
    $amountPaid = filter_input(INPUT_POST, 'amount_paid', FILTER_VALIDATE_FLOAT);
    $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $collectedBy = $_SESSION['user_id'];

    // Validate required fields
    if (!$citationId || !$amountPaid || !$paymentMethod) {
        throw new Exception('Missing required fields');
    }

    // Validate payment method
    $validMethods = ['cash', 'check', 'online', 'gcash', 'paymaya', 'bank_transfer', 'money_order'];
    if (!in_array($paymentMethod, $validMethods)) {
        throw new Exception('Invalid payment method');
    }

    // Prepare additional data
    $additionalData = [];

    // Check-specific fields
    if ($paymentMethod === 'check') {
        $additionalData['check_number'] = filter_input(INPUT_POST, 'check_number', FILTER_SANITIZE_STRING);
        $additionalData['check_bank'] = filter_input(INPUT_POST, 'check_bank', FILTER_SANITIZE_STRING);
        $additionalData['check_date'] = filter_input(INPUT_POST, 'check_date', FILTER_SANITIZE_STRING);

        if (empty($additionalData['check_number'])) {
            throw new Exception('Check number is required for check payments');
        }
    }

    // Online payment reference
    if (in_array($paymentMethod, ['online', 'gcash', 'paymaya', 'bank_transfer'])) {
        $additionalData['reference_number'] = filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_STRING);
    }

    // Notes
    $additionalData['notes'] = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    // Initialize PaymentService
    $paymentService = new PaymentService(getPDO());

    // Record payment
    $result = $paymentService->recordPayment(
        $citationId,
        $amountPaid,
        $paymentMethod,
        $collectedBy,
        $additionalData
    );

    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
