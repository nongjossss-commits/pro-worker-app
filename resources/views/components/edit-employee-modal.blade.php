{{--
  Reusable Edit Employee Modal — opens employee edit page in an iframe.
  After save: closes modal, refreshes day appointments, scrolls to the edited card.

  Usage:
    Place once in your view: <x-edit-employee-modal />
    Trigger via JS: window.openEditEmployeeModal(employeeId)
--}}
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-scrollable" style="max-width: 95vw;">
        <div class="modal-content" style="height: 95vh;">
            <div class="modal-header py-2">
                <h5 class="modal-title fw-bold" id="editEmployeeModalLabel">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>{{ __('Edit Employee') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 position-relative" style="overflow: hidden;">
                {{-- Loading spinner --}}
                <div id="edit-employee-modal-loading"
                     class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-white"
                     style="z-index: 10;">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 small text-muted">{{ __('Loading...') }}</p>
                    </div>
                </div>
                {{-- iframe for the edit form --}}
                <iframe id="edit-employee-iframe"
                        src="about:blank"
                        style="width: 100%; height: 100%; border: 0;"
                        title="Edit Employee"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const SAVE_SIGNAL_PATH = '/employees/edit-modal-saved';
    let lastEditedEmployeeId = null;

    /**
     * Open the edit employee modal.
     * @param {number|string} employeeId - the employee ID to edit
     */
    window.openEditEmployeeModal = function(employeeId) {
        const modalEl = document.getElementById('editEmployeeModal');
        if (!modalEl) {
            console.error('[EditEmployeeModal] Modal element not found.');
            return;
        }

        const iframe = document.getElementById('edit-employee-iframe');
        const loading = document.getElementById('edit-employee-modal-loading');

        lastEditedEmployeeId = employeeId;

        // Show loading spinner before iframe loads
        loading.style.display = 'flex';

        // Build URL with return signal — after save, form redirects to this URL,
        // which we detect to know save was successful
        const editUrl = `/employees/${employeeId}/edit?_previous=${encodeURIComponent(SAVE_SIGNAL_PATH)}&modal=1`;
        iframe.src = editUrl;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    };

    // --- Iframe onload handler ---
    document.addEventListener('DOMContentLoaded', function() {
        const iframe = document.getElementById('edit-employee-iframe');
        const loading = document.getElementById('edit-employee-modal-loading');
        if (!iframe || !loading) return;

        iframe.addEventListener('load', function() {
            loading.style.display = 'none';

            try {
                const currentPath = iframe.contentWindow.location.pathname;

                // Detect save: iframe redirected to our signal path
                if (currentPath.includes('edit-modal-saved') || currentPath === SAVE_SIGNAL_PATH) {
                    handleSaveSuccess();
                }
            } catch(e) {
                // Cross-origin error — iframe loaded an external page (shouldn't happen)
            }
        });
    });

    function handleSaveSuccess() {
        const modalEl = document.getElementById('editEmployeeModal');
        const modal = bootstrap.Modal.getInstance(modalEl);

        // Close modal
        if (modal) modal.hide();

        // Show success toast
        if (typeof showToast === 'function') {
            showToast('{{ __("Employee updated successfully.") }}', 'success');
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '{{ __("Employee updated successfully.") }}',
                showConfirmButton: false,
                timer: 2500
            });
        }

        const empId = lastEditedEmployeeId;

        // Capture state to restore once the day appointments list reloads.
        // openDay() resets searchQuery to '', so we save it here and put it
        // back after the new list is in the DOM — keeping the user's filter,
        // selected date, and visual position intact.
        const calendarScope = getCalendarScope();
        const prevSearchQuery = (calendarScope && typeof calendarScope.searchQuery === 'string') ? calendarScope.searchQuery : '';
        const prevPageScrollY = window.scrollY;

        // Trigger refresh of the day appointments list (Alpine fetches fresh HTML)
        const refreshed = refreshDayAppointments();

        if (!refreshed) {
            // No calendar on this page — fall back to a soft scroll-to-card
            findCardForEmployee(empId).then(card => {
                if (card) scrollToCardAndHighlight(card);
            });
            return;
        }

        // Wait for the freshly-rendered card to appear (the fetch + Alpine
        // re-render is variable in latency, so polling is safer than a
        // fixed setTimeout). Scroll first, then restore the search filter
        // so the user gets a visual confirmation before the filter (which
        // may hide the card) reapplies.
        findCardForEmployee(empId, 6000).then(card => {
            if (card) {
                scrollToCardAndHighlight(card);
            } else {
                // Card never appeared — at least restore the page scroll
                // the user had before so the world doesn't jump.
                window.scrollTo({ top: prevPageScrollY, behavior: 'auto' });
            }

            // Restore the search filter — Alpine's $watch will re-filter cards.
            // Setting it back after the list is repopulated is essential because
            // openDay() unconditionally clears searchQuery.
            if (calendarScope && prevSearchQuery) {
                try { calendarScope.searchQuery = prevSearchQuery; } catch(_) {}
            }
        });
    }

    function getCalendarScope() {
        try {
            const root = document.querySelector('[x-data="calendarApp()"]');
            if (root && window.Alpine) return window.Alpine.$data(root);
        } catch(_) {}
        return null;
    }

    function refreshDayAppointments() {
        const scope = getCalendarScope();
        if (scope && scope.selectedDate && typeof scope.openDay === 'function') {
            scope.openDay(scope.selectedDate);
            return true;
        }
        return false;
    }

    function findCardForEmployee(empId, timeoutMs = 0) {
        // Promise-based polling: resolves with the card element as soon as it
        // appears in the DOM, or null if the timeout expires first.
        return new Promise((resolve) => {
            if (!empId) return resolve(null);

            const selectors = [
                `[data-employee-id="${empId}"]`,
                `#employee-card-${empId}`,
                `.employee-checkbox[value="${empId}"]`
            ];

            const find = () => {
                for (const sel of selectors) {
                    const el = document.querySelector(sel);
                    if (el) return el.closest('.appt-card-item, .employee-card-wrapper, .employee-card-outer') || el;
                }
                return null;
            };

            const immediate = find();
            if (immediate || timeoutMs <= 0) return resolve(immediate);

            const start = Date.now();
            const tick = () => {
                const card = find();
                if (card) return resolve(card);
                if (Date.now() - start >= timeoutMs) return resolve(null);
                setTimeout(tick, 100);
            };
            setTimeout(tick, 100);
        });
    }

    function scrollToCardAndHighlight(card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.style.transition = 'background-color 0.5s ease';
        const orig = card.style.backgroundColor;
        card.style.backgroundColor = '#fff7ed';
        setTimeout(() => { card.style.backgroundColor = orig; }, 1500);
    }

    // Reset iframe when modal closes (free memory)
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('editEmployeeModal');
        if (!modalEl) return;
        modalEl.addEventListener('hidden.bs.modal', function() {
            const iframe = document.getElementById('edit-employee-iframe');
            if (iframe) iframe.src = 'about:blank';
        });
    });
})();
</script>
