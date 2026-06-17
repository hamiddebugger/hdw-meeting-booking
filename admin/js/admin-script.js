/**
 * HDW Meeting Booking Admin Scripts
 * Uses vanilla ES6+ JavaScript (no jQuery)
 */

document.addEventListener('DOMContentLoaded', () => {
    initializeActionButtons();
});

/**
 * Initialize approve/reject action buttons
 */
function initializeActionButtons() {
    const tableContainer = document.querySelector('.hdw-table-container');
    if (!tableContainer) return;

    tableContainer.addEventListener('click', handleActionClick);
}

/**
 * Handle action button clicks using event delegation
 */
function handleActionClick(event) {
    const button = event.target.closest('.hdw-btn-approve, .hdw-btn-reject, .hdw-btn-pending');
    if (!button) return;

    event.preventDefault();

    const action        = button.dataset.action;
    const reservationId = button.dataset.id;

    if (!action || !reservationId) return;

    const messages = {
        approve : window.hdwAdminData?.strings?.confirmApprove  || 'Approve this reservation?',
        reject  : window.hdwAdminData?.strings?.confirmReject   || 'Reject this reservation?',
        pending : window.hdwAdminData?.strings?.confirmPending  || 'Reset this reservation to pending?',
    };

    if (!confirm(messages[action] || 'Are you sure?')) return;

    updateReservationStatus(reservationId, action, button);
}

/**
 * Update reservation status via AJAX
 */
async function updateReservationStatus(reservationId, status, button) {
    const row = button.closest('tr');
    setRowLoading(row, true);

    const formData = new FormData();
    formData.append('action', 'hdw_update_reservation_status');
    formData.append('reservation_id', reservationId);
    formData.append('status', status);
    formData.append('_wpnonce', window.hdwAdminData?.nonce || '');

    try {
        const response = await fetch(window.hdwAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            updateRowStatus(row, data.data?.status || status, data.data?.room_name ?? null);
            showNotification(data.data?.message || 'Status updated successfully.', 'success');
        } else {
            showNotification(data.data || 'An error occurred.', 'error');
        }
    } catch (error) {
        console.error('HDW Admin Error:', error);
        showNotification(window.hdwAdminData?.strings?.error || 'An error occurred.', 'error');
    } finally {
        setRowLoading(row, false);
    }
}

/**
 * Set loading state on a table row
 */
function setRowLoading(row, isLoading) {
    if (isLoading) {
        row.classList.add('hdw-loading');
        row.style.opacity = '0.5';
    } else {
        row.classList.remove('hdw-loading');
        row.style.opacity = '1';
    }
}

/**
 * Update row display after status change.
 * Also updates the room cell if the room was changed during approval.
 */
function updateRowStatus(row, newStatus, newRoomName = null) {
    const reservationId = row.dataset.reservationId;
    const statusCell    = row.querySelector('.hdw-status-badge');
    const actionsCell   = row.querySelector('.hdw-action-buttons');
    const roomCell      = row.querySelector('.hdw-room-badge');

    const statusLabels  = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected' };
    const statusClasses = { pending: 'hdw-status-pending', approved: 'hdw-status-approved', rejected: 'hdw-status-rejected' };

    if (statusCell) {
        statusCell.className   = 'hdw-status-badge ' + (statusClasses[newStatus] || '');
        statusCell.textContent = statusLabels[newStatus] || capitalizeFirst(newStatus);
    }

    // Update room name if it changed during approval
    if (roomCell && newRoomName) {
        roomCell.className   = 'hdw-room-badge';
        roomCell.textContent = newRoomName;
    }

    if (actionsCell) {
        let html = '';
        if (newStatus !== 'approved') {
            html += `<button type="button" class="button button-small hdw-btn-approve"
                        data-action="approve" data-id="${escapeHtml(reservationId)}">Approve</button>`;
        }
        if (newStatus !== 'rejected') {
            html += `<button type="button" class="button button-small hdw-btn-reject"
                        data-action="reject" data-id="${escapeHtml(reservationId)}">Reject</button>`;
        }
        if (newStatus !== 'pending') {
            html += `<button type="button" class="button button-small hdw-btn-pending"
                        data-action="pending" data-id="${escapeHtml(reservationId)}">Set Pending</button>`;
        }
        actionsCell.innerHTML = html;
    }
}

/**
 * Show notification message
 */
function showNotification(message, type = 'success') {
    const existingNotice = document.querySelector('.hdw-admin-notice');
    if (existingNotice) {
        existingNotice.remove();
    }

    const notice = document.createElement('div');
    notice.className = `notice notice-${type === 'success' ? 'success' : 'error'} is-dismissible hdw-admin-notice`;
    notice.innerHTML = `<p>${escapeHtml(message)}</p>`;

    const wrap = document.querySelector('.hdw-admin-wrap');
    if (wrap) {
        wrap.insertBefore(notice, wrap.firstChild);
    }

    setTimeout(() => {
        notice.style.opacity = '0';
        notice.style.transition = 'opacity 0.5s';
        setTimeout(() => notice.remove(), 500);
    }, 4000);
}

/**
 * Capitalize first letter
 */
function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Escape HTML special characters
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
