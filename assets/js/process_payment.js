/**
 * Process Payment JavaScript
 * Handles payment processing modal and cash/change calculations
 */

let paymentModal;
let currentCitation = null;
let formIsDirty = false;
let receiptLoadTimeout = null;

// OR Number validation pattern (2-4 uppercase letters followed by 6-10 digits)
const OR_NUMBER_PATTERN = /^[A-Z]{2,4}[0-9]{6,10}$/;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal
    paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));

    // Payment method change event
    document.getElementById('payment_method').addEventListener('change', handlePaymentMethodChange);

    // Cash received input event - calculate change
    document.getElementById('cash_received').addEventListener('input', calculateChange);

    // OR number validation
    document.getElementById('receipt_number').addEventListener('input', validateOrNumberFormat);
    document.getElementById('receipt_number').addEventListener('blur', validateOrNumberFormat);

    // Payment form submit
    document.getElementById('paymentForm').addEventListener('submit', handlePaymentSubmit);

    // Track form changes for dirty state
    document.getElementById('paymentForm').addEventListener('input', markFormAsDirty);

    // Warn before closing modal if form has data
    document.getElementById('paymentModal').addEventListener('hide.bs.modal', preventDataLoss);
});

/**
 * Open payment modal with citation data
 */
function openPaymentModal(citation) {
    currentCitation = citation;

    // Populate citation details
    document.getElementById('citation_id').value = citation.citation_id;
    document.getElementById('modal_ticket_number').textContent = citation.ticket_number;
    document.getElementById('modal_driver_name').textContent = citation.driver_name;
    document.getElementById('modal_license').textContent = citation.license_number || 'N/A';
    document.getElementById('modal_vehicle').textContent = (citation.plate_number || 'N/A') + ' - ' + (citation.vehicle_description || '');
    document.getElementById('modal_violation').textContent = citation.violations || 'N/A';
    document.getElementById('modal_date').textContent = formatDate(citation.apprehension_datetime);
    document.getElementById('modal_amount').textContent = parseFloat(citation.total_fine).toFixed(2);

    // Reset form
    document.getElementById('paymentForm').reset();
    document.getElementById('citation_id').value = citation.citation_id;
    document.getElementById('payment_method').value = 'cash';
    document.getElementById('receipt_number').value = '';
    document.getElementById('cash_received').value = '';
    document.getElementById('changeDisplay').style.display = 'none';

    // Reset dirty state
    formIsDirty = false;

    // Reset OR validation feedback
    removeOrValidationFeedback();

    // Show cash fields by default
    handlePaymentMethodChange();

    // Show modal
    paymentModal.show();
}

/**
 * Handle payment method change
 */
function handlePaymentMethodChange() {
    const paymentMethod = document.getElementById('payment_method').value;
    const cashFields = document.getElementById('cashFields');
    const referenceField = document.getElementById('referenceField');
    const cashReceivedInput = document.getElementById('cash_received');
    const referenceNumberInput = document.getElementById('reference_number');

    if (paymentMethod === 'cash') {
        // Show cash fields, hide reference field
        cashFields.style.display = 'block';
        referenceField.style.display = 'none';
        cashReceivedInput.required = true;
        referenceNumberInput.required = false;
    } else {
        // Hide cash fields, show reference field
        cashFields.style.display = 'none';
        referenceField.style.display = 'block';
        cashReceivedInput.required = false;
        referenceNumberInput.required = false;
    }

    // Reset change display
    document.getElementById('changeDisplay').style.display = 'none';
}

/**
 * Calculate change automatically
 */
function calculateChange() {
    const amountDue = parseFloat(document.getElementById('modal_amount').textContent);
    const cashReceived = parseFloat(document.getElementById('cash_received').value) || 0;
    const change = cashReceived - amountDue;

    if (cashReceived > 0) {
        document.getElementById('change_amount').textContent = change.toFixed(2);
        document.getElementById('changeDisplay').style.display = 'block';

        // Change color based on sufficient/insufficient amount
        const changeDisplay = document.getElementById('changeDisplay');
        if (change < 0) {
            changeDisplay.style.backgroundColor = '#f8d7da';
            changeDisplay.style.color = '#dc3545';
        } else {
            changeDisplay.style.backgroundColor = '#cfe2ff';
            changeDisplay.style.color = '#0d6efd';
        }
    } else {
        document.getElementById('changeDisplay').style.display = 'none';
    }
}

