<?php
/**
 * Citation Delete API Endpoint
 *
 * Deletes a citation and all related records
 * Only admins can delete citations
 *
 * @package TrafficCitationSystem
 * @subpackage API
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Require admin access
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin access required']);
    exit;
}

// Validate CSRF
if (!isset($_POST['csrf_token']) || !verify_token($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Security token validation failed']);
    exit;
}

// Rate limiting
if (!check_rate_limit('citation_delete', 10, 300)) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Too many requests. Please wait.']);
    exit;
}

try {
    // Validate required field
    if (empty($_POST['citation_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Citation ID is required']);
        exit;
    }

    $citation_id = (int)$_POST['citation_id'];
    $pdo = getPDO();

    // Get citation info for logging
    $stmt = $pdo->prepare("SELECT ticket_number, first_name, last_name FROM citations WHERE citation_id = ?");
    $stmt->execute([$citation_id]);
    $citation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$citation) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Citation not found']);
        exit;
    }

    // Check if citation has completed payments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE citation_id = ? AND status = 'completed'");
    $stmt->execute([$citation_id]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Cannot delete: This citation has completed payment records. Consider voiding the citation instead.'
        ]);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Delete related records (in correct order to handle foreign keys)

        // 1. Delete violation records
        $stmt = $pdo->prepare("DELETE FROM violations WHERE citation_id = ?");
        $stmt->execute([$citation_id]);

        // 2. Delete payment records (pending_print, voided, etc.)
        $stmt = $pdo->prepare("DELETE FROM payments WHERE citation_id = ?");
        $stmt->execute([$citation_id]);

        // 3. Delete status history if exists
        $stmt = $pdo->prepare("DELETE FROM citation_status_history WHERE citation_id = ?");
        $stmt->execute([$citation_id]);

        // 4. Delete the citation
        $stmt = $pdo->prepare("DELETE FROM citations WHERE citation_id = ?");
        $stmt->execute([$citation_id]);

        // Commit transaction
        $pdo->commit();

        // Log the deletion
        error_log("Citation deleted: ID={$citation_id}, Ticket={$citation['ticket_number']}, Name={$citation['first_name']} {$citation['last_name']}, By User ID=" . $_SESSION['user_id']);

        echo json_encode([
            'status' => 'success',
            'message' => 'Citation deleted successfully!'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Citation delete error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}
