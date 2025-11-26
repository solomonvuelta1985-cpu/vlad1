<?php
/**
 * Process Payment Page
 *
 * Displays pending/unpaid citations with payment processing capability
 * Cashiers can process payments with cash/change calculator
 *
 * @package TrafficCitationSystem
 * @subpackage Public
 */

session_start();

// Define root path
define('ROOT_PATH', dirname(__DIR__));

// Include dependencies
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';

// Require authentication
require_login();

// Require cashier or admin privileges
if (!can_process_payment()) {
    set_flash('Access denied. Only cashiers can process payments.', 'danger');
    header('Location: /vlad/public/index.php');
    exit;
}

// Page title
$pageTitle = 'Process Payments';

// Get all pending citations (excluding those with active payments)
$pdo = getPDO();

// Check if database connection failed
if ($pdo === null) {
    set_flash('Database connection failed. Please check if MySQL is running and try again.', 'danger');
    $pendingCitations = [];
    $pendingPrintCount = 0;
} else {
    $sql = "SELECT
                c.citation_id,
                c.ticket_number,
                c.apprehension_datetime,
                c.total_fine,
                c.status,
                CONCAT(c.first_name, ' ', c.last_name) as driver_name,
                c.license_number,
                c.plate_mv_engine_chassis_no as plate_number,
                c.vehicle_description,
                GROUP_CONCAT(vt.violation_type SEPARATOR ', ') as violations
            FROM citations c
            LEFT JOIN violations v ON c.citation_id = v.citation_id
            LEFT JOIN violation_types vt ON v.violation_type_id = vt.violation_type_id
            LEFT JOIN payments p ON c.citation_id = p.citation_id
                AND p.status IN ('pending_print', 'completed')
            WHERE c.status = 'pending'
            AND p.payment_id IS NULL
            GROUP BY c.citation_id
            ORDER BY c.apprehension_datetime DESC";

    $stmt = $pdo->query($sql);
    $pendingCitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get pending print count for the warning banner
    $pendingPrintCount = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending_print'")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Traffic Citation System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-size: 16px;
            background-color: #f5f7fa;
        }

        .main-content {
            margin-left: 250px;
            margin-top: 60px;
            min-height: calc(100vh - 60px);
            background-color: #f5f7fa;
            padding: 2.5rem;
            font-size: 1.05rem;
        }

        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e0e6ed;
        }

        .page-header h2 {
            font-size: 1.75rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        /* Card Styling */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            background: white;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e0e6ed;
            padding: 1.25rem 1.5rem;
            border-radius: 12px 12px 0 0 !important;
        }

        .card-header h5 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Table styling */
        .table {
            font-size: 0.95rem;
            margin: 0;
        }

        .table thead th {
            font-size: 0.875rem;
            padding: 0.875rem 0.75rem;
            font-weight: 600;
            background-color: #f8fafc;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e0e6ed;
        }

        .table tbody td {
            font-size: 0.95rem;
            padding: 1rem 0.75rem;
            vertical-align: middle;
            color: #475569;
        }

        .table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s;
        }

        .table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Buttons */
        .btn {
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-sm {
            font-size: 0.875rem;
            padding: 0.4rem 0.85rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        /* Status badges */
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
            border-radius: 6px;
            text-transform: capitalize;
        }

        /* Modal Styling - Clean & Professional */
        .modal-content {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
        }

        .modal-header {
            background: #1e293b;
            color: white;
            padding: 1.25rem 1.75rem;
            border-radius: 8px 8px 0 0;
            border: none;
        }

        .modal-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .modal-title i {
            font-size: 1rem;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.7;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 1.75rem;
            font-size: 0.9375rem;
            background: #ffffff;
        }

        /* Citation Summary Box */
        .summary-section {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.25rem;
            border: 1px solid #e2e8f0;
        }

        .summary-section h6 {
            font-size: 0.8125rem;
            font-weight: 600;
            margin-bottom: 0.875rem;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.875rem;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .summary-label {
            font-size: 0.6875rem;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-value {
            font-size: 0.875rem;
            color: #0f172a;
            font-weight: 500;
        }

        /* Amount Display - Large & Clear */
        .amount-display {
            font-size: 2rem;
            font-weight: 600;
            color: #0f172a;
            text-align: center;
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 6px;
            margin: 1.25rem 0;
            border: 1px solid #e2e8f0;
        }

        .amount-display small {
            display: block;
            font-size: 0.6875rem;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.375rem;
        }

        /* Change Display */
        .change-display {
            font-size: 2rem;
            font-weight: 600;
            color: #0f172a;
            text-align: center;
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 6px;
            margin: 1.25rem 0;
            border: 1px solid #e2e8f0;
        }

        .change-display small {
            display: block;
            font-size: 0.6875rem;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.375rem;
        }

        /* Form Elements */
        .form-label {
            font-size: 0.8125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #334155;
        }

        .form-label i {
            font-size: 0.75rem;
            color: #64748b;
            margin-right: 0.375rem;
        }

        .form-control, .form-select {
            font-size: 0.875rem;
            padding: 0.625rem 0.875rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            transition: all 0.15s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #475569;
            box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.1);
            outline: none;
        }

        textarea.form-control {
            resize: vertical;
        }

        .form-text {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.375rem;
        }

        .modal-footer {
            padding: 1rem 1.75rem;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            border-radius: 0 0 8px 8px;
            gap: 0.625rem;
        }

        .modal-footer .btn {
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            font-size: 0.875rem;
        }

        /* Alerts */
        .alert {
            font-size: 0.95rem;
            padding: 1rem 1.25rem;
            border-radius: 10px;
            border: none;
        }

        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        /* Modern Print Preview Modal */
        .modern-modal {
            border: none;
            border-radius: 16px !important;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        /* Gradient Header */
        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 1.5rem 2rem;
            color: white;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .header-text small {
            display: block;
            font-size: 0.8rem;
            opacity: 0.9;
        }

        /* Modern Loading State */
        .receipt-loading-modern {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 500px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .loading-content {
            text-align: center;
            padding: 2rem;
        }

        /* Spinner Animation */
        .spinner-modern {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }

        .spinner-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
        }

        .spinner-ring:nth-child(2) {
            width: 80%;
            height: 80%;
            top: 10%;
            left: 10%;
            border-top-color: #764ba2;
            animation-delay: 0.2s;
        }

        .spinner-ring:nth-child(3) {
            width: 60%;
            height: 60%;
            top: 20%;
            left: 20%;
            border-top-color: #f093fb;
            animation-delay: 0.4s;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            color: #667eea;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 0.5; transform: translate(-50%, -50%) scale(0.9); }
        }

        .loading-dots {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out;
        }

        .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
        .loading-dots span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        /* Preview Wrapper */
        .receipt-preview-wrapper {
            background: #f8fafc;
            min-height: 500px;
        }

        .preview-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: white;
            border-bottom: 2px solid #e2e8f0;
        }

        .toolbar-info {
            display: flex;
            align-items: center;
            font-weight: 500;
            color: #475569;
            font-size: 0.95rem;
        }

        .toolbar-actions {
            display: flex;
            gap: 0.5rem;
        }

        .receipt-container {
            padding: 2rem;
            display: flex;
            justify-content: center;
            background: #e2e8f0;
        }

        .receipt-iframe {
            width: 100%;
            max-width: 800px;
            min-height: 600px;
            border: none;
            background: white;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
        }

        /* Modern Footer */
        .modern-footer {
            background: linear-gradient(to top, #f8fafc 0%, #ffffff 100%);
            border-top: 2px solid #e2e8f0;
            padding: 1.5rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .footer-instructions {
            background: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 1rem 1.5rem;
            border-radius: 8px;
        }

        .instruction-steps {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
        }

        .step-number {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .step-text {
            color: #475569;
            font-weight: 500;
        }

        .footer-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .footer-actions .btn-lg {
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 10px;
            min-width: 160px;
        }

        .footer-actions .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .footer-actions .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .instruction-steps {
                flex-direction: column;
                gap: 1rem;
            }

            .footer-actions {
                flex-direction: column;
            }

            .footer-actions .btn-lg {
                width: 100%;
            }

            .receipt-container {
                padding: 1rem;
            }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                margin-top: 70px;
                padding: 1.5rem;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .amount-display,
            .change-display {
                font-size: 2rem;
            }

            .modal-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h2>
                    <i class="fas fa-cash-register"></i> Process Payments
                </h2>
            </div>

            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show">
                    <?php
                    echo $_SESSION['flash_message'];
                    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php
            // Check if there are pending_print payments
            if ($pendingPrintCount > 0):
            ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Notice:</strong> You have <strong><?= $pendingPrintCount ?></strong> payment(s) waiting for print confirmation.
                    <a href="/vlad/public/pending_print_payments.php" class="alert-link">Click here to review them</a>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Pending Citations Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Pending Citations (<?= count($pendingCitations) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingCitations)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> No pending citations found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Ticket Number</th>
                                        <th>Driver</th>
                                        <th>License</th>
                                        <th>Vehicle</th>
                                        <th>Violation</th>
                                        <th>Date</th>
                                        <th>Amount Due</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingCitations as $citation): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($citation['ticket_number']) ?></strong>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($citation['driver_name']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($citation['license_number'] ?: 'N/A') ?></td>
                                            <td>
                                                <?= htmlspecialchars($citation['plate_number'] ?: 'N/A') ?><br>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($citation['vehicle_description'] ?: '') ?>
                                                </small>
                                            </td>
                                            <td><?= htmlspecialchars($citation['violations'] ?: 'N/A') ?></td>
                                            <td><?= date('M d, Y', strtotime($citation['apprehension_datetime'])) ?></td>
                                            <td>
                                                <strong class="text-success">
                                                    ₱<?= number_format($citation['total_fine'], 2) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge status-badge bg-warning">
                                                    <?= ucfirst($citation['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button
                                                    class="btn btn-sm btn-primary"
                                                    onclick="openPaymentModal(<?= htmlspecialchars(json_encode($citation)) ?>)"
                                                >
                                                    <i class="fas fa-money-bill-wave"></i> Process Payment
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Processing Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">
                        <i class="fas fa-cash-register"></i> Process Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="paymentForm">
                    <div class="modal-body">
                        <input type="hidden" id="citation_id" name="citation_id">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <!-- Citation Summary -->
                        <div class="summary-section">
                            <h6><i class="fas fa-ticket-alt"></i> Citation Details</h6>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span class="summary-label">Ticket Number</span>
                                    <span class="summary-value" id="modal_ticket_number"></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Date</span>
                                    <span class="summary-value" id="modal_date"></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Driver Name</span>
                                    <span class="summary-value" id="modal_driver_name"></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">License Number</span>
                                    <span class="summary-value" id="modal_license"></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Vehicle</span>
                                    <span class="summary-value" id="modal_vehicle"></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Violation(s)</span>
                                    <span class="summary-value" id="modal_violation"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Amount Due -->
                        <div class="amount-display">
                            <small>Amount Due</small><br>
                            ₱<span id="modal_amount">0.00</span>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">
                                <i class="fas fa-credit-card"></i> Payment Method *
                            </label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="cash" selected>Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="paymaya">PayMaya</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                            </select>
                        </div>

                        <!-- OR/Receipt Number (REQUIRED - Manual Entry) -->
                        <div class="mb-3">
                            <label for="receipt_number" class="form-label">
                                <i class="fas fa-receipt"></i> Official Receipt (OR) Number *
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                id="receipt_number"
                                name="receipt_number"
                                required
                                placeholder="Enter OR number from physical receipt (e.g., CGVM15320501)"
                                style="font-family: 'Courier New', monospace; font-weight: bold; font-size: 1.1rem;"
                            >
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> Enter the OR number exactly as it appears on the physical receipt booklet.
                            </div>
                        </div>

                        <!-- Cash Received (only for cash payments) -->
                        <div id="cashFields">
                            <div class="mb-3">
                                <label for="cash_received" class="form-label">
                                    <i class="fas fa-money-bill"></i> Cash Received *
                                </label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="cash_received"
                                    name="cash_received"
                                    step="0.01"
                                    min="0"
                                    placeholder="Enter amount received"
                                >
                            </div>

                            <!-- Change Display -->
                            <div class="change-display" id="changeDisplay" style="display: none;">
                                <small>Change</small><br>
                                ₱<span id="change_amount">0.00</span>
                            </div>
                        </div>

                        <!-- Reference Number (for non-cash payments) -->
                        <div id="referenceField" style="display: none;">
                            <div class="mb-3">
                                <label for="reference_number" class="form-label">
                                    <i class="fas fa-hashtag"></i> Reference Number
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="reference_number"
                                    name="reference_number"
                                    placeholder="Enter transaction reference number"
                                >
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note"></i> Notes (Optional)
                            </label>
                            <textarea
                                class="form-control"
                                id="notes"
                                name="notes"
                                rows="2"
                                placeholder="Add any additional notes"
                            ></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="confirmPaymentBtn">
                            <i class="fas fa-check-circle"></i> Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reprint Options Modal -->
    <div class="modal fade" id="reprintOptionsModal" tabindex="-1" aria-labelledby="reprintOptionsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);">
                    <h5 class="modal-title" id="reprintOptionsModalLabel">
                        <i class="fas fa-exclamation-triangle"></i> Printer Problem
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reprint_payment_id">
                    <input type="hidden" id="reprint_current_or">

                    <p class="mb-3">
                        <strong>What would you like to do?</strong>
                    </p>

                    <div class="d-grid gap-3">
                        <!-- Option 1: Reprint -->
                        <button class="btn btn-primary btn-lg" onclick="reprintReceipt()">
                            <i class="fas fa-redo"></i> REPRINT
                            <div class="small">Use same OR: <span id="display_current_or"></span></div>
                        </button>

                        <!-- Option 2: Use New Receipt -->
                        <button class="btn btn-warning btn-lg" onclick="showNewOrInput()">
                            <i class="fas fa-edit"></i> USE NEW RECEIPT
                            <div class="small">Enter new OR number</div>
                        </button>

                        <!-- New OR Input (hidden by default) -->
                        <div id="newOrInputSection" style="display: none;">
                            <div class="mb-3">
                                <label for="new_or_input" class="form-label">New OR Number</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="new_or_input"
                                    placeholder="Enter new OR number"
                                    style="font-family: 'Courier New', monospace; font-weight: bold;"
                                >
                            </div>
                            <button class="btn btn-success w-100" onclick="confirmNewOr()">
                                <i class="fas fa-check"></i> Confirm New OR
                            </button>
                        </div>

                        <!-- Option 3: Cancel Payment -->
                        <button class="btn btn-danger btn-lg" onclick="voidPaymentConfirm()">
                            <i class="fas fa-times-circle"></i> CANCEL PAYMENT
                            <div class="small">Void this transaction</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Preview Modal - Redesigned -->
    <div class="modal fade" id="printPreviewModal" tabindex="-1" aria-labelledby="printPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-lg-down modal-xl">
            <div class="modal-content modern-modal">
                <!-- Modern Header -->
                <div class="modal-header gradient-header">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="modal-title mb-0" id="printPreviewModalLabel">
                                Receipt Preview
                            </h5>
                            <small class="text-white-50">Review before printing</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Loading State -->
                <div class="modal-body p-0 position-relative">
                    <div class="receipt-loading-modern" id="receiptLoading">
                        <div class="loading-content">
                            <div class="spinner-modern">
                                <div class="spinner-ring"></div>
                                <div class="spinner-ring"></div>
                                <div class="spinner-ring"></div>
                                <i class="fas fa-file-invoice spinner-icon"></i>
                            </div>
                            <h5 class="mt-4 mb-2">Generating Receipt Preview</h5>
                            <p class="text-muted">Please wait while we prepare your receipt...</p>
                            <div class="loading-dots">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Receipt Preview Content -->
                    <div class="receipt-preview-wrapper" id="receiptPreviewContent" style="display: none;">
                        <div class="preview-toolbar">
                            <div class="toolbar-info">
                                <i class="fas fa-eye text-primary"></i>
                                <span class="ms-2">Preview Mode</span>
                            </div>
                            <div class="toolbar-actions">
                                <button class="btn btn-sm btn-outline-secondary" onclick="zoomReceipt('out')">
                                    <i class="fas fa-search-minus"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="zoomReceipt('reset')">
                                    <i class="fas fa-compress"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="zoomReceipt('in')">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="receipt-container">
                            <iframe id="receiptIframe" class="receipt-iframe"></iframe>
                        </div>
                    </div>
                </div>

                <!-- Modern Footer with Instructions -->
                <div class="modal-footer modern-footer">
                    <div class="footer-instructions">
                        <div class="instruction-steps">
                            <div class="step">
                                <span class="step-number">1</span>
                                <span class="step-text">Review receipt details above</span>
                            </div>
                            <div class="step">
                                <span class="step-number">2</span>
                                <span class="step-text">Click "Print Receipt" button</span>
                            </div>
                            <div class="step">
                                <span class="step-number">3</span>
                                <span class="step-text">Confirm print was successful</span>
                            </div>
                        </div>
                    </div>
                    <div class="footer-actions">
                        <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-success btn-lg" onclick="printReceiptFromPreview()">
                            <i class="fas fa-print"></i> Print Receipt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <script src="../assets/js/process_payment.js"></script>
</body>
</html>
