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

        // Refresh the day appointments list (Alpine calendar) and scroll
        const empId = lastEditedEmployeeId;
        setTimeout(() => {
            // Try to refresh the current day if calendar is available
            try {
                const calendarRoot = document.querySelector('[x-data="calendarApp()"]');
                if (calendarRoot && window.Alpine) {
                    const scope = window.Alpine.$data(calendarRoot);
                    if (scope && scope.selectedDate && typeof scope.openDay === 'function') {
                        scope.openDay(scope.selectedDate);
                    }
                }
            } catch(e) {
                // Fallback: just scroll
            }

            // Scroll to the edited employee card after content reloads
            setTimeout(() => scrollToEmployeeCard(empId), 500);
        }, 100);
    }

    function scrollToEmployeeCard(empId) {
        if (!empId) return;
        // Try multiple selectors used across the app
        const selectors = [
            `[data-employee-id="${empId}"]`,
            `#employee-card-${empId}`,
            `.employee-checkbox[value="${empId}"]`
        ];
        for (const sel of selectors) {
            const el = document.querySelector(sel);
            if (el) {
                const card = el.closest('.appt-card-item, .employee-card-wrapper, .employee-card-outer') || el;
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Highlight briefly
                card.style.transition = 'background-color 0.5s ease';
                const orig = card.style.backgroundColor;
                card.style.backgroundColor = '#fff7ed';
                setTimeout(() => { card.style.backgroundColor = orig; }, 1500);
                return;
            }
        }
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
