/**
 * Citation List JavaScript
 * Handles all client-side logic for the citations listing page
 */

// Search on enter
document.querySelector('#searchForm input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        this.form.submit();
    }
});

// Filter by status
function filterByStatus(status) {
    const url = new URL(window.location);
    url.searchParams.set('status', status);
    url.searchParams.set('page', '1');
    window.location = url;
}

// Current citation ID for status updates
let currentCitationId = null;

// View citation
function viewCitation(id) {
    currentCitationId = id;
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    modal.show();

    fetch(`../api/citation_get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayCitationDetails(data.citation);
                document.getElementById('editFromViewBtn').onclick = () => editCitation(id);
            } else {
                document.getElementById('viewModalContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('viewModalContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Failed to load citation details.
                </div>
            `;
        });
}

function displayCitationDetails(citation) {
    const html = `
        <div class="section-title"><i class="fas fa-ticket-alt"></i> Citation Information</div>
        <table class="detail-table">
            <tr>
                <th>Ticket Number</th>
                <td><strong>${citation.ticket_number}</strong></td>
            </tr>
            <tr>
                <th>Date/Time</th>
                <td>${new Date(citation.apprehension_datetime).toLocaleString()}</td>
            </tr>
            <tr>
                <th>Place of Apprehension</th>
                <td>${citation.place_of_apprehension}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td><span class="badge badge-${citation.status}">${citation.status.toUpperCase()}</span></td>
            </tr>
        </table>

        <div class="section-title"><i class="fas fa-user"></i> Driver Information</div>
        <table class="detail-table">
            <tr>
                <th>Full Name</th>
                <td>${citation.last_name}, ${citation.first_name} ${citation.middle_initial || ''} ${citation.suffix || ''}</td>
            </tr>
            <tr>
                <th>Age</th>
                <td>${citation.age || 'N/A'}</td>
            </tr>
            <tr>
                <th>Address</th>
                <td>${citation.zone ? 'Zone ' + citation.zone + ', ' : ''}${citation.barangay}, ${citation.municipality}, ${citation.province}</td>
            </tr>
            <tr>
                <th>License Number</th>
                <td>${citation.license_number || 'N/A'}</td>
            </tr>
            <tr>
                <th>License Type</th>
                <td>${citation.license_type || 'N/A'}</td>
            </tr>
        </table>

        <div class="section-title"><i class="fas fa-car"></i> Vehicle Information</div>
        <table class="detail-table">
            <tr>
                <th>Plate/MV/Engine/Chassis No.</th>
                <td>${citation.plate_mv_engine_chassis_no}</td>
            </tr>
            <tr>
                <th>Vehicle Type</th>
                <td>${citation.vehicle_type || 'N/A'}</td>
            </tr>
            <tr>
                <th>Vehicle Description</th>
                <td>${citation.vehicle_description || 'N/A'}</td>
            </tr>
        </table>

        <div class="section-title"><i class="fas fa-exclamation-triangle"></i> Violations</div>
        <table class="detail-table">
            <thead>
                <tr>
                    <th>Violation Type</th>
                    <th>Offense #</th>
                    <th>Fine Amount</th>
                </tr>
            </thead>
            <tbody>
                ${citation.violations.map(v => `
                    <tr>
                        <td>${v.violation_type}</td>
                        <td>${v.offense_count}</td>
                        <td>P${parseFloat(v.fine_amount).toFixed(2)}</td>
                    </tr>
                `).join('')}
                <tr>
                    <th colspan="2" class="text-end">Total Fine:</th>
                    <td><strong>P${parseFloat(citation.total_fine).toFixed(2)}</strong></td>
                </tr>
            </tbody>
        </table>
        <table class="detail-table mt-2">
            <tr>
                <th>Apprehension Officer</th>
                <td>${citation.apprehension_officer || 'N/A'}</td>
            </tr>
        </table>

        ${citation.remarks ? `
            <div class="section-title"><i class="fas fa-comment"></i> Remarks</div>
            <div class="remarks-box">${citation.remarks}</div>
        ` : ''}
    `;

    document.getElementById('viewModalContent').innerHTML = html;
}

// Edit citation
function editCitation(id) {
    window.location.href = `edit_citation.php?id=${id}`;
}

// Delete citation - requires CSRF token from page
function deleteCitation(id) {
    if (confirm('Are you sure you want to delete this citation? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('citation_id', id);
        // CSRF token will be passed from the PHP page
        const csrfToken = document.querySelector('[data-csrf-token]').getAttribute('data-csrf-token');
        formData.append('csrf_token', csrfToken);

        fetch('../api/citation_delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Citation deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Failed to delete citation.');
        });
    }
}

// Export CSV
function exportCSV() {
    const searchParam = new URLSearchParams(window.location.search).get('search') || '';
    const statusParam = new URLSearchParams(window.location.search).get('status') || '';
    window.location.href = `../api/citations_export.php?search=${encodeURIComponent(searchParam)}&status=${encodeURIComponent(statusParam)}`;
}

