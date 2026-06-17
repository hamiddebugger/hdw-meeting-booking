/**
 * HDW Meeting Booking Public Scripts
 * Vanilla ES6+ — No jQuery
 *
 * Time-point model
 * ─────────────────
 * The server returns 30-min slots: { start:"08:00", end:"08:30", available:bool }
 * We render ONE button per slot showing only slot.start (the time-point).
 * A final sentinel button shows the last possible end-time (e.g. "20:00").
 *
 * • 1st click → start_time = that time-point
 * • 2nd click → end_time   = that time-point  (must be AFTER start)
 *
 * Example: click "08:00" then click "09:00"  →  start=08:00  end=09:00
 * No ambiguity, no ±30-min drift.
 */

const state = {
    selectedDate : null,
    startTime    : null,   // "HH:MM" string
    endTime      : null,   // "HH:MM" string
    slots        : [],     // raw server data
    isSubmitting : false,
};

const elements = {};

document.addEventListener('DOMContentLoaded', () => {
    cacheElements();
    if (!elements.form) return;
    bindEvents();
});

/* ─── DOM cache ───────────────────────────────────────────────────── */
function cacheElements() {
    elements.container  = document.getElementById('hdw-booking-form');
    if (!elements.container) return;

    elements.form       = elements.container.querySelector('form.hdw-booking-form');
    if (!elements.form) return;

    elements.dateInput      = document.getElementById('hdw_meeting_date');
    elements.slotsWrapper   = document.getElementById('hdw-time-slots-wrapper');
    elements.slotsContainer = document.getElementById('hdw-time-slots');
    elements.startTimeInput = document.getElementById('hdw_start_time');
    elements.endTimeInput   = document.getElementById('hdw_end_time');
    elements.submitBtn      = document.getElementById('hdw-submit-btn');
    elements.messageBox     = document.getElementById('hdw-form-message');
    elements.modal          = document.getElementById('hdw-success-modal');
    elements.modalMessage   = document.getElementById('hdw-modal-message');
    elements.modalClose     = document.getElementById('hdw-modal-close');
    elements.customTimeRow  = document.getElementById('hdw-custom-time');
    elements.customStart    = document.getElementById('hdw_custom_start');
    elements.customEnd      = document.getElementById('hdw_custom_end');
    elements.fullName       = document.getElementById('hdw_full_name');
    elements.mobile         = document.getElementById('hdw_mobile');
    elements.email          = document.getElementById('hdw_email');
    elements.meetingTitle   = document.getElementById('hdw_meeting_title');
}

/* ─── Event binding ───────────────────────────────────────────────── */
function bindEvents() {
    elements.dateInput?.addEventListener('change', handleDateChange);
    elements.form?.addEventListener('submit', handleFormSubmit);
    elements.modalClose?.addEventListener('click', closeModal);
    elements.modal?.addEventListener('click', e => { if (e.target === elements.modal) closeModal(); });
    elements.slotsContainer?.addEventListener('click', handleTimePointClick);
}

/* ─── Date change ─────────────────────────────────────────────────── */
async function handleDateChange(event) {
    const date = event.target.value;
    if (!date) { hideSlots(); return; }

    const sel   = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (sel < today) {
        showMessage(window.hdwPublicData?.strings?.dateRequired || 'Please select a future date.', 'error');
        hideSlots();
        return;
    }

    state.selectedDate = date;
    state.startTime    = null;
    state.endTime      = null;
    clearTimeInputs();
    clearMessage();
    await fetchAvailableSlots(date);
}

/* ─── Fetch slots ─────────────────────────────────────────────────── */
async function fetchAvailableSlots(date) {
    showSlotsLoading();

    const url = new URL(window.hdwPublicData?.ajaxUrl || '/wp-admin/admin-ajax.php');
    url.searchParams.append('action', 'hdw_get_available_slots');
    url.searchParams.append('date', date);

    try {
        const response = await fetch(url, { method: 'GET', credentials: 'same-origin' });
        const data     = await response.json();

        if (data.success && data.data?.slots) {
            state.slots = data.data.slots;
            renderTimePoints(data.data.slots);
        } else {
            showMessage(data.data || 'Failed to load time slots.', 'error');
            hideSlots();
        }
    } catch (err) {
        console.error('HDW slot fetch error:', err);
        showFallbackTimeInputs();
    }
}

