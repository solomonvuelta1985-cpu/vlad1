<?php
/**
 * Pending Print Payments Management
 *
 * Shows all payments in 'pending_print' status and allows cashier to finalize or void them
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
    set_flash('Access denied. Only cashiers can manage pending payments.', 'danger');
    header('Location: /vlad/public/index.php');
    exit;
}

// Page title
$pageTitle = 'Pending Print Payments';

// Get all pending_print payments
$pdo = getPDO();

// Check if database connection failed
if ($pdo === null) {
    // Set error message
    set_flash('Database connection failed. Please check if MySQL is running and try again.', 'danger');
    $pendingPayments = [];
} else {
    $sql = "SELECT
                p.payment_id,
                p.receipt_number,
                p.amount_paid,
                p.payment_method,
                p.payment_date,
                p.status,
                c.citation_id,
                c.ticket_number,
                c.status as citation_status,
                CONCAT(c.first_name, ' ', c.last_name) as driver_name,
                u.full_name as collected_by_name
            FROM payments p
            JOIN citations c ON p.citation_id = c.citation_id
            JOIN users u ON p.collected_by = u.user_id
            WHERE p.status = 'pending_print'
            ORDER BY p.payment_date DESC";

    $stmt = $pdo->query($sql);
    $pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        .status-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
            border-radius: 6px;
            text-transform: capitalize;
        }

        .alert {
            font-size: 0.95rem;
            padding: 1rem 1.25rem;
            border-radius: 10px;
            border: none;
        }

        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        /* Payment Age Indicators */
        .payment-age-old {
            background-color: #fee2e2 !important;
        }

        .payment-age-warning {
            background-color: #fef3c7 !important;
        }

        /* Action Buttons */
        .btn-group .btn {
            margin: 0;
        }

        .dropdown-toggle::after {
            margin-left: 0.5em;
        }

        /* Modern Modal Styles */
        .modern-modal {
            border: none;
            border-radius: 12px !important;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .gradient-header {
            background: #ffffff;
            border: none;
            padding: 24px 28px;
            color: #0f172a;
            border-bottom: 1px solid #e5e7eb;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon {
            width: 56px;
            height: 56px;
            background: #dbeafe;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #3b82f6;
        }

        .header-text {
            flex: 1;
        }

        .header-text .modal-title {
            color: #0f172a;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .header-text small {
            display: block;
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Receipt Preview */
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
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            padding: 24px 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .footer-instructions {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px 20px;
            border-radius: 10px;
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
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
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
            flex-wrap: wrap;
        }

        .footer-actions .btn-lg {
            padding: 14px 28px;
            font-weight: 700;
            font-size: 1rem;
            border-radius: 10px;
            min-width: 160px;
        }

        .footer-actions .btn-light {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #374151;
        }

        .footer-actions .btn-light:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }

        .footer-actions .btn-primary {
            background: #3b82f6;
            border: none;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .footer-actions .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
        }

        .footer-actions .btn-success {
            background: #10b981;
            border: none;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .footer-actions .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }

        .footer-actions .btn-danger {
            background: #ef4444;
            border: none;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .footer-actions .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3);
        }

        .footer-actions .btn-warning {
            background: #f59e0b;
            border: none;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }

        .footer-actions .btn-warning:hover {
            background: #d97706;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.3);
        }

        /* Payment Details Card */
        .payment-details-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .payment-details-card h6 {
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }

        .detail-value {
            font-size: 0.9rem;
            color: #0f172a;
            font-weight: 600;
        }

        .detail-value.or-number {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            color: #2563eb;
            background: #dbeafe;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                margin-top: 70px;
                padding: 1.5rem;
            }

            .footer-actions {
                flex-direction: column;
            }

            .footer-actions .btn-lg {
                width: 100%;
            }

            .instruction-steps {
                flex-direction: column;
                gap: 1rem;
            }

            .receipt-container {
                padding: 1rem;
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
                    <i class="fas fa-clock"></i> Pending Print Payments
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

            <!-- Info Box -->
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle"></i>
                <strong>What are Pending Print Payments?</strong><br>
                These are payments that were recorded but never confirmed as printed. This can happen if:
                <ul class="mb-0 mt-2">
                    <li>The page was closed before confirming the print</li>
                    <li>The printer jammed and the cashier didn't complete the process</li>
                    <li>There was a system error during confirmation</li>
                </ul>
                <strong>You can:</strong> View the receipt, mark as completed, or void the payment.
            </div>

            <!-- Pending Payments Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-hourglass-half"></i> Payments Waiting for Confirmation (<?= count($pendingPayments) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingPayments)): ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle"></i> No pending print payments. All payments are finalized!
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>OR Number</th>
                                        <th>Ticket Number</th>
                                        <th>Driver</th>
                                        <th>Amount</th>
                                        <th>Payment Date</th>
                                        <th>Collected By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingPayments as $payment):
                                        // Calculate payment age in hours
                                        $paymentTime = strtotime($payment['payment_date']);
                                        $currentTime = time();
                                        $hoursOld = ($currentTime - $paymentTime) / 3600;

                                        // Determine row class based on age
                                        $rowClass = '';
                                        $ageText = '';
                                        if ($hoursOld > 24) {
                                            $rowClass = 'payment-age-old';
                                            $daysOld = floor($hoursOld / 24);
                                            $ageText = $daysOld . ' day' . ($daysOld > 1 ? 's' : '') . ' old';
                                        } elseif ($hoursOld > 4) {
                                            $rowClass = 'payment-age-warning';
                                            $ageText = floor($hoursOld) . ' hours old';
                                        }
                                    ?>
                                        <tr class="<?= $rowClass ?>"
                                            data-payment-id="<?= $payment['payment_id'] ?>"
                                            data-receipt-number="<?= htmlspecialchars($payment['receipt_number']) ?>"
                                            data-ticket-number="<?= htmlspecialchars($payment['ticket_number']) ?>"
                                            data-driver-name="<?= htmlspecialchars($payment['driver_name']) ?>"
                                            data-amount="<?= $payment['amount_paid'] ?>"
                                            data-payment-method="<?= htmlspecialchars($payment['payment_method']) ?>"
                                            data-payment-date="<?= date('M d, Y H:i', strtotime($payment['payment_date'])) ?>"
                                            data-collected-by="<?= htmlspecialchars($payment['collected_by_name']) ?>"
                                        >
                                            <td><strong>#<?= $payment['payment_id'] ?></strong></td>
                                            <td>
                                                <span style="font-family: 'Courier New', monospace; font-weight: bold;">
                                                    <?= htmlspecialchars($payment['receipt_number']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($payment['ticket_number']) ?></td>
                                            <td><?= htmlspecialchars($payment['driver_name']) ?></td>
                                            <td>
                                                <strong class="text-success">
                                                    ₱<?= number_format($payment['amount_paid'], 2) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?= date('M d, Y H:i', strtotime($payment['payment_date'])) ?>
                                                <?php if ($ageText): ?>
                                                    <br><small class="text-muted"><i class="fas fa-clock"></i> <?= $ageText ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($payment['collected_by_name']) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button
                                                        class="btn btn-sm btn-primary"
                                                        onclick="viewReceiptModal(this.closest('tr'))"
                                                        title="View Receipt"
                                                    >
                                                        <i class="fas fa-file-invoice"></i> View
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split"
                                                        data-bs-toggle="dropdown"
                                                        aria-expanded="false"
                                                    >
                                                        <span class="visually-hidden">Toggle Dropdown</span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="event.preventDefault(); finalizePayment(<?= $payment['payment_id'] ?>, '<?= $payment['receipt_number'] ?>')">
                                                                <i class="fas fa-check text-success"></i> Finalize Payment
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="event.preventDefault(); printReceipt('<?= $payment['receipt_number'] ?>')">
                                                                <i class="fas fa-print text-primary"></i> Print Receipt
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#" onclick="event.preventDefault(); changeORNumber(<?= $payment['payment_id'] ?>, '<?= $payment['receipt_number'] ?>')">
                                                                <i class="fas fa-edit text-warning"></i> Change OR Number
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); voidPayment(<?= $payment['payment_id'] ?>, '<?= $payment['receipt_number'] ?>')">
                                                                <i class="fas fa-times-circle"></i> Void Payment
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
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

    <!-- Receipt Preview Modal -->
    <div class="modal fade" id="receiptPreviewModal" tabindex="-1" aria-labelledby="receiptPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content modern-modal">
                <!-- Modern Header -->
                <div class="modal-header gradient-header">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="modal-title mb-0" id="receiptPreviewModalLabel">
                                Receipt Preview
                            </h5>
                            <small>OR #<span id="modal_or_number"></span> | Ticket #<span id="modal_ticket_num"></span></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-0">
                    <div class="row g-0">
                        <!-- Left Column: Payment Details -->
                        <div class="col-md-4 p-4" style="background: #f8fafc; border-right: 1px solid #e5e7eb;">
                            <div class="payment-details-card">
                                <h6><i class="fas fa-info-circle text-primary"></i> Payment Details</h6>
                                <div class="detail-row">
                                    <span class="detail-label">Payment ID</span>
                                    <span class="detail-value">#<span id="detail_payment_id"></span></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">OR Number</span>
                                    <span class="detail-value or-number" id="detail_or_number"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Amount Paid</span>
                                    <span class="detail-value text-success">₱<span id="detail_amount"></span></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Payment Method</span>
                                    <span class="detail-value text-capitalize" id="detail_payment_method"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Payment Date</span>
                                    <span class="detail-value" id="detail_payment_date"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Collected By</span>
                                    <span class="detail-value" id="detail_collected_by"></span>
                                </div>
                            </div>

                            <div class="payment-details-card">
                                <h6><i class="fas fa-user text-primary"></i> Driver Information</h6>
                                <div class="detail-row">
                                    <span class="detail-label">Ticket Number</span>
                                    <span class="detail-value" id="detail_ticket_number"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Driver Name</span>
                                    <span class="detail-value" id="detail_driver_name"></span>
                                </div>
                            </div>

                            <div class="alert alert-warning mb-0" style="font-size: 0.85rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Status:</strong> This payment is pending print confirmation.
                            </div>
                        </div>

                        <!-- Right Column: Receipt Preview -->
                        <div class="col-md-8">
                            <div class="receipt-preview-wrapper">
                                <div class="preview-toolbar">
                                    <div class="toolbar-info">
                                        <i class="fas fa-file-invoice me-2"></i>
                                        <span>Official Receipt Preview</span>
                                    </div>
                                </div>
                                <div class="receipt-container">
                                    <iframe id="receiptIframe" class="receipt-iframe"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modern Footer with Action Buttons -->
                <div class="modal-footer modern-footer">
                    <div class="footer-instructions">
                        <div class="instruction-steps">
                            <div class="step">
                                <span class="step-number">1</span>
                                <span class="step-text">Review payment details and receipt</span>
                            </div>
                            <div class="step">
                                <span class="step-number">2</span>
                                <span class="step-text">Choose an action below</span>
                            </div>
                            <div class="step">
                                <span class="step-number">3</span>
                                <span class="step-text">Confirm to finalize the payment</span>
                            </div>
                        </div>
                    </div>
                    <div class="footer-actions">
                        <button type="button" class="btn btn-light btn-lg" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button type="button" class="btn btn-warning btn-lg" onclick="changeORNumberFromModal()">
                            <i class="fas fa-edit"></i> Change OR Number
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" onclick="printReceiptFromModal()">
                            <i class="fas fa-print"></i> Print Only
                        </button>
                        <button type="button" class="btn btn-success btn-lg" onclick="printAndFinalize()">
                            <i class="fas fa-check-circle"></i> Print & Finalize
                        </button>
                        <button type="button" class="btn btn-danger btn-lg" onclick="voidFromModal()">
                            <i class="fas fa-times-circle"></i> Void Payment
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

    <script>
        const csrfToken = '<?= $_SESSION['csrf_token'] ?>';
        let currentPaymentData = {};

        /**
         * View receipt in modal with payment details
         */
        function viewReceiptModal(row) {
            // Get data from table row
            currentPaymentData = {
                paymentId: row.dataset.paymentId,
                receiptNumber: row.dataset.receiptNumber,
                ticketNumber: row.dataset.ticketNumber,
                driverName: row.dataset.driverName,
                amount: row.dataset.amount,
                paymentMethod: row.dataset.paymentMethod,
                paymentDate: row.dataset.paymentDate,
                collectedBy: row.dataset.collectedBy
            };

            // Populate modal header
            document.getElementById('modal_or_number').textContent = currentPaymentData.receiptNumber;
            document.getElementById('modal_ticket_num').textContent = currentPaymentData.ticketNumber;

            // Populate payment details
            document.getElementById('detail_payment_id').textContent = currentPaymentData.paymentId;
            document.getElementById('detail_or_number').textContent = currentPaymentData.receiptNumber;
            document.getElementById('detail_amount').textContent = parseFloat(currentPaymentData.amount).toFixed(2);
            document.getElementById('detail_payment_method').textContent = currentPaymentData.paymentMethod;
            document.getElementById('detail_payment_date').textContent = currentPaymentData.paymentDate;
            document.getElementById('detail_collected_by').textContent = currentPaymentData.collectedBy;

            // Populate driver information
            document.getElementById('detail_ticket_number').textContent = currentPaymentData.ticketNumber;
            document.getElementById('detail_driver_name').textContent = currentPaymentData.driverName;

            // Load receipt in iframe
            const iframe = document.getElementById('receiptIframe');
            iframe.src = '/vlad/public/receipt.php?receipt=' + currentPaymentData.receiptNumber;

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('receiptPreviewModal'));
            modal.show();
        }

        /**
         * Print receipt from modal (print only, don't finalize)
         */
        function printReceiptFromModal() {
            const iframe = document.getElementById('receiptIframe');
            if (iframe.contentWindow) {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }
        }

        /**
         * Print and finalize payment
         */
        function printAndFinalize() {
            Swal.fire({
                title: 'Print & Finalize?',
                html: `This will:<br>
                       1. Open print dialog for OR <strong>${currentPaymentData.receiptNumber}</strong><br>
                       2. Mark payment as completed<br>
                       3. Update citation status to "paid"<br><br>
                       <strong>Please ensure the receipt prints successfully!</strong>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check-circle"></i> Print & Finalize',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Print receipt
                    printReceiptFromModal();

                    // Small delay to allow print dialog to open
                    setTimeout(() => {
                        // Finalize payment
                        finalizePaymentAPI(currentPaymentData.paymentId, currentPaymentData.receiptNumber);
                    }, 500);
                }
            });
        }

        /**
         * Void payment from modal
         */
        function voidFromModal() {
            // Close the receipt modal first
            const modal = bootstrap.Modal.getInstance(document.getElementById('receiptPreviewModal'));
            modal.hide();

            // Small delay before showing void confirmation
            setTimeout(() => {
                voidPayment(currentPaymentData.paymentId, currentPaymentData.receiptNumber);
            }, 300);
        }

        /**
         * Print receipt (for dropdown menu)
         */
        function printReceipt(receiptNumber) {
            window.open('/vlad/public/receipt.php?receipt=' + receiptNumber, '_blank');
        }

        /**
         * Change OR Number (from dropdown menu)
         */
        function changeORNumber(paymentId, currentORNumber) {
            Swal.fire({
                title: 'Change OR Number',
                html: `
                    <div style="margin-bottom: 1.5rem;">
                        <div style="background: #f0f9ff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #bae6fd;">
                            <p style="margin: 0; font-size: 0.9rem; color: #0c4a6e;">
                                <strong>Current OR:</strong> <span style="font-family: 'Courier New', monospace; font-size: 1rem; color: #2563eb; font-weight: bold;">${currentORNumber}</span>
                            </p>
                        </div>
                        <div style="background: #fef3c7; padding: 0.75rem; border-radius: 6px; border-left: 4px solid #f59e0b; font-size: 0.85rem; color: #92400e; margin-bottom: 1rem;">
                            <strong><i class="fas fa-exclamation-triangle"></i> When to use:</strong><br>
                            Printer jammed • Receipt torn • Need new receipt booklet
                        </div>
                    </div>
                `,
                input: 'text',
                inputLabel: 'Enter New OR Number',
                inputPlaceholder: 'e.g., CGVM15320502',
                inputAttributes: {
                    autocapitalize: 'off',
                    autocorrect: 'off',
                    autocomplete: 'off',
                    style: 'font-family: "Courier New", monospace; font-weight: bold; font-size: 1.2rem; text-align: center; padding: 12px;'
                },
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Update OR Number',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6b7280',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please enter a new OR number';
                    }
                    if (value.trim() === currentORNumber) {
                        return 'New OR number must be different from current OR number';
                    }
                    if (value.trim().length < 5) {
                        return 'OR number seems too short';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const newORNumber = result.value.trim();

                    // Confirm the change
                    Swal.fire({
                        title: 'Confirm Change',
                        html: `<div style="text-align: left;">
                               <p>You are about to change the OR number:</p>
                               <table style="width: 100%; margin: 1rem 0;">
                                   <tr>
                                       <td style="padding: 0.5rem; font-weight: 600;">Old OR:</td>
                                       <td style="padding: 0.5rem; font-family: 'Courier New', monospace; text-decoration: line-through; color: #dc2626;">${currentORNumber}</td>
                                   </tr>
                                   <tr>
                                       <td style="padding: 0.5rem; font-weight: 600;">New OR:</td>
                                       <td style="padding: 0.5rem; font-family: 'Courier New', monospace; color: #059669; font-weight: bold;">${newORNumber}</td>
                                   </tr>
                               </table>
                               <div class="alert alert-info" style="font-size: 0.9rem;">
                                   <i class="fas fa-info-circle"></i> This change will be logged for audit purposes.
                               </div>
                               </div>`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-check-circle"></i> Yes, update it',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#059669',
                        cancelButtonColor: '#6b7280'
                    }).then((confirmResult) => {
                        if (confirmResult.isConfirmed) {
                            updateORNumberAPI(paymentId, newORNumber, currentORNumber);
                        }
                    });
                }
            });
        }

        /**
         * Change OR Number from modal
         */
        function changeORNumberFromModal() {
            changeORNumber(currentPaymentData.paymentId, currentPaymentData.receiptNumber);
        }

        /**
         * Update OR Number API call
         */
        function updateORNumberAPI(paymentId, newORNumber, oldORNumber) {
            // Show loading
            Swal.fire({
                title: 'Updating OR Number...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send update request
            const formData = new FormData();
            formData.append('payment_id', paymentId);
            formData.append('new_or_number', newORNumber);
            formData.append('reason', `OR number changed from ${oldORNumber} to ${newORNumber} - Physical receipt damaged/printer jam`);
            formData.append('csrf_token', csrfToken);

            fetch('/vlad/api/payments/update_or_number.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'OR Number Updated!',
                        html: `<div style="text-align: left;">
                               <p>The OR number has been successfully updated:</p>
                               <table style="width: 100%; margin: 1rem 0;">
                                   <tr>
                                       <td style="padding: 0.5rem; font-weight: 600;">Old OR:</td>
                                       <td style="padding: 0.5rem; font-family: 'Courier New', monospace; text-decoration: line-through;">${oldORNumber}</td>
                                   </tr>
                                   <tr>
                                       <td style="padding: 0.5rem; font-weight: 600;">New OR:</td>
                                       <td style="padding: 0.5rem; font-family: 'Courier New', monospace; color: #059669; font-weight: bold;">${newORNumber}</td>
                                   </tr>
                               </table>
                               </div>`,
                        confirmButtonColor: '#059669'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to update OR number',
                        confirmButtonColor: '#dc2626'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error updating OR number: ' + error.message,
                    confirmButtonColor: '#dc2626'
                });
            });
        }

        /**
         * Finalize payment (mark as completed) - API call
         */
        function finalizePaymentAPI(paymentId, receiptNumber) {
            // Show loading
            Swal.fire({
                title: 'Finalizing Payment...',
                text: 'Please wait',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send finalize request
            const formData = new FormData();
            formData.append('payment_id', paymentId);
            formData.append('csrf_token', csrfToken);

            fetch('/vlad/api/payments/finalize_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Payment Finalized!',
                        text: 'Citation status updated to PAID',
                        confirmButtonColor: '#059669'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#dc2626'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error finalizing payment: ' + error.message,
                    confirmButtonColor: '#dc2626'
                });
            });
        }

        /**
         * Finalize payment (mark as completed) - with confirmation
         */
        function finalizePayment(paymentId, receiptNumber) {
            Swal.fire({
                title: 'Finalize Payment?',
                html: `Mark payment with OR <strong>${receiptNumber}</strong> as completed?<br><br>This will update the citation status to "paid".`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Yes, finalize',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    finalizePaymentAPI(paymentId, receiptNumber);
                }
            });
        }

        /**
         * Void payment
         */
        function voidPayment(paymentId, receiptNumber) {
            Swal.fire({
                title: 'Void Payment?',
                html: `This will void payment with OR <strong>${receiptNumber}</strong>.<br><br>The citation will remain in "pending" status.<br><br><strong>This action cannot be undone.</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-times-circle"></i> Yes, void payment',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Voiding Payment...',
                        text: 'Please wait',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Send void request
                    const formData = new FormData();
                    formData.append('payment_id', paymentId);
                    formData.append('reason', 'Payment voided by admin - was stuck in pending_print status');
                    formData.append('csrf_token', csrfToken);

                    fetch('/vlad/api/payments/void_payment.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Payment Voided',
                                text: 'The payment has been voided. Citation remains pending.',
                                confirmButtonColor: '#059669'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                                confirmButtonColor: '#dc2626'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error voiding payment: ' + error.message,
                            confirmButtonColor: '#dc2626'
                        });
                    });
                }
            });
        }
    </script>
</body>
</html>
