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

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                margin-top: 70px;
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
                                    <?php foreach ($pendingPayments as $payment): ?>
                                        <tr>
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
                                            <td><?= date('M d, Y H:i', strtotime($payment['payment_date'])) ?></td>
                                            <td><?= htmlspecialchars($payment['collected_by_name']) ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button
                                                        class="btn btn-sm btn-primary"
                                                        onclick="viewReceipt('<?= $payment['receipt_number'] ?>')"
                                                        title="View Receipt"
                                                    >
                                                        <i class="fas fa-file-invoice"></i>
                                                    </button>
                                                    <button
                                                        class="btn btn-sm btn-success"
                                                        onclick="finalizePayment(<?= $payment['payment_id'] ?>, '<?= $payment['receipt_number'] ?>')"
                                                        title="Mark as Completed"
                                                    >
                                                        <i class="fas fa-check"></i> Finalize
                                                    </button>
                                                    <button
                                                        class="btn btn-sm btn-danger"
                                                        onclick="voidPayment(<?= $payment['payment_id'] ?>, '<?= $payment['receipt_number'] ?>')"
                                                        title="Void Payment"
                                                    >
                                                        <i class="fas fa-times"></i> Void
                                                    </button>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const csrfToken = '<?= $_SESSION['csrf_token'] ?>';

        /**
         * View receipt in new window
         */
        function viewReceipt(receiptNumber) {
            window.open('/vlad/public/receipt.php?receipt=' + receiptNumber, '_blank');
        }

        /**
         * Finalize payment (mark as completed)
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