/* ─── Render time-point buttons ───────────────────────────────────── */
/**
 * Each slot { start, end, available } contributes ONE button for slot.start.
 * After the last slot we add one sentinel button for the last slot's .end
 * so the user can pick it as an end-time.
 *
 * A button is SELECTABLE only when it can be a valid start OR end:
 *   • As a START  → the slot beginning at this time must be available
 *   • As an END   → the slot ending at this time must be available
 *     (i.e. the slot whose .end equals this time-point)
 */
function renderTimePoints(slots) {
    elements.slotsWrapper.style.display = 'block';
    elements.customTimeRow.style.display = 'none';
    elements.slotsContainer.innerHTML   = '';

    if (!slots.length) {
        elements.slotsContainer.innerHTML = `<div class="hdw-no-slots">${
            window.hdwPublicData?.strings?.noSlots || 'No available time slots for this date.'
        }</div>`;
        return;
    }

    // Instruction
    const instr = document.createElement('p');
    instr.className   = 'hdw-time-instructions';
    instr.textContent = '1st click: start time  |  2nd click: end time';
    elements.slotsContainer.appendChild(instr);

    // Selected-range label
    const label = document.createElement('p');
    label.id        = 'hdw-selection-label';
    label.className = 'hdw-selection-label';
    label.textContent = '';
    elements.slotsContainer.appendChild(label);

    const grid = document.createElement('div');
    grid.className = 'hdw-time-points';

    // Build a quick lookup: which time-points are "available as start"
    // and which are "available as end"
    // available-as-start[t]  = slot.start == t && slot.available
    // available-as-end[t]    = slot.end   == t && slot.available
    const availableAsStart = new Set();
    const availableAsEnd   = new Set();
    slots.forEach(s => {
        if (s.available) {
            availableAsStart.add(s.start);
            availableAsEnd.add(s.end);
        }
    });

    // Collect all unique time-points in order
    const points = [];
    slots.forEach(s => { if (!points.includes(s.start)) points.push(s.start); });
    // sentinel: last slot's end
    const lastEnd = slots[slots.length - 1].end;
    if (!points.includes(lastEnd)) points.push(lastEnd);

    points.forEach(tp => {
        const btn = document.createElement('button');
        btn.type          = 'button';
        btn.className     = 'hdw-time-point';
        btn.dataset.time  = tp;
        btn.textContent   = tp;

        // A point is blocked only if it cannot be used as start OR end at all
        const canBeStart = availableAsStart.has(tp);
        const canBeEnd   = availableAsEnd.has(tp);

        if (!canBeStart && !canBeEnd) {
            btn.classList.add('hdw-tp-blocked');
            btn.disabled  = true;
            btn.title     = 'Not available';
        }

        grid.appendChild(btn);
    });

    elements.slotsContainer.appendChild(grid);
}

/* ─── Time-point click handler ────────────────────────────────────── */
function handleTimePointClick(event) {
    const btn = event.target.closest('.hdw-time-point');
    if (!btn || btn.disabled) return;

    const tp = btn.dataset.time;

    if (state.startTime === null) {
        // ── Phase 1: pick start ──────────────────────────────────────
        // Must be a valid start-of-slot time
        const validStart = state.slots.some(s => s.start === tp && s.available);
        if (!validStart) {
            showMessage('Please select an available start time.', 'error');
            return;
        }
        state.startTime = tp;
        state.endTime   = null;
        clearTimeInputs();
        updateTimePointVisuals();
        updateSelectionLabel();

    } else if (state.endTime === null) {
        // ── Phase 2: pick end ────────────────────────────────────────
        if (tp === state.startTime) {
            // Same point → cancel selection
            state.startTime = null;
            updateTimePointVisuals();
            updateSelectionLabel();
            clearTimeInputs();
            return;
        }

        if (tp < state.startTime) {
            // Clicked before start → restart selection from this point
            const validStart = state.slots.some(s => s.start === tp && s.available);
            if (!validStart) { showMessage('Please select an available start time.', 'error'); return; }
            state.startTime = tp;
            state.endTime   = null;
            clearTimeInputs();
            updateTimePointVisuals();
            updateSelectionLabel();
            return;
        }

        // Validate: every 30-min slot between startTime and tp must be available
        if (!isRangeAvailable(state.startTime, tp)) {
            showMessage('Selected range includes reserved slots. Please choose a different range.', 'error');
            return;
        }

        state.endTime = tp;
        setTimeInputs(state.startTime, state.endTime);
        updateTimePointVisuals();
        updateSelectionLabel();

    } else {
        // ── Phase 3: new selection ───────────────────────────────────
        const validStart = state.slots.some(s => s.start === tp && s.available);
        if (!validStart) { showMessage('Please select an available start time.', 'error'); return; }
        state.startTime = tp;
        state.endTime   = null;
        clearTimeInputs();
        updateTimePointVisuals();
        updateSelectionLabel();
    }
}

