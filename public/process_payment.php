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

// Get all pending citations
$pdo = getPDO();
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
        WHERE c.status IN ('pending', 'unpaid')
        GROUP BY c.citation_id
        ORDER BY c.apprehension_datetime DESC";

$stmt = $pdo->query($sql);
$pendingCitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 16px 16px 0 0;
            border: none;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 2rem;
            font-size: 0.95rem;
        }

        /* Citation Summary Box */
        .summary-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid #e0e6ed;
        }

        .summary-section h6 {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
        }

        .summary-label {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-value {
            font-size: 0.95rem;
            color: #1e293b;
            font-weight: 500;
        }

        /* Amount Display - Large & Clear */
        .amount-display {
            font-size: 2.5rem;
            font-weight: 700;
            color: #059669;
            text-align: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 12px;
            margin: 1.5rem 0;
            border: 2px solid #6ee7b7;
        }

        .amount-display small {
            display: block;
            font-size: 0.75rem;
            color: #047857;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.25rem;
        }

        /* Change Display */
        .change-display {
            font-size: 2.5rem;
            font-weight: 700;
            color: #0284c7;
            text-align: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 12px;
            margin: 1.5rem 0;
            border: 2px solid #7dd3fc;
        }

        .change-display small {
            display: block;
            font-size: 0.75rem;
            color: #0369a1;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.25rem;
        }

        /* Form Elements */
        .form-label {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control, .form-select {
            font-size: 0.95rem;
            padding: 0.75rem;
            border: 1.5px solid #e0e6ed;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        textarea.form-control {
            resize: vertical;
        }

        .modal-footer {
            padding: 1.25rem 2rem;
            background-color: #f8fafc;
            border-top: 1px solid #e0e6ed;
            border-radius: 0 0 16px 16px;
        }

        .modal-footer .btn {
            padding: 0.625rem 1.5rem;
            font-weight: 600;
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
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success" id="confirmPaymentBtn" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border: none;">
                            <i class="fas fa-check-circle"></i> Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/process_payment.js"></script>
</body>
</html>
