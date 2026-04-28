{{--
  Reusable Edit Employee Modal — fetches the employee edit form via AJAX
  and renders it inline (no iframe). After save, refreshes the day
  appointments calendar and scrolls to the edited card.

  Usage:
    Place once in your view:        <x-edit-employee-modal />
    Also include on the same page:  <x-cropper-modal />
                                    @include('employees.partials._edit_scripts')
    Trigger via JS:                 window.openEditEmployeeModal(employeeId)
--}}
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fw-bold" id="editEmployeeModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>{{ __('Edit Employee') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light" id="editEmployeeModalBody">
                <div class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let lastEditedEmployeeId = null;

    /**
     * Open the edit employee modal.
     * Fetches the form partial via AJAX (X-Requested-With: XMLHttpRequest)
     * so the server returns the form-only partial (employees.partials._edit_form)
     * instead of the full layout.
     *
     * @param {number|string} employeeId
     */
    window.openEditEmployeeModal = function(employeeId) {
        const modalEl = document.getElementById('editEmployeeModal');
        const modalBody = document.getElementById('editEmployeeModalBody');
        if (!modalEl || !modalBody) {
            console.error('[EditEmployeeModal] Modal elements not found.');
            return;
        }

        lastEditedEmployeeId = employeeId;

        // Reset and show spinner immediately
        modalBody.innerHTML = `
            <div class="d-flex justify-content-center align-items-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">{{ __('Loading...') }}</span>
                </div>
            </div>
        `;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        fetch(`/employees/${employeeId}/edit`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            credentials: 'same-origin'
        })
        .then(res => {
            if (!res.ok) throw new Error('Failed to load edit form (' + res.status + ')');
            return res.text();
        })
        .then(html => {
            modalBody.innerHTML = html;

            // Re-initialize any scripts that need to bind to the freshly-injected
            // form (cropper, datepickers, etc.). Defined in
            // resources/views/employees/partials/_edit_scripts.blade.php.
            if (typeof window.initEmployeeEditForm === 'function') {
                try { window.initEmployeeEditForm(); } catch(e) { console.error(e); }
            }

            // Wire up the form for AJAX submit
            const form = modalBody.querySelector('form');
            if (form) {
                // Cancel button inside the form should just close the modal
                const cancelBtn = form.querySelector('.btn-cancel-edit');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', () => modal.hide());
                }
                form.addEventListener('submit', handleEditFormSubmit);
            }
        })
        .catch(err => {
            modalBody.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>${err.message}
                </div>
            `;
        });
    };

    function handleEditFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerText : '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerText = '{{ __("Saving...") }}';
        }

        // Clear previous validation errors
        form.querySelectorAll('.alert-danger.ajax-error').forEach(el => el.remove());

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST', // _method=PUT inside the FormData
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: formData
        })
        .then(response => {
            if (response.status === 422) {
                return response.json().then(data => {
                    const errors = data.errors ? Object.values(data.errors).flat() : [data.message || 'Validation failed'];
                    throw new Error(errors.join('\n'));
                });
            }
            if (!response.ok) {
                return response.json().catch(() => ({}))
                    .then(data => { throw new Error(data.message || ('Server error ' + response.status)); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                handleSaveSuccess();
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        })
        .catch(err => {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger ajax-error mt-3';
            errorDiv.style.whiteSpace = 'pre-line';
            errorDiv.innerHTML = `<strong>{{ __('Error') }}:</strong><br>${err.message}`;
            form.prepend(errorDiv);
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        });
    }

    function handleSaveSuccess() {
        const modalEl = document.getElementById('editEmployeeModal');
        const modal = bootstrap.Modal.getInstance(modalEl);

        if (modal) modal.hide();

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

        const refreshed = refreshDayAppointments();

        if (!refreshed) {
            findCardForEmployee(empId).then(card => {
                if (card) scrollToCardAndHighlight(card);
            });
            return;
        }

        // Wait for the freshly-rendered card to appear, then scroll +
        // highlight, then restore the search filter.
        findCardForEmployee(empId, 6000).then(card => {
            if (card) {
                scrollToCardAndHighlight(card);
            } else {
                window.scrollTo({ top: prevPageScrollY, behavior: 'auto' });
            }
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

    // Reset modal body on close to free DOM and avoid stale form state
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('editEmployeeModal');
        if (!modalEl) return;
        modalEl.addEventListener('hidden.bs.modal', function() {
            const modalBody = document.getElementById('editEmployeeModalBody');
            if (modalBody) {
                modalBody.innerHTML = '';
            }
        });
    });
})();
</script>
