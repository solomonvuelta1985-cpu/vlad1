<?php
/**
 * PaymentProcessor Class
 *
 * Handles payment recording, receipt creation, and citation status updates
 *
 * @package TrafficCitationSystem
 * @subpackage Services\Payment
 */

class PaymentProcessor {
    private $pdo;
    private $validator;
    private $auditService;

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;

        // Initialize dependencies
        require_once __DIR__ . '/PaymentValidator.php';
        require_once dirname(__DIR__) . '/AuditService.php';

        $this->validator = new PaymentValidator($pdo);
        $this->auditService = new AuditService($pdo);
    }

    /**
     * Record a new payment for a citation
     *
     * @param int $citationId Citation ID
     * @param float $amountPaid Amount paid
     * @param string $paymentMethod Payment method (cash, check, online, etc.)
     * @param int $collectedBy User ID of cashier/collector
     * @param array $additionalData Additional payment data (check details, notes, receipt_number, etc.)
     * @return array Result with success status, message, and payment data
     */
    public function recordPayment($citationId, $amountPaid, $paymentMethod, $collectedBy, $additionalData = []) {
        try {
            $this->pdo->beginTransaction();

            // 1. Validate payment
            $validation = $this->validator->validatePayment($citationId, $amountPaid);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }

            // 2. Get OR number (manual entry from physical receipt)
            $receiptNumber = $additionalData['receipt_number'] ?? null;

            if (empty($receiptNumber)) {
                throw new Exception('Receipt/OR number is required. Please enter the OR number from the physical receipt.');
            }

            // 3. Validate OR number uniqueness
            $orValidation = $this->validator->validateReceiptNumber($receiptNumber);
            if (!$orValidation['valid']) {
                throw new Exception($orValidation['message']);
            }

            // 4. Prepare payment data
            $paymentDate = date('Y-m-d H:i:s');
            $referenceNumber = $additionalData['reference_number'] ?? null;
            $checkNumber = $additionalData['check_number'] ?? null;
            $checkBank = $additionalData['check_bank'] ?? null;
            $checkDate = $additionalData['check_date'] ?? null;
            $notes = $additionalData['notes'] ?? null;
            // Set status to 'pending_print' - will be changed to 'completed' after print confirmation
            $status = $additionalData['status'] ?? 'pending_print';

            // 5. Insert payment record
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

            // 6. DO NOT update citation status yet - wait for print confirmation
            // Citation status will be updated to 'paid' after cashier confirms receipt printed successfully
            // This prevents OR number mismatch if printer jams

            // 7. Create receipt record
            $this->createReceiptRecord($paymentId, $receiptNumber, $collectedBy);

            // 8. Commit transaction
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
     * Create receipt record in receipts table
     *
     * @param int $paymentId Payment ID
     * @param string $receiptNumber Receipt number
     * @param int $generatedBy User ID who generated the receipt
     * @return bool Success status
     */
    public function createReceiptRecord($paymentId, $receiptNumber, $generatedBy) {
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
     * Update citation status with audit logging
     *
     * @param int $citationId Citation ID
     * @param string $newStatus New status
     * @param int|null $userId User ID making the change
     * @param string|null $reason Reason for status change
     * @return bool Success status
     * @throws Exception If update fails
     */
    public function updateCitationStatus($citationId, $newStatus, $userId = null, $reason = null) {
        try {
            // Get current status
            $sql = "SELECT status FROM citations WHERE citation_id = :citation_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':citation_id' => $citationId]);
            $citation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$citation) {
                throw new Exception('Citation not found');
            }

            $oldStatus = $citation['status'];

            // Don't update if status is the same
            if ($oldStatus === $newStatus) {
                return true;
            }

            // Update citation status and payment_date
            if ($newStatus === 'paid') {
                $sql = "UPDATE citations
                        SET status = :status,
                            payment_date = NOW()
                        WHERE citation_id = :citation_id";
            } else {
                $sql = "UPDATE citations
                        SET status = :status,
                            payment_date = NULL
                        WHERE citation_id = :citation_id";
            }

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':status' => $newStatus,
                ':citation_id' => $citationId
            ]);

            // Log status change to audit trail
            if ($result) {
                $this->auditService->logCitationStatusChange(
                    $citationId,
                    $oldStatus,
                    $newStatus,
                    $userId,
                    $reason
                );
            }

            return $result;

        } catch (Exception $e) {
            error_log("Error updating citation status: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Manually change citation status (for admin use)
     *
     * @param int $citationId Citation ID
     * @param string $newStatus New status
     * @param int $userId User ID making the change
     * @param string|null $reason Reason for status change
     * @return array Result with success and message
     */
    public function changeCitationStatus($citationId, $newStatus, $userId, $reason = null) {
        try {
            // Validate status
            $validStatuses = ['pending', 'paid', 'contested', 'dismissed', 'void'];
            if (!in_array($newStatus, $validStatuses)) {
                return [
                    'success' => false,
                    'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)
                ];
            }

            $this->pdo->beginTransaction();

            // Update status
            $this->updateCitationStatus($citationId, $newStatus, $userId, $reason);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Citation status updated successfully'
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error updating citation status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Finalize payment after successful print confirmation
     * Updates payment status to 'completed' and citation status to 'paid'
     *
     * @param int $paymentId Payment ID
     * @param int $userId User ID confirming the print
     * @return array Result with success and message
     */
    public function finalizePayment($paymentId, $userId) {
        try {
            $this->pdo->beginTransaction();

            // Get payment details
            $sql = "SELECT p.*, c.status as citation_status
                    FROM payments p
                    JOIN citations c ON p.citation_id = c.citation_id
                    WHERE p.payment_id = :payment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':payment_id' => $paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                throw new Exception('Payment not found');
            }

            if ($payment['status'] !== 'pending_print') {
                throw new Exception('Payment is not in pending_print status');
            }

            // Update payment status to completed
            $sql = "UPDATE payments
                    SET status = 'completed'
                    WHERE payment_id = :payment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':payment_id' => $paymentId]);

            // Update citation status to paid
            $this->updateCitationStatus(
                $payment['citation_id'],
                'paid',
                $userId,
                'Payment confirmed and receipt printed successfully - Receipt: ' . $payment['receipt_number']
            );

            // Update receipt print tracking
            $sql = "UPDATE receipts
                    SET printed_at = NOW(),
                        print_count = print_count + 1,
                        last_printed_by = :user_id,
                        last_printed_at = NOW()
                    WHERE payment_id = :payment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':payment_id' => $paymentId,
                ':user_id' => $userId
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Payment finalized successfully'
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error finalizing payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update OR number for a pending_print payment
     * Used when cashier needs to use different receipt due to printer jam
     *
     * @param int $paymentId Payment ID
     * @param string $newOrNumber New OR number from physical receipt
     * @param int $userId User ID making the change
     * @param string $reason Reason for OR change
     * @return array Result with success and message
     */
    public function updateOrNumber($paymentId, $newOrNumber, $userId, $reason) {
        try {
            $this->pdo->beginTransaction();

            // Get payment details
            $sql = "SELECT * FROM payments WHERE payment_id = :payment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':payment_id' => $paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                throw new Exception('Payment not found');
            }

            if ($payment['status'] !== 'pending_print') {
                throw new Exception('Can only update OR number for pending_print payments');
            }

            // Validate new OR number
            $orValidation = $this->validator->validateReceiptNumber($newOrNumber);
            if (!$orValidation['valid']) {
                throw new Exception($orValidation['message']);
            }

            $oldOrNumber = $payment['receipt_number'];

            // Update payment OR number
            $sql = "UPDATE payments
                    SET receipt_number = :new_or,
                        notes = CONCAT(COALESCE(notes, ''), '\n[OR CHANGED] Old: ', :old_or, ' → New: ', :new_or, ' | Reason: ', :reason)
                    WHERE payment_id = :payment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':payment_id' => $paymentId,
                ':new_or' => $newOrNumber,
                ':old_or' => $oldOrNumber,
                ':reason' => $reason
            ]);

            // Update receipt record
            $sql = "UPDATE receipts
                    SET receipt_number = :new_or
                    WHERE payment_id = :payment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':payment_id' => $paymentId,
                ':new_or' => $newOrNumber
            ]);

            // Log to audit trail
            $this->auditService->logPaymentAction(
                $paymentId,
                'or_number_changed',
                ['receipt_number' => $oldOrNumber],
                ['receipt_number' => $newOrNumber],
                $userId
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'OR number updated successfully',
                'new_or' => $newOrNumber
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error updating OR number: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Void a pending_print payment
     * Used when cashier cancels payment due to errors or printer issues
     *
     * @param int $paymentId Payment ID
     * @param int $userId User ID voiding the payment
     * @param string $reason Reason for voiding
     * @return array Result with success and message
     */
    public function voidPayment($paymentId, $userId, $reason) {
        try {
            $this->pdo->beginTransaction();

            // Get payment details
            $sql = "SELECT * FROM payments WHERE payment_id = :payment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':payment_id' => $paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                throw new Exception('Payment not found');
            }

            if ($payment['status'] !== 'pending_print') {
                throw new Exception('Can only void pending_print payments');
            }

            // Update payment status to voided
            $sql = "UPDATE payments
                    SET status = 'voided',
                        notes = CONCAT(COALESCE(notes, ''), '\n[VOIDED] Reason: ', :reason)
                    WHERE payment_id = :payment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':payment_id' => $paymentId,
                ':reason' => $reason
            ]);

            // Update receipt status to void
            $sql = "UPDATE receipts
                    SET status = 'void',
                        cancellation_reason = :reason,
                        cancelled_by = :user_id,
                        cancelled_at = NOW()
                    WHERE payment_id = :payment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':payment_id' => $paymentId,
                ':reason' => $reason,
                ':user_id' => $userId
            ]);

            // Log to audit trail
            $this->auditService->logPaymentAction(
                $paymentId,
                'voided',
                ['status' => $payment['status']],
                ['status' => 'voided', 'reason' => $reason],
                $userId
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Payment voided successfully'
            ];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Error voiding payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate unique receipt number (currently not used - manual entry preferred)
     * Format: OR-YYYY-NNNNNN
     *
     * @return string Receipt number
     */
    public function generateReceiptNumber() {
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
}
