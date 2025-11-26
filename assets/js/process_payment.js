/**
 * Process Payment JavaScript
 * Handles payment processing modal and cash/change calculations
 */

let paymentModal;
let currentCitation = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal
    paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));

    // Payment method change event
    document.getElementById('payment_method').addEventListener('change', handlePaymentMethodChange);

    // Cash received input event - calculate change
    document.getElementById('cash_received').addEventListener('input', calculateChange);

    // Payment form submit
    document.getElementById('paymentForm').addEventListener('submit', handlePaymentSubmit);
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

    // Validation for cash payments
    if (paymentMethod === 'cash' && cashReceived < amountDue) {
        showAlert('Cash received is less than the amount due!', 'danger');
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
            showAlert('Payment successful! Receipt Number: ' + data.receipt_number, 'success');
            paymentModal.hide();

            // Automatically open receipt in new window
            if (data.receipt_number) {
                window.open('/vlad/public/receipt.php?receipt=' + data.receipt_number, '_blank');
            }

            // Reload page after 2 seconds
            setTimeout(() => {
                location.reload();
            }, 2000);
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
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
