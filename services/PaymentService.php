<?php
/**
 * PaymentService Class
 *
 * Handles all payment-related business logic including:
 * - Recording new payments
 * - Payment validation
 * - Payment history retrieval
 * - Payment status management
 * - Refunds and cancellations
 *
 * @package TrafficCitationSystem
 * @subpackage Services
 */

class PaymentService {
    private $pdo;
    private $tableName = 'payments';

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Record a new payment for a citation
     *
     * @param int $citationId Citation ID
     * @param float $amountPaid Amount paid
     * @param string $paymentMethod Payment method (cash, check, online, etc.)
     * @param int $collectedBy User ID of cashier/collector
     * @param array $additionalData Additional payment data (check details, notes, etc.)
     * @return array Result with success status and payment/receipt data
     */
    public function recordPayment($citationId, $amountPaid, $paymentMethod, $collectedBy, $additionalData = []) {
        try {
            $this->pdo->beginTransaction();

            // 1. Validate payment
            $validation = $this->validatePayment($citationId, $amountPaid);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }

            // 2. Generate receipt number
            $receiptNumber = $this->generateReceiptNumber();

            // 3. Prepare payment data
            $paymentDate = date('Y-m-d H:i:s');
            $referenceNumber = $additionalData['reference_number'] ?? null;
            $checkNumber = $additionalData['check_number'] ?? null;
            $checkBank = $additionalData['check_bank'] ?? null;
            $checkDate = $additionalData['check_date'] ?? null;
            $notes = $additionalData['notes'] ?? null;
            $status = $additionalData['status'] ?? 'completed';

            // 4. Insert payment record
            $sql = "INSERT INTO payments (
                citation_id, amount_paid, payment_method, payment_date,
                reference_number, receipt_number, collected_by,
                check_number, check_bank, check_date, notes, status
            ) VALUES (
                :citation_id, :amount_paid, :payment_method, :payment_date,
                :reference_number, :receipt_number, :collected_by,
                :check_number, :check_bank, :check_date, :notes, :status
            )";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':citation_id' => $citationId,
                ':amount_paid' => $amountPaid,
                ':payment_method' => $paymentMethod,
                ':payment_date' => $paymentDate,
                ':reference_number' => $referenceNumber,
                ':receipt_number' => $receiptNumber,
                ':collected_by' => $collectedBy,
                ':check_number' => $checkNumber,
                ':check_bank' => $checkBank,
                ':check_date' => $checkDate,
                ':notes' => $notes,
                ':status' => $status
            ]);

            $paymentId = $this->pdo->lastInsertId();

            // 5. Create receipt record
            $this->createReceiptRecord($paymentId, $receiptNumber, $collectedBy);

            // 6. Commit transaction
            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Payment recorded successfully',
                'payment_id' => $paymentId,
                'receipt_number' => $receiptNumber,
                'payment_date' => $paymentDate
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error recording payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate payment before recording
     *
     * @param int $citationId Citation ID
     * @param float $amountPaid Amount to be paid
     * @return array Validation result
     */
    public function validatePayment($citationId, $amountPaid) {
        // 1. Check if citation exists
        $sql = "SELECT citation_id, status, total_fine, payment_date
                FROM citations
                WHERE citation_id = :citation_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':citation_id' => $citationId]);
        $citation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$citation) {
            return [
                'valid' => false,
                'message' => 'Citation not found'
            ];
        }

        // 2. Check if citation is already paid
        if ($citation['status'] === 'paid') {
            return [
                'valid' => false,
                'message' => 'Citation has already been paid'
            ];
        }

        // 3. Check if citation is voided or dismissed
        if (in_array($citation['status'], ['void', 'dismissed'])) {
            return [
                'valid' => false,
                'message' => 'Cannot process payment for ' . $citation['status'] . ' citation'
            ];
        }

        // 4. Validate amount (must match total fine)
        if ($amountPaid != $citation['total_fine']) {
            return [
                'valid' => false,
                'message' => 'Payment amount must match total fine of ₱' . number_format($citation['total_fine'], 2)
            ];
        }

        // 5. Check for duplicate payment
        $sql = "SELECT payment_id FROM payments
                WHERE citation_id = :citation_id
                AND status IN ('completed', 'pending')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':citation_id' => $citationId]);

        if ($stmt->fetch()) {
            return [
                'valid' => false,
                'message' => 'Payment already exists for this citation'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Validation passed',
            'citation' => $citation
        ];
    }

    /**
     * Generate unique receipt number
     * Format: OR-YYYY-NNNNNN
     *
     * @return string Receipt number
     */
    private function generateReceiptNumber() {
        $currentYear = date('Y');

        // Lock the row to prevent race conditions
        $sql = "SELECT current_year, current_number
                FROM receipt_sequence
                WHERE id = 1
                FOR UPDATE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $sequence = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if year has changed
        if ($sequence['current_year'] != $currentYear) {
            // Reset counter for new year
            $newNumber = 1;
            $sql = "UPDATE receipt_sequence
                    SET current_year = :year, current_number = :number
                    WHERE id = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':year' => $currentYear,
                ':number' => $newNumber
            ]);
        } else {
            // Increment counter
            $newNumber = $sequence['current_number'] + 1;
            $sql = "UPDATE receipt_sequence
                    SET current_number = :number
                    WHERE id = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':number' => $newNumber]);
        }

        // Format: OR-2025-000001
        $receiptNumber = 'OR-' . $currentYear . '-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);

        return $receiptNumber;
    }

    /**
     * Create receipt record in receipts table
     *
     * @param int $paymentId Payment ID
     * @param string $receiptNumber Receipt number
     * @param int $generatedBy User ID who generated the receipt
     * @return bool Success status
     */
    private function createReceiptRecord($paymentId, $receiptNumber, $generatedBy) {
        $sql = "INSERT INTO receipts (
            payment_id, receipt_number, generated_by
        ) VALUES (
            :payment_id, :receipt_number, :generated_by
        )";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':payment_id' => $paymentId,
            ':receipt_number' => $receiptNumber,
            ':generated_by' => $generatedBy
        ]);
    }

    /**
     * Get payment history for a citation
     *
     * @param int $citationId Citation ID
     * @return array Payment history records
     */
    public function getPaymentHistory($citationId) {
        $sql = "SELECT
                    p.*,
                    u.full_name as collector_name,
                    u.username as collector_username,
                    r.receipt_id,
                    r.print_count,
                    r.printed_at,
                    r.status as receipt_status
                FROM payments p
                LEFT JOIN users u ON p.collected_by = u.user_id
                LEFT JOIN receipts r ON p.payment_id = r.payment_id
                WHERE p.citation_id = :citation_id
                ORDER BY p.payment_date DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':citation_id' => $citationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all payments with optional filters
     *
     * @param array $filters Filter criteria
     * @param int $limit Results limit
     * @param int $offset Results offset
     * @return array Payments list
     */
    public function getAllPayments($filters = [], $limit = 50, $offset = 0) {
        $where = [];
        $params = [];

        // Date range filter
        if (!empty($filters['date_from'])) {
            $where[] = "p.payment_date >= :date_from";
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "p.payment_date <= :date_to";
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        // Payment method filter
        if (!empty($filters['payment_method'])) {
            $where[] = "p.payment_method = :payment_method";
            $params[':payment_method'] = $filters['payment_method'];
        }

        // Cashier filter
        if (!empty($filters['collected_by'])) {
            $where[] = "p.collected_by = :collected_by";
            $params[':collected_by'] = $filters['collected_by'];
        }

        // Status filter
        if (!empty($filters['status'])) {
            $where[] = "p.status = :status";
            $params[':status'] = $filters['status'];
        }

        // Receipt number search
        if (!empty($filters['receipt_number'])) {
            $where[] = "p.receipt_number LIKE :receipt_number";
            $params[':receipt_number'] = '%' . $filters['receipt_number'] . '%';
        }

        // Ticket number search
        if (!empty($filters['ticket_number'])) {
            $where[] = "c.ticket_number LIKE :ticket_number";
            $params[':ticket_number'] = '%' . $filters['ticket_number'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT
                    p.*,
                    c.ticket_number,
                    CONCAT(c.first_name, ' ', c.last_name) as driver_name,
                    c.license_number,
                    u.full_name as collector_name,
                    r.print_count,
                    r.status as receipt_status
                FROM payments p
                LEFT JOIN citations c ON p.citation_id = c.citation_id
                LEFT JOIN users u ON p.collected_by = u.user_id
                LEFT JOIN receipts r ON p.payment_id = r.payment_id
                $whereClause
                ORDER BY p.payment_date DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        // Bind filter parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        // Bind limit and offset
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get payment by ID
     *
     * @param int $paymentId Payment ID
     * @return array|false Payment record
     */
    public function getPaymentById($paymentId) {
        $sql = "SELECT
                    p.*,
                    c.ticket_number,
                    c.apprehension_datetime as citation_date,
                    c.place_of_apprehension as location,
                    CONCAT(c.first_name, ' ', c.last_name) as driver_name,
                    c.license_number,
                    CONCAT(c.barangay, ', ', c.municipality, ', ', c.province) as driver_address,
                    c.plate_mv_engine_chassis_no as plate_number,
                    c.vehicle_description,
                    u.full_name as collector_name,
                    u.username as collector_username,
                    r.receipt_id,
                    r.print_count,
                    r.printed_at,
                    r.status as receipt_status
                FROM payments p
                LEFT JOIN citations c ON p.citation_id = c.citation_id
                LEFT JOIN users u ON p.collected_by = u.user_id
                LEFT JOIN receipts r ON p.payment_id = r.payment_id
                WHERE p.payment_id = :payment_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':payment_id' => $paymentId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Refund a payment
     *
     * @param int $paymentId Payment ID
     * @param string $reason Refund reason
     * @param int $userId User ID performing the refund
     * @return array Result
     */
    public function refundPayment($paymentId, $reason, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Update payment status
            $sql = "UPDATE payments
                    SET status = 'refunded',
                        notes = CONCAT(COALESCE(notes, ''), '\n[REFUNDED] ', :reason),
                        updated_at = NOW()
                    WHERE payment_id = :payment_id
                    AND status = 'completed'";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':payment_id' => $paymentId,
                ':reason' => $reason
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Payment not found or cannot be refunded');
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Payment refunded successfully'
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error refunding payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get payment statistics
     *
     * @param array $dateRange Optional date range
     * @return array Statistics
     */
    public function getPaymentStatistics($dateRange = []) {
        $where = "WHERE p.status = 'completed'";
        $params = [];

        if (!empty($dateRange['from'])) {
            $where .= " AND p.payment_date >= :date_from";
            $params[':date_from'] = $dateRange['from'] . ' 00:00:00';
        }
        if (!empty($dateRange['to'])) {
            $where .= " AND p.payment_date <= :date_to";
            $params[':date_to'] = $dateRange['to'] . ' 23:59:59';
        }

        $sql = "SELECT
                    COUNT(*) as total_payments,
                    SUM(amount_paid) as total_amount,
                    AVG(amount_paid) as average_payment,
                    MIN(amount_paid) as min_payment,
                    MAX(amount_paid) as max_payment,
                    COUNT(DISTINCT collected_by) as unique_collectors
                FROM payments p
                $where";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