/* ─── Range validation ────────────────────────────────────────────── */
/**
 * All 30-min slots that fall within [startTime, endTime) must be available.
 */
function isRangeAvailable(startTime, endTime) {
    const covered = state.slots.filter(s => s.start >= startTime && s.end <= endTime);
    if (!covered.length) return false;
    return covered.every(s => s.available);
}

/* ─── Visual updates ──────────────────────────────────────────────── */
function updateTimePointVisuals() {
    const buttons = elements.slotsContainer.querySelectorAll('.hdw-time-point');

    buttons.forEach(btn => {
        const tp = btn.dataset.time;
        btn.classList.remove('hdw-tp-start', 'hdw-tp-end', 'hdw-tp-in-range');

        if (!state.startTime) return;

        if (tp === state.startTime) {
            btn.classList.add('hdw-tp-start');
        } else if (state.endTime && tp === state.endTime) {
            btn.classList.add('hdw-tp-end');
        } else if (state.endTime && tp > state.startTime && tp < state.endTime) {
            btn.classList.add('hdw-tp-in-range');
        }
    });
}

function updateSelectionLabel() {
    const label = document.getElementById('hdw-selection-label');
    if (!label) return;

    if (state.startTime && state.endTime) {
        label.textContent = `Selected: ${state.startTime} → ${state.endTime}`;
        label.className   = 'hdw-selection-label hdw-selection-complete';
    } else if (state.startTime) {
        label.textContent = `Start: ${state.startTime} — now click an end time`;
        label.className   = 'hdw-selection-label hdw-selection-partial';
    } else {
        label.textContent = '';
        label.className   = 'hdw-selection-label';
    }
}

/* ─── Helpers ─────────────────────────────────────────────────────── */
function showSlotsLoading() {
    elements.slotsWrapper.style.display = 'block';
    elements.customTimeRow.style.display = 'none';
    elements.slotsContainer.innerHTML   = '<div class="hdw-slots-loading">Loading available times…</div>';
}

function hideSlots() {
    elements.slotsWrapper.style.display = 'none';
    elements.slotsContainer.innerHTML   = '';
}

function showFallbackTimeInputs() {
    elements.slotsWrapper.style.display = 'none';
    elements.customTimeRow.style.display = 'flex';
}

function setTimeInputs(start, end) {
    elements.startTimeInput.value = start;
    elements.endTimeInput.value   = end;
}

function clearTimeInputs() {
    elements.startTimeInput.value = '';
    elements.endTimeInput.value   = '';
}

/* ─── Form submission ─────────────────────────────────────────────── */
async function handleFormSubmit(event) {
    event.preventDefault();
    if (state.isSubmitting) return;

    clearMessage();

    // If in fallback mode, sync custom inputs → hidden inputs before validation
    if (elements.customTimeRow && elements.customTimeRow.style.display !== 'none') {
        if (elements.customStart?.value) elements.startTimeInput.value = elements.customStart.value;
        if (elements.customEnd?.value)   elements.endTimeInput.value   = elements.customEnd.value;
    }

    const validation = validateForm();
    if (!validation.valid) { showMessage(validation.error, 'error'); return; }

    state.isSubmitting = true;
    setSubmitLoading(true);

    const formData = new FormData(elements.form);
    formData.append('action', 'hdw_submit_reservation');

    if (elements.customTimeRow.style.display !== 'none') {
        formData.set('start_time', elements.customStart.value);
        formData.set('end_time',   elements.customEnd.value);
    }

    try {
        const response = await fetch(window.hdwPublicData?.ajaxUrl || '/wp-admin/admin-ajax.php', {
            method: 'POST', credentials: 'same-origin', body: formData,
        });
        const data = await response.json();

        if (data.success) {
            showSuccessModal(data.data?.message || 'Reservation submitted successfully!');
            resetForm();
        } else {
            showMessage(data.data || 'An error occurred.', 'error');
        }
    } catch (err) {
        console.error('HDW submit error:', err);
        showMessage(window.hdwPublicData?.strings?.generalError || 'An error occurred.', 'error');
    } finally {
        state.isSubmitting = false;
        setSubmitLoading(false);
    }
}