// Print citation
function printCitation() {
    window.print();
}

// Status update functions
function openStatusModal(newStatus) {
    if (!currentCitationId) {
        alert('No citation selected');
        return;
    }

    const statusMessages = {
        'paid': 'You are about to mark this citation as <strong>PAID</strong>. This indicates the violator has settled the fine.',
        'contested': 'You are about to mark this citation as <strong>CONTESTED</strong>. This indicates the violator is disputing the citation.',
        'dismissed': 'You are about to <strong>DISMISS</strong> this citation. This removes the violation without payment.',
        'void': 'You are about to <strong>VOID</strong> this citation. This permanently invalidates the citation.',
        'pending': 'You are about to reset this citation to <strong>PENDING</strong> status.'
    };

    document.getElementById('statusCitationId').value = currentCitationId;
    document.getElementById('newStatus').value = newStatus;
    document.getElementById('statusMessage').innerHTML = statusMessages[newStatus] || 'Update citation status.';
    document.querySelector('#statusForm textarea[name="reason"]').value = '';

    // Change alert color based on status
    const alertBox = document.getElementById('statusAlertInfo');
    alertBox.className = 'alert';
    if (newStatus === 'void' || newStatus === 'dismissed') {
        alertBox.classList.add('alert-warning');
    } else if (newStatus === 'paid') {
        alertBox.classList.add('alert-success');
    } else {
        alertBox.classList.add('alert-info');
    }

    const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    statusModal.show();
}

// Quick status update from table
function quickStatusUpdate(citationId, newStatus) {
    currentCitationId = citationId;
    openStatusModal(newStatus);
}

// Quick info modal
function quickInfo(id) {
    currentCitationId = id;
    const modal = new bootstrap.Modal(document.getElementById('quickInfoModal'));
    modal.show();

    fetch(`../api/citation_get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayQuickInfo(data.citation);
                document.getElementById('viewFullDetailsBtn').onclick = () => {
                    bootstrap.Modal.getInstance(document.getElementById('quickInfoModal')).hide();
                    viewCitation(id);
                };
            } else {
                document.getElementById('quickInfoContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('quickInfoContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> Failed to load citation info.
                </div>
            `;
        });
}

function displayQuickInfo(citation) {
    const violationsList = citation.violations.map(v =>
        `<li class="list-group-item d-flex justify-content-between align-items-center">
            <span>${v.violation_type}</span>
            <span class="badge bg-danger">P${parseFloat(v.fine_amount).toFixed(2)}</span>
        </li>`
    ).join('');

    const html = `
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0"><i class="fas fa-user"></i> ${citation.last_name}, ${citation.first_name} ${citation.middle_initial || ''}</h6>
                <span class="badge badge-${citation.status}">${citation.status.toUpperCase()}</span>
            </div>
            <small class="text-muted">
                <i class="fas fa-ticket-alt"></i> Ticket: <strong>${citation.ticket_number}</strong>
            </small>
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <small class="text-muted d-block">Age</small>
                <strong>${citation.age || 'N/A'}</strong>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">License #</small>
                <strong>${citation.license_number || 'N/A'}</strong>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <small class="text-muted d-block">Vehicle</small>
                <strong>${citation.plate_mv_engine_chassis_no}</strong>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">Date</small>
                <strong>${new Date(citation.apprehension_datetime).toLocaleDateString()}</strong>
            </div>
        </div>

        <div class="mb-3">
            <small class="text-muted d-block mb-1"><i class="fas fa-exclamation-triangle"></i> Violations (${citation.violations.length})</small>
            <ul class="list-group list-group-flush">
                ${violationsList}
            </ul>
        </div>

        <div class="alert alert-warning mb-0 py-2">
            <div class="d-flex justify-content-between align-items-center">
                <strong>Total Fine:</strong>
                <span class="fs-5 fw-bold">P${parseFloat(citation.total_fine).toFixed(2)}</span>
            </div>
        </div>
    `;

    document.getElementById('quickInfoContent').innerHTML = html;
}

// Confirm status update
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmStatusBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            const formData = new FormData(document.getElementById('statusForm'));

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            fetch('../api/citation_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    // Close both modals and reload
                    const statusModalEl = document.getElementById('statusModal');
                    const viewModalEl = document.getElementById('viewModal');

                    if (statusModalEl) {
                        const statusModalInstance = bootstrap.Modal.getInstance(statusModalEl);
                        if (statusModalInstance) statusModalInstance.hide();
                    }
                    if (viewModalEl) {
                        const viewModalInstance = bootstrap.Modal.getInstance(viewModalEl);
                        if (viewModalInstance) viewModalInstance.hide();
                    }
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    if (data.new_csrf_token) {
                        document.querySelector('#statusForm input[name="csrf_token"]').value = data.new_csrf_token;
                    }
                }
            })
            .catch(error => {
                alert('Failed to update status: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-check"></i> Confirm';
            });
        });
    }
});
