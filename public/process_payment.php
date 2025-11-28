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
            background: #3b82f6;
            border: none;
        }

        .btn-primary:hover {
            background: #2563eb;
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
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .modal-header {
            background: #ffffff;
            color: #0f172a;
            padding: 24px 28px;
            border-bottom: 1px solid #e5e7eb;
            border-radius: 0;
        }

        /* Modal Two-Column Layout */
        .modal-two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: start;
        }

        .modal-column-left,
        .modal-column-right {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .modal-column-left .summary-section,
        .modal-column-right .amount-display,
        .modal-column-right .payment-info-section,
        .modal-column-right .change-display,
        .modal-column-right > div:last-child {
            margin-bottom: 20px;
        }

        .modal-column-left .summary-section:last-child,
        .modal-column-right > div:last-child {
            margin-bottom: 0;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .modal-title i {
            font-size: 1.25rem;
            color: #3b82f6;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            border-radius: 8px;
        }

        .modal-header .btn-close {
            opacity: 0.5;
            font-size: 1.25rem;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 28px;
            font-size: 0.9375rem;
            background: #f8fafc;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        /* Custom scrollbar for modal body */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Citation Summary Box */
        .summary-section {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
        }

        .summary-section h6 {
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .summary-section h6 i {
            color: #3b82f6;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .summary-label {
            font-size: 0.6875rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-value {
            font-size: 0.9375rem;
            color: #0f172a;
            font-weight: 600;
        }

        /* Amount Display - Horizontal Card Design */
        .amount-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            background: #eff6ff;
            border-radius: 10px;
            margin: 24px 0;
            border-left: 4px solid #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.08);
        }

        .amount-display .amount-content {
            flex: 1;
        }

        .amount-display small {
            display: block;
            font-size: 0.75rem;
            color: #1e40af;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }

        .amount-display .amount-value {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
        }

        .amount-display .amount-icon {
            width: 56px;
            height: 56px;
            background: #3b82f6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.5rem;
            flex-shrink: 0;
            margin-left: 20px;
        }

        /* Change Display - Horizontal Card Design */
        .change-display {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            background: #d1fae5;
            border-radius: 10px;
            margin: 24px 0;
            border-left: 4px solid #10b981;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.08);
        }

        .change-display .change-content {
            flex: 1;
        }

        .change-display small {
            display: block;
            font-size: 0.75rem;
            color: #047857;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }

        .change-display .change-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: #0f172a;
        }

        .change-display .change-icon {
            width: 56px;
            height: 56px;
            background: #10b981;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.5rem;
            flex-shrink: 0;
            margin-left: 20px;
        }

        /* Payment Information Section */
        .payment-info-section {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
        }

        .payment-info-section h6 {
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

        .payment-info-section h6 i {
            color: #3b82f6;
        }

        .payment-info-section .row {
            margin-top: 8px;
        }

        /* Form Elements */
        .form-label {
            font-size: 0.8125rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            font-size: 0.875rem;
            color: #3b82f6;
        }

        .form-control, .form-select {
            font-size: 0.9375rem;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            transition: all 0.2s;
            background: #ffffff;
        }

        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .form-text {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-text i {
            color: #3b82f6;
        }

        .modal-footer {
            padding: 20px 28px;
            background-color: #ffffff;
            border-top: 1px solid #e5e7eb;
            border-radius: 0;
            gap: 12px;
            display: flex;
            justify-content: flex-end;
        }

        .modal-footer .btn {
            padding: 12px 24px;
            font-weight: 600;
            font-size: 0.9375rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .modal-footer .btn-secondary {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #374151;
        }

        .modal-footer .btn-secondary:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }

        .modal-footer .btn-primary {
            background: #3b82f6;
            border: none;
            color: #ffffff;
        }

        .modal-footer .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
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
            border-radius: 12px !important;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        /* Clean Header */
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
            background: #d1fae5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: #059669;
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

        /* Modern Loading State */
        .receipt-loading-modern {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 500px;
            background: #f8fafc;
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
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
        }

        .spinner-ring:nth-child(2) {
            width: 80%;
            height: 80%;
            top: 10%;
            left: 10%;
            border-top-color: #2563eb;
            animation-delay: 0.2s;
        }

        .spinner-ring:nth-child(3) {
            width: 60%;
            height: 60%;
            top: 20%;
            left: 20%;
            border-top-color: #60a5fa;
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
            color: #3b82f6;
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
            background: #3b82f6;
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

        /* Filter & Search Styles */
        .input-group-lg .input-group-text {
            font-size: 1.1rem;
            background: #f8fafc;
            border-right: none;
        }

        .input-group-lg .form-control {
            border-left: none;
        }

        .input-group-lg .form-control:focus {
            border-left: none;
        }

        .stat-item {
            padding: 0.5rem;
        }

        .pagination {
            margin: 0;
        }

        .pagination .page-link {
            border-radius: 6px;
            margin: 0 2px;
            font-weight: 500;
            color: #475569;
        }

        .pagination .page-link:hover {
            background-color: #e0e6ed;
        }

        .pagination .page-item.active .page-link {
            background: #3b82f6;
            border-color: #3b82f6;
        }

        .pagination .page-item.disabled .page-link {
            opacity: 0.5;
        }

        #loadingSpinner {
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Payment Summary Styles */
        .success-icon-wrapper {
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .payment-summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.25rem;
        }

        .summary-section-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }

        .summary-value {
            font-size: 0.95rem;
            color: #0f172a;
            font-weight: 600;
        }

        .summary-value.or-number {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            color: #2563eb;
            background: #dbeafe;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
        }

        .highlight-change {
            background: #cfe2ff;
            padding: 0.75rem;
            margin-top: 0.5rem;
            border-radius: 6px;
            border: 1px solid #9ec5fe;
        }

        .highlight-change .summary-value {
            color: #0d6efd;
            font-size: 1.1rem;
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

            .modal-two-column {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .amount-display .amount-value,
            .change-display .change-value {
                font-size: 1.5rem;
            }

            .amount-display .amount-icon,
            .change-display .change-icon {
                width: 48px;
                height: 48px;
                font-size: 1.25rem;
                margin-left: 12px;
            }

            .modal-body {
                padding: 20px;
            }

            .payment-info-section {
                padding: 16px;
            }

            .payment-info-section .row {
                row-gap: 16px;
            }

            #paginationContainer {
                flex-direction: column;
                gap: 1rem;
            }

            .summary-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
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

            <!-- Search & Filters Card -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter"></i> Search & Filters
                    </h5>
                </div>
                <div class="card-body">
                    <form id="filterForm" onsubmit="return false;">
                        <!-- Search Bar -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="searchInput"
                                        placeholder="Search by ticket number, driver name, license, or plate number..."
                                        autocomplete="off"
                                    >
                                    <button class="btn btn-primary" type="button" onclick="applyFilters()">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" onclick="clearFilters()">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Filters (Collapsible) -->
                        <div class="collapse" id="advancedFilters">
                            <div class="row g-3">
                                <!-- Date Range -->
                                <div class="col-md-3">
                                    <label class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="dateFrom">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="dateTo">
                                </div>

                                <!-- Amount Range -->
                                <div class="col-md-3">
                                    <label class="form-label">Min Amount (₱)</label>
                                    <input type="number" class="form-control" id="minAmount" step="0.01" placeholder="0.00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Max Amount (₱)</label>
                                    <input type="number" class="form-control" id="maxAmount" step="0.01" placeholder="99999.99">
                                </div>

                                <!-- Violation Type -->
                                <div class="col-md-6">
                                    <label class="form-label">Violation Type</label>
                                    <select class="form-select" id="violationType">
                                        <option value="">All Violations</option>
                                        <!-- Will be populated via AJAX -->
                                    </select>
                                </div>

                                <!-- Sort By -->
                                <div class="col-md-6">
                                    <label class="form-label">Sort By</label>
                                    <select class="form-select" id="sortBy">
                                        <option value="date_desc">Newest First</option>
                                        <option value="date_asc">Oldest First</option>
                                        <option value="amount_desc">Highest Amount</option>
                                        <option value="amount_asc">Lowest Amount</option>
                                        <option value="driver_name">Driver Name (A-Z)</option>
                                        <option value="ticket_number">Ticket Number</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-primary" type="button" onclick="applyFilters()">
                                    <i class="fas fa-check"></i> Apply Filters
                                </button>
                                <button class="btn btn-outline-secondary" type="button" onclick="clearFilters()">
                                    <i class="fas fa-redo"></i> Reset All
                                </button>
                            </div>
                        </div>

                        <!-- Toggle Advanced Filters -->
                        <div class="text-center mt-2">
                            <a
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="collapse"
                                href="#advancedFilters"
                                role="button"
                                aria-expanded="false"
                                aria-controls="advancedFilters"
                            >
                                <i class="fas fa-sliders-h"></i> Advanced Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card mb-3" id="statsCard" style="display: none;">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h6 class="text-muted mb-1">Total Citations</h6>
                                <h4 class="mb-0" id="statTotalCitations">0</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h6 class="text-muted mb-1">Total Amount</h6>
                                <h4 class="mb-0 text-success" id="statTotalAmount">₱0.00</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h6 class="text-muted mb-1">Average Fine</h6>
                                <h4 class="mb-0 text-primary" id="statAvgFine">₱0.00</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <h6 class="text-muted mb-1">Fine Range</h6>
                                <h4 class="mb-0 text-info" id="statFineRange">₱0 - ₱0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Citations Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Pending Citations <span id="citationCount">(0)</span>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-success" onclick="exportToCSV()" id="exportBtn" style="display: none;">
                            <i class="fas fa-file-excel"></i> Export to CSV
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Loading Spinner -->
                    <div id="loadingSpinner" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading citations...</p>
                    </div>

                    <!-- No Results Message -->
                    <div id="noResults" class="alert alert-info mb-0" style="display: none;">
                        <i class="fas fa-info-circle"></i> No pending citations found matching your filters.
                    </div>

                    <!-- Table Container -->
                    <div id="tableContainer">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="citationsTable">
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
                                <tbody id="citationsTableBody">
                                    <!-- Will be populated via AJAX -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div id="paginationContainer" class="d-flex justify-content-between align-items-center mt-3">
                            <div class="pagination-info">
                                <span id="paginationInfo">Showing 0 to 0 of 0 citations</span>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination mb-0" id="paginationControls">
                                    <!-- Will be populated via AJAX -->
                                </ul>
                            </nav>
                            <div class="page-size-selector">
                                <select class="form-select form-select-sm" id="pageSizeSelect" onchange="changePageSize(this.value)" style="width: auto;">
                                    <option value="10">10 per page</option>
                                    <option value="25" selected>25 per page</option>
                                    <option value="50">50 per page</option>
                                    <option value="100">100 per page</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Processing Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
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

                        <div class="modal-two-column">
                            <!-- Left Column: Citation Details -->
                            <div class="modal-column-left">
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
                            </div>

                            <!-- Right Column: Payment Information -->
                            <div class="modal-column-right">
                                <!-- Amount Due -->
                                <div class="amount-display">
                                    <div class="amount-content">
                                        <small>Amount Due</small>
                                        <div class="amount-value">₱<span id="modal_amount">0.00</span></div>
                                    </div>
                                    <div class="amount-icon">
                                        <i class="fas fa-peso-sign"></i>
                                    </div>
                                </div>

                                <!-- Payment Information Section -->
                                <div class="payment-info-section">
                                    <h6><i class="fas fa-credit-card"></i> Payment Information</h6>

                                    <div class="row g-3">
                                        <!-- Payment Method -->
                                        <div class="col-md-6">
                                            <label for="payment_method" class="form-label">
                                                <i class="fas fa-wallet"></i> Payment Method *
                                            </label>
                                            <select class="form-select" id="payment_method" name="payment_method" required>
                                                <option value="">Select Method</option>
                                                <option value="cash" selected>Cash</option>
                                                <option value="gcash">GCash</option>
                                                <option value="paymaya">PayMaya</option>
                                                <option value="bank_transfer">Bank Transfer</option>
                                                <option value="check">Check</option>
                                            </select>
                                        </div>

                                        <!-- OR/Receipt Number -->
                                        <div class="col-md-6">
                                            <label for="receipt_number" class="form-label">
                                                <i class="fas fa-receipt"></i> OR Number *
                                            </label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="receipt_number"
                                                name="receipt_number"
                                                required
                                                placeholder="e.g., CGVM15320501"
                                                style="font-family: 'Courier New', monospace; font-weight: bold; font-size: 1rem;"
                                            >
                                        </div>

                                        <!-- Cash Received (only for cash payments) -->
                                        <div class="col-md-6" id="cashReceivedField">
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
                                                placeholder="0.00"
                                            >
                                        </div>

                                        <!-- Reference Number (for non-cash payments) -->
                                        <div class="col-md-6" id="referenceField" style="display: none;">
                                            <label for="reference_number" class="form-label">
                                                <i class="fas fa-hashtag"></i> Reference Number
                                            </label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                id="reference_number"
                                                name="reference_number"
                                                placeholder="Transaction reference"
                                            >
                                        </div>
                                    </div>

                                    <div class="form-text mt-2">
                                        <i class="fas fa-info-circle"></i> Enter the OR number exactly as it appears on the physical receipt booklet.
                                    </div>
                                </div>

                                <!-- Change Display (only for cash payments) -->
                                <div class="change-display" id="changeDisplay" style="display: none;">
                                    <div class="change-content">
                                        <small>Change</small>
                                        <div class="change-value">₱<span id="change_amount">0.00</span></div>
                                    </div>
                                    <div class="change-icon">
                                        <i class="fas fa-hand-holding-usd"></i>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="mb-0">
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
                <div class="modal-header" style="background: #ffffff; border-bottom: 1px solid #e5e7eb;">
                    <h5 class="modal-title" id="reprintOptionsModalLabel" style="color: #0f172a; display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b; background: #fef3c7; padding: 8px; border-radius: 8px; font-size: 1.25rem;"></i>
                        <span>Printer Problem</span>
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

    <!-- Payment Summary Modal -->
    <div class="modal fade" id="printPreviewModal" tabindex="-1" aria-labelledby="printPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modern-modal">
                <!-- Modern Header -->
                <div class="modal-header gradient-header">
                    <div class="header-content">
                        <div class="header-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="header-text">
                            <h5 class="modal-title mb-0" id="printPreviewModalLabel">
                                Payment Confirmed
                            </h5>
                            <small class="text-white-50">Transaction completed successfully</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Payment Summary Content -->
                <div class="modal-body p-4">
                    <div id="paymentSummaryContent">
                        <!-- Content will be populated via JavaScript -->
                    </div>
                </div>

                <!-- Modern Footer with Instructions -->
                <div class="modal-footer modern-footer">
                    <div class="footer-instructions">
                        <div class="instruction-steps">
                            <div class="step">
                                <span class="step-number">1</span>
                                <span class="step-text">Review payment details above</span>
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
    <!-- Process Payment Filters & Pagination -->
    <script src="../assets/js/process_payment_filters.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/process_payment.js"></script>
</body>
</html>
