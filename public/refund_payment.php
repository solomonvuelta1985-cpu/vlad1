<?php
/**
 * Refund Payment Interface
 *
 * Allows admins to view paid citations and process refunds
 * Automatically reverts citation status to pending
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
require_once ROOT_PATH . '/services/PaymentService.php';

// Require login and admin access
require_login();

if (!is_admin()) {
    set_flash('Access denied. Only administrators can process refunds.', 'danger');
    header('Location: /vlad/public/index.php');
    exit;
}

$pdo = getPDO();
$paymentService = new PaymentService($pdo);

// Get all completed payments
$sql = "SELECT
            p.payment_id,
            p.receipt_number,
            p.amount_paid,
            p.payment_method,
            p.payment_date,
            p.reference_number,
            p.notes,
            c.citation_id,
            c.ticket_number,
            c.status as citation_status,
            CONCAT(c.first_name, ' ', c.last_name) as driver_name,
            c.license_number,
            u.full_name as collected_by_name
        FROM payments p
        INNER JOIN citations c ON p.citation_id = c.citation_id
        LEFT JOIN users u ON p.collected_by = u.user_id
        WHERE p.status = 'completed'
        ORDER BY p.payment_date DESC
        LIMIT 100";

$stmt = $pdo->query($sql);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Page title
$pageTitle = 'Refund Payments';
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
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e0e6ed;
            padding: 1.25rem 1.5rem;
            border-radius: 12px 12px 0 0;
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
            padding: 1rem 0.75rem;
            vertical-align: middle;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .modal-content {
            border-radius: 16px;
        }

        .modal-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
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
                    <i class="fas fa-undo"></i> Refund Payments
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

            <!-- Warning Alert -->
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> Refunding a payment will automatically revert the citation status to "pending".
                This action will be logged in the audit trail for transparency.
            </div>

            <!-- Completed Payments Table -->
            <div class="card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-list"></i> Completed Payments (<?= count($payments) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> No completed payments found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Receipt #</th>
                                        <th>Ticket #</th>
                                        <th>Driver Name</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Payment Date</th>
                                        <th>Collected By</th>
                                        <th>Citation Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($payment['receipt_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($payment['ticket_number']) ?></td>
                                            <td><?= htmlspecialchars($payment['driver_name']) ?></td>
                                            <td><strong class="text-success">₱<?= number_format($payment['amount_paid'], 2) ?></strong></td>
                                            <td><?= ucfirst($payment['payment_method']) ?></td>
                                            <td><?= date('M d, Y h:i A', strtotime($payment['payment_date'])) ?></td>
                                            <td><?= htmlspecialchars($payment['collected_by_name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $payment['citation_status'] === 'paid' ? 'success' : 'warning' ?>">
                                                    <?= ucfirst($payment['citation_status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-danger"
                                                        onclick="openRefundModal(<?= htmlspecialchars(json_encode($payment)) ?>)">
                                                    <i class="fas fa-undo"></i> Refund
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

    <!-- Refund Modal -->
    <div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="refundModalLabel">
                        <i class="fas fa-undo"></i> Confirm Refund
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="refundForm">
                    <div class="modal-body">
                        <input type="hidden" id="payment_id" name="payment_id">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> This action cannot be undone!
                        </div>

                        <!-- Payment Details -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Payment Details:</label>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="40%">Receipt Number:</th>
                                    <td id="modal_receipt_number"></td>
                                </tr>
                                <tr>
                                    <th>Ticket Number:</th>
                                    <td id="modal_ticket_number"></td>
                                </tr>
                                <tr>
                                    <th>Driver Name:</th>
                                    <td id="modal_driver_name"></td>
                                </tr>
                                <tr>
                                    <th>Amount Paid:</th>
                                    <td id="modal_amount_paid" class="fw-bold text-success"></td>
                                </tr>
                                <tr>
                                    <th>Payment Date:</th>
                                    <td id="modal_payment_date"></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Refund Reason -->
                        <div class="mb-3">
                            <label for="refund_reason" class="form-label">
                                <i class="fas fa-comment"></i> Refund Reason *
                            </label>
                            <textarea
                                class="form-control"
                                id="refund_reason"
                                name="reason"
                                rows="3"
                                placeholder="Enter detailed reason for refund (required for audit trail)"
                                required
                            ></textarea>
                            <small class="text-muted">
                                This reason will be logged in the audit trail and displayed in reports.
                            </small>
                        </div>

                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i>
                            After refund, the citation status will automatically revert to <strong>"pending"</strong>
                            and can be paid again if needed.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger" id="confirmRefundBtn">
                            <i class="fas fa-undo"></i> Confirm Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let refundModal;

        document.addEventListener('DOMContentLoaded', function() {
            refundModal = new bootstrap.Modal(document.getElementById('refundModal'));
            document.getElementById('refundForm').addEventListener('submit', handleRefundSubmit);
        });

        function openRefundModal(payment) {
            // Populate payment details
            document.getElementById('payment_id').value = payment.payment_id;
            document.getElementById('modal_receipt_number').textContent = payment.receipt_number;
            document.getElementById('modal_ticket_number').textContent = payment.ticket_number;
            document.getElementById('modal_driver_name').textContent = payment.driver_name;
            document.getElementById('modal_amount_paid').textContent = '₱' + parseFloat(payment.amount_paid).toFixed(2);
            document.getElementById('modal_payment_date').textContent = formatDate(payment.payment_date);

            // Reset form
            document.getElementById('refund_reason').value = '';

            // Show modal
            refundModal.show();
        }

        function handleRefundSubmit(e) {
            e.preventDefault();

            const reason = document.getElementById('refund_reason').value.trim();

            if (reason.length < 10) {
                alert('Please provide a detailed reason for the refund (at least 10 characters).');
                return;
            }

            if (!confirm('Are you sure you want to refund this payment?\n\nThis will revert the citation status to "pending" and cannot be undone.')) {
                return;
            }

            const formData = new FormData(e.target);
            const submitBtn = document.getElementById('confirmRefundBtn');

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            // Submit refund request
            fetch('/vlad/api/refund_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Success: ' + data.message);
                    refundModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-undo"></i> Confirm Refund';
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-undo"></i> Confirm Refund';
            });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
</body>
</html>