/**
 * Handle payment form submission
 */
function handlePaymentSubmit(e) {
    e.preventDefault();

    const paymentMethod = document.getElementById('payment_method').value;
    const amountDue = parseFloat(document.getElementById('modal_amount').textContent);
    const cashReceived = parseFloat(document.getElementById('cash_received').value) || 0;
    const receiptNumber = document.getElementById('receipt_number').value.trim();

    // Validation for OR/receipt number (REQUIRED)
    if (!receiptNumber) {
        showAlert('OR/Receipt number is required! Please enter the OR number from the physical receipt.', 'danger');
        document.getElementById('receipt_number').focus();
        return;
    }

    // Validate OR number format
    if (!OR_NUMBER_PATTERN.test(receiptNumber)) {
        showAlert('Invalid OR number format! Expected format: 2-4 letters followed by 6-10 digits (e.g., CGVM15320501)', 'danger');
        document.getElementById('receipt_number').focus();
        return;
    }

    // Validation for cash payments
    if (paymentMethod === 'cash' && cashReceived < amountDue) {
        showAlert('Cash received is less than the amount due!', 'danger');
        return;
    }

    // Check CSRF token exists
    const csrfToken = document.querySelector('[name="csrf_token"]')?.value;
    if (!csrfToken) {
        showAlert('Security token missing. Please reload the page and try again.', 'danger');
        return;
    }

    // Confirm payment
    if (!confirm('Confirm payment processing?\n\nThis action cannot be undone.')) {
        return;
    }

    // Disable submit button
    const submitBtn = document.getElementById('confirmPaymentBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    // Prepare form data
    const formData = new FormData(e.target);
    formData.append('amount_paid', amountDue);

    // Calculate change for cash payments
    if (paymentMethod === 'cash') {
        formData.append('change_amount', (cashReceived - amountDue).toFixed(2));
    }

    // Submit payment
    fetch('/vlad/api/payment_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Payment recorded with 'pending_print' status
            formIsDirty = false; // Reset dirty state
            paymentModal.hide();

            // Store payment info for later use
            window.currentPaymentId = data.payment_id;
            window.currentReceiptNumber = data.receipt_number;

            // Show receipt in preview modal instead of new tab
            showReceiptPreview(data.receipt_number, data.payment_id);
        } else {
            showAlert('Error: ' + data.message, 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Payment';
        }
    })
    .catch(error => {
        showAlert('Error processing payment: ' + error.message, 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Payment';
    });
}

/**
 * Show receipt preview in modal
 */
function showReceiptPreview(receiptNumber, paymentId) {
    // Store payment info
    window.currentPaymentId = paymentId;
    window.currentReceiptNumber = receiptNumber;

    // Get modal elements
    const previewModal = new bootstrap.Modal(document.getElementById('printPreviewModal'));
    const loadingDiv = document.getElementById('receiptLoading');
    const contentDiv = document.getElementById('receiptPreviewContent');
    const iframe = document.getElementById('receiptIframe');

    // Show loading state
    loadingDiv.style.display = 'block';
    contentDiv.style.display = 'none';

    // Show modal
    previewModal.show();

    // Clear any existing timeout
    if (receiptLoadTimeout) {
        clearTimeout(receiptLoadTimeout);
    }

    // Set timeout for loading (10 seconds)
    receiptLoadTimeout = setTimeout(() => {
        if (loadingDiv.style.display !== 'none') {
            loadingDiv.innerHTML = `
                <div class="loading-content">
                    <div class="text-center">
                        <i class="fas fa-clock fa-4x mb-4" style="color: #f59e0b;"></i>
                        <h5 class="mb-3">Preview Timeout</h5>
                        <p class="text-muted mb-4">The receipt preview took too long to load.<br>The payment was recorded, but preview timed out.</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <button class="btn btn-primary btn-lg" onclick="retryReceiptLoad('${receiptNumber}', ${paymentId})">
                                <i class="fas fa-redo"></i> Try Again
                            </button>
                            <button class="btn btn-outline-secondary btn-lg" onclick="proceedWithoutPreview(${paymentId})">
                                Skip Preview
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
    }, 10000);

    // Load receipt in iframe - use full URL to avoid connection issues
    const receiptUrl = window.location.origin + '/vlad/public/receipt.php?receipt=' + encodeURIComponent(receiptNumber);
    console.log('Loading receipt:', receiptUrl); // Debug log
    iframe.src = receiptUrl;

    // When iframe loads, hide loading and show content
    iframe.onload = function() {
        // Clear timeout
        if (receiptLoadTimeout) {
            clearTimeout(receiptLoadTimeout);
        }

        // Hide loading and show content
        // Note: We can't check iframe content due to CORS restrictions
        // The timeout will handle actual load failures
        loadingDiv.style.display = 'none';
        contentDiv.style.display = 'block';
    };

    // Handle iframe errors
    iframe.onerror = function(error) {
        console.error('Iframe onerror:', error);
        if (receiptLoadTimeout) {
            clearTimeout(receiptLoadTimeout);
        }
        loadingDiv.innerHTML = `
            <div class="loading-content">
                <div class="text-center">
                    <i class="fas fa-exclamation-circle fa-4x mb-4" style="color: #ef4444;"></i>
                    <h5 class="mb-3">Failed to Load Receipt</h5>
                    <p class="text-muted mb-4">Unable to load the receipt preview.<br>Please check that Apache and MySQL are running.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-danger btn-lg" onclick="retryReceiptLoad('${receiptNumber}', ${paymentId})">
                            <i class="fas fa-redo"></i> Try Again
                        </button>
                        <button class="btn btn-outline-secondary btn-lg" onclick="proceedWithoutPreview(${paymentId})">
                            Skip Preview
                        </button>
                    </div>
                    <p class="text-muted mt-3 small">
                        <i class="fas fa-lightbulb"></i> Tip: The payment was recorded successfully even if preview fails
                    </p>
                </div>
            </div>
        `;
        loadingDiv.style.display = 'flex';
    };
}

/**
 * Print receipt from preview and confirm
 */
function printReceiptFromPreview() {
    const iframe = document.getElementById('receiptIframe');
    const paymentId = window.currentPaymentId;
    const receiptNumber = window.currentReceiptNumber;

    // Print the iframe content
    iframe.contentWindow.focus();
    iframe.contentWindow.print();

    // Close preview modal
    const previewModal = bootstrap.Modal.getInstance(document.getElementById('printPreviewModal'));
    previewModal.hide();

    // Show print confirmation dialog after a brief moment
    setTimeout(() => {
        showPrintConfirmation(paymentId, receiptNumber);
    }, 500);
}

/**
 * Show print confirmation dialog using SweetAlert2
 */
function showPrintConfirmation(paymentId, receiptNumber) {
    Swal.fire({
        title: 'Receipt Print Confirmation',
        text: 'Did the receipt print successfully?',
        icon: 'question',
        showDenyButton: true,
        showCancelButton: false,
        confirmButtonText: '<i class="fas fa-check"></i> Yes - Print OK',
        denyButtonText: '<i class="fas fa-times"></i> No - Printer Problem',
        confirmButtonColor: '#059669',
        denyButtonColor: '#dc2626',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Print successful - finalize payment
            finalizePayment(paymentId);
        } else if (result.isDenied) {
            // Printer problem - show reprint options
            showReprintOptions(paymentId, receiptNumber);
        }
    });
}

/**
 * Finalize payment after print confirmation
 */
function finalizePayment(paymentId) {
    // Check CSRF token exists
    const csrfToken = document.querySelector('[name="csrf_token"]')?.value;
    if (!csrfToken) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Security token missing. Please reload the page and try again.',
            confirmButtonColor: '#dc2626'
        });
        return;
    }

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
                title: 'Payment Completed!',
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
 * Show reprint options modal
 */
function showReprintOptions(paymentId, receiptNumber) {
    // Store values in hidden fields
    document.getElementById('reprint_payment_id').value = paymentId;
    document.getElementById('reprint_current_or').value = receiptNumber;
    document.getElementById('display_current_or').textContent = receiptNumber;

    // Reset new OR input section
    document.getElementById('newOrInputSection').style.display = 'none';
    document.getElementById('new_or_input').value = '';

    // Show modal
    const reprintModal = new bootstrap.Modal(document.getElementById('reprintOptionsModal'));
    reprintModal.show();
}

/**
 * Reprint receipt with same OR number
 */
function reprintReceipt() {
    const receiptNumber = document.getElementById('reprint_current_or').value;
    const paymentId = document.getElementById('reprint_payment_id').value;

    // Close reprint modal
    bootstrap.Modal.getInstance(document.getElementById('reprintOptionsModal')).hide();

    // Show receipt in preview modal instead of new tab
    showReceiptPreview(receiptNumber, paymentId);
}

/**
 * Show new OR input section
 */
function showNewOrInput() {
    document.getElementById('newOrInputSection').style.display = 'block';
    document.getElementById('new_or_input').focus();
}

/**
 * Confirm new OR number
 */
function confirmNewOr() {
    const paymentId = document.getElementById('reprint_payment_id').value;
    const newOrNumber = document.getElementById('new_or_input').value.trim().toUpperCase();

    if (!newOrNumber) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please enter a new OR number',
            confirmButtonColor: '#dc2626'
        });
        return;
    }

    // Validate OR number format
    if (!OR_NUMBER_PATTERN.test(newOrNumber)) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Format',
            text: 'Invalid OR number format! Expected: 2-4 letters + 6-10 digits (e.g., CGVM15320501)',
            confirmButtonColor: '#dc2626'
        });
        return;
    }

    // Check CSRF token exists
    const csrfToken = document.querySelector('[name="csrf_token"]')?.value;
    if (!csrfToken) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Security token missing. Please reload the page and try again.',
            confirmButtonColor: '#dc2626'
        });
        return;
    }

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
    formData.append('new_or_number', newOrNumber);
    formData.append('reason', 'Printer jam - using different receipt');
    formData.append('csrf_token', csrfToken);

    fetch('/vlad/api/payments/update_or_number.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close reprint modal
            bootstrap.Modal.getInstance(document.getElementById('reprintOptionsModal')).hide();

            // Show receipt in preview modal instead of new tab
            showReceiptPreview(data.new_or, paymentId);
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
            text: 'Error updating OR number: ' + error.message,
            confirmButtonColor: '#dc2626'
        });
    });
}

/**
 * Void payment confirmation
 */
function voidPaymentConfirm() {
    Swal.fire({
        title: 'Void Payment?',
        text: 'This will cancel the payment transaction. This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, void payment',
        cancelButtonText: 'No, go back',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            voidPayment();
        }
    });
}

/**
 * Void payment
 */
function voidPayment() {
    const paymentId = document.getElementById('reprint_payment_id').value;

    // Check CSRF token exists
    const csrfToken = document.querySelector('[name="csrf_token"]')?.value;
    if (!csrfToken) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Security token missing. Please reload the page and try again.',
            confirmButtonColor: '#dc2626'
        });
        return;
    }

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
    formData.append('reason', 'Payment cancelled by cashier - printer issue');
    formData.append('csrf_token', csrfToken);

    fetch('/vlad/api/payments/void_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close reprint modal
            bootstrap.Modal.getInstance(document.getElementById('reprintOptionsModal')).hide();

            Swal.fire({
                icon: 'success',
                title: 'Payment Voided',
                text: 'The payment has been cancelled. Citation remains pending.',
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

/**
 * Validate OR number format in real-time
 */
function validateOrNumberFormat() {
    const input = document.getElementById('receipt_number');
    const value = input.value.trim().toUpperCase();

    // Auto-convert to uppercase
    if (input.value !== value) {
        input.value = value;
    }

    // Remove any existing feedback
    removeOrValidationFeedback();

    if (value.length === 0) {
        return; // Don't show validation for empty input
    }

    // Create feedback element
    const feedbackDiv = document.createElement('div');
    feedbackDiv.id = 'or-validation-feedback';
    feedbackDiv.style.marginTop = '0.5rem';
    feedbackDiv.style.fontSize = '0.875rem';

    if (OR_NUMBER_PATTERN.test(value)) {
        // Valid format
        feedbackDiv.className = 'text-success';
        feedbackDiv.innerHTML = '<i class="fas fa-check-circle"></i> Valid OR format';
        input.style.borderColor = '#198754';
        input.style.backgroundColor = '#f0fdf4';
    } else {
        // Invalid format
        feedbackDiv.className = 'text-danger';
        feedbackDiv.innerHTML = '<i class="fas fa-times-circle"></i> Invalid format. Expected: 2-4 letters + 6-10 digits (e.g., CGVM15320501)';
        input.style.borderColor = '#dc3545';
        input.style.backgroundColor = '#fef2f2';
    }

    // Insert feedback after input
    input.parentNode.appendChild(feedbackDiv);
}

/**
 * Remove OR validation feedback
 */
function removeOrValidationFeedback() {
    const existing = document.getElementById('or-validation-feedback');
    if (existing) {
        existing.remove();
    }
    const input = document.getElementById('receipt_number');
    input.style.borderColor = '';
    input.style.backgroundColor = '';
}

/**
 * Mark form as dirty (has unsaved changes)
 */
function markFormAsDirty() {
    formIsDirty = true;
}

/**
 * Prevent data loss when closing modal
 */
function preventDataLoss(e) {
    // Check if form has data
    const receiptNumber = document.getElementById('receipt_number').value.trim();
    const cashReceived = document.getElementById('cash_received').value.trim();
    const referenceNumber = document.getElementById('reference_number').value.trim();
    const notes = document.getElementById('notes').value.trim();

    const hasData = receiptNumber || cashReceived || referenceNumber || notes;

    if (formIsDirty && hasData) {
        const confirmClose = confirm('You have entered data. Are you sure you want to close and discard it?');
        if (!confirmClose) {
            e.preventDefault();
            return false;
        }
    }

    // Reset dirty state when closing
    formIsDirty = false;
}

/**
 * Retry loading receipt preview
 */
function retryReceiptLoad(receiptNumber, paymentId) {
    showReceiptPreview(receiptNumber, paymentId);
}

/**
 * Zoom receipt preview
 */
let currentZoom = 100;
function zoomReceipt(action) {
    const iframe = document.getElementById('receiptIframe');

    if (action === 'in') {
        currentZoom = Math.min(currentZoom + 10, 150);
    } else if (action === 'out') {
        currentZoom = Math.max(currentZoom - 10, 50);
    } else if (action === 'reset') {
        currentZoom = 100;
    }

    iframe.style.transform = `scale(${currentZoom / 100})`;
    iframe.style.transformOrigin = 'top center';
    iframe.style.transition = 'transform 0.2s ease';
}

/**
 * Proceed without preview (skip directly to print confirmation)
 */
function proceedWithoutPreview(paymentId) {
    // Close preview modal
    const previewModal = bootstrap.Modal.getInstance(document.getElementById('printPreviewModal'));
    if (previewModal) {
        previewModal.hide();
    }

    // Show print confirmation dialog immediately
    setTimeout(() => {
        showPrintConfirmation(paymentId, window.currentReceiptNumber);
    }, 300);
}

/**
 * Utility functions
 */
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function showAlert(message, type) {
    // Remove any existing alert of the same type first
    const existingAlert = document.querySelector(`.alert-${type}`);
    if (existingAlert) {
        existingAlert.remove();
    }

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;

    // Add icon based on type
    let icon = '';
    if (type === 'success') icon = '<i class="fas fa-check-circle"></i> ';
    if (type === 'danger') icon = '<i class="fas fa-exclamation-circle"></i> ';
    if (type === 'warning') icon = '<i class="fas fa-exclamation-triangle"></i> ';
    if (type === 'info') icon = '<i class="fas fa-info-circle"></i> ';

    alertDiv.innerHTML = `
        ${icon}${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);

    // Only auto-dismiss success and info alerts (NOT errors/warnings)
    if (type === 'success' || type === 'info') {
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    // Danger and warning alerts stay until manually dismissed

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