/* ─── Validation ──────────────────────────────────────────────────── */
function validateForm() {
    const s = window.hdwPublicData?.strings || {};

    if (!elements.fullName?.value?.trim() || elements.fullName.value.trim().length < 2)
        return { valid: false, error: s.nameRequired    || 'Full name is required.' };

    if (!elements.mobile?.value?.trim() || !isValidMobile(elements.mobile.value))
        return { valid: false, error: s.mobileRequired  || 'Valid mobile number is required.' };

    if (!elements.email?.value?.trim() || !isValidEmail(elements.email.value))
        return { valid: false, error: s.emailRequired   || 'Valid email is required.' };

    if (!elements.meetingTitle?.value?.trim() || elements.meetingTitle.value.trim().length < 2)
        return { valid: false, error: s.titleRequired   || 'Meeting title is required.' };

    if (!elements.dateInput?.value)
        return { valid: false, error: s.dateRequired    || 'Please select a meeting date.' };

    const startTime = elements.startTimeInput?.value || elements.customStart?.value;
    const endTime   = elements.endTimeInput?.value   || elements.customEnd?.value;

    if (!startTime || !endTime)
        return { valid: false, error: s.selectTime      || 'Please select start and end times.' };

    if (startTime >= endTime)
        return { valid: false, error: s.timeInvalid     || 'End time must be after start time.' };

    if (timeToMinutes(endTime) - timeToMinutes(startTime) < 15)
        return { valid: false, error: s.minDuration     || 'Meeting must be at least 15 minutes.' };

    return { valid: true };
}

function isValidMobile(mobile) {
    return /^(09\d{9}|\+98\d{10}|0\d{10})$/.test(mobile.replace(/\s/g, ''));
}
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
function timeToMinutes(time) {
    const [h, m] = time.split(':').map(Number);
    return h * 60 + m;
}

/* ─── Submit button state ─────────────────────────────────────────── */
function setSubmitLoading(isLoading) {
    if (!elements.submitBtn) return;
    if (isLoading) {
        elements.submitBtn.disabled = true;
        elements.submitBtn.dataset.originalText = elements.submitBtn.textContent;
        elements.submitBtn.textContent = window.hdwPublicData?.strings?.submitting || 'Submitting…';
    } else {
        elements.submitBtn.disabled   = false;
        elements.submitBtn.textContent = elements.submitBtn.dataset.originalText
            || window.hdwPublicData?.strings?.submit || 'Submit Reservation';
    }
}

/* ─── Messages & modal ────────────────────────────────────────────── */
function showMessage(message, type) {
    if (!elements.messageBox) return;
    elements.messageBox.textContent = message;
    elements.messageBox.className   = `hdw-form-message hdw-message-${type}`;
    elements.messageBox.style.display = 'block';
    if (type === 'success') setTimeout(clearMessage, 5000);
}
function clearMessage() {
    if (!elements.messageBox) return;
    elements.messageBox.textContent  = '';
    elements.messageBox.className    = 'hdw-form-message';
    elements.messageBox.style.display = 'none';
}
function showSuccessModal(message) {
    if (elements.modalMessage) elements.modalMessage.textContent = message;
    if (elements.modal)        elements.modal.style.display = 'flex';
}
function closeModal() {
    if (elements.modal) elements.modal.style.display = 'none';
}

/* ─── Reset ───────────────────────────────────────────────────────── */
function resetForm() {
    elements.form?.reset();
    state.selectedDate = null;
    state.startTime    = null;
    state.endTime      = null;
    state.slots        = [];
    clearTimeInputs();
    hideSlots();
}
