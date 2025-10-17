/**
 * Centralized script for handling the Employment History modal.
 *
 * This script manages:
 * 1. Fetching and rendering terminated employees.
 * 2. Live search functionality.
 * 3. Handling "Restore" and "Force Delete" actions via confirmation modals.
 * 4. Fixing the Bootstrap modal stacking issue when a modal opens another.
 */
document.addEventListener('DOMContentLoaded', () => {
    const historyModalEl = document.getElementById('employmentHistoryModal');
    if (!historyModalEl) return;

    const tableBody = document.getElementById('historyTableBody');
    const searchInput = document.getElementById('history-search-input');
    let currentEmployerId = null;
    let formToSubmit = null; // Stores the form that triggered a confirmation modal

    // --- Confirmation Modal Elements & Instances ---
    const forceDeleteModalEl = document.getElementById('forceDeleteConfirmationModal');
    const restoreModalEl = document.getElementById('restoreConfirmationModal');
    const confirmDeleteBtn = document.getElementById('confirm-force-delete-btn');
    const confirmRestoreBtn = document.getElementById('confirm-restore-btn');

    // Ensure all required modal elements exist before proceeding
    if (!forceDeleteModalEl || !restoreModalEl || !confirmDeleteBtn || !confirmRestoreBtn) {
        console.error('One or more confirmation modal elements are missing from the DOM.');
        return;
    }

    const forceDeleteBootstrapModal = new bootstrap.Modal(forceDeleteModalEl);
    const restoreBootstrapModal = new bootstrap.Modal(restoreModalEl);

    /**
     * Fetches and renders the employment history for a given employer.
     * @param {string} employerId - The ID of the employer.
     * @param {string} [searchTerm=''] - The search term to filter results.
     */
    const fetchAndRenderHistory = (employerId, searchTerm = '') => {
        if (!employerId) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error: Employer ID not found.</td></tr>';
            return;
        }
        if (searchTerm === '') {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
        }

        const fetchUrl = `/employers/${employerId}/history?search=${encodeURIComponent(searchTerm)}`;

        fetch(fetchUrl)
            .then(response => response.ok ? response.json() : Promise.reject(response.status))
            .then(data => {
                tableBody.innerHTML = '';
                if (!data.data || data.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center">No employment history found ${searchTerm ? 'matching search' : ''}.</td></tr>`;
                    return;
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                data.data.forEach((employee, index) => {
                    const terminatedDate = employee.terminated_at ? new Date(employee.terminated_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';

                    const restoreForm = employee.can_restore ? `
                        <form action="/employees/${employee.id}/restore" method="POST" class="d-inline js-restore-form">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <button type="submit" class="btn btn-sm btn-success" title="Restore">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </form>` : '';

                    const forceDeleteForm = employee.can_force_delete ? `
                        <form action="/employees/${employee.id}/force-delete" method="POST" class="d-inline js-delete-form">
                             <input type="hidden" name="_method" value="DELETE">
                             <input type="hidden" name="_token" value="${csrfToken}">
                             <button type="submit" class="btn btn-sm btn-danger" title="Delete Permanently">
                                <i class="bi bi-trash3-fill"></i>
                             </button>
                        </form>` : '';

                    const flagMap = { 'เมียนมา': 'mm', 'ลาว': 'la', 'กัมพูชา': 'kh', 'เวียดนาม': 'vn', 'ไทย': 'th' };
                    const countryCode = flagMap[employee.employeeNationality];
                    const flagHTML = countryCode ? `<img src="/images/flags/${countryCode}.png" alt="${employee.employeeNationality}" style="width: 16px; height: 11px; margin-right: 4px; border-radius: 2px;">` : '';

                    const employeeNameEnWithPrefix = [employee.english_prefix, employee.employeeNameEn].filter(Boolean).join(' ');

                    const employeeCellHTML = `
                        <div class="d-flex align-items-center">
                            <img src="${employee.employeePhoto ? `/storage/${employee.employeePhoto}` : '/images/default-avatar.png'}" alt="Photo" class="employee-photo-thumb">
                            <div>
                                <strong>${employeeNameEnWithPrefix || 'No English Name'}</strong>
                                <div class="text-muted small">${employee.employeeTitleTh || ''} ${employee.employeeNameTh || 'N/A'} (${employee.employeePosition || 'N/A'})</div>
                                <div class="text-muted small">Passport: ${employee.employeePassport || 'N/A'}</div>
                                <div class="text-muted small d-flex align-items-center">${flagHTML} ${employee.employeeNationality || ''}</div>
                            </div>
                        </div>`;

                    const row = `
                        <tr id="history-row-${employee.id}">
                            <td>${index + 1}</td>
                            <td>${employeeCellHTML}</td>
                            <td>${terminatedDate}</td>
                            <td>${employee.termination_reason || '-'}</td>
                            <td><div class="d-flex gap-1">${restoreForm} ${forceDeleteForm}</div></td>
                        </tr>`;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            })
            .catch(error => {
                console.error('Error fetching employment history:', error);
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading data.</td></tr>';
            });
    };

    /**
     * Handles the AJAX submission for a given form (Restore or Force Delete).
     * @param {HTMLFormElement} form - The form to submit.
     */
    const submitFormAndRefresh = (form) => {
        const actionUrl = form.action;
        const isRestoreAction = actionUrl.includes('/restore');

        fetch(actionUrl, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    if (isRestoreAction) {
                        const historyModal = bootstrap.Modal.getInstance(historyModalEl);
                        if (historyModal) historyModal.hide();
                        // Asynchronously refresh the main employee list container
                        fetch(window.location.href)
                            .then(res => res.text())
                            .then(html => {
                                const newDoc = new DOMParser().parseFromString(html, 'text/html');
                                const newContainer = newDoc.getElementById('employee-list-container');
                                const currentContainer = document.getElementById('employee-list-container');
                                if (newContainer && currentContainer) {
                                    currentContainer.innerHTML = newContainer.innerHTML;
                                } else { window.location.reload(); } // Fallback
                            })
                            .catch(() => window.location.reload()); // Fallback
                    } else {
                        // For delete, just refresh the history modal list
                        fetchAndRenderHistory(currentEmployerId, searchInput.value.trim());
                    }
                } else {
                    showToast(data.message || 'An unknown error occurred.', 'danger');
                }
            })
            .catch(error => {
                console.error('Error submitting form:', error);
                showToast('An error occurred while communicating with the server.', 'danger');
            });
    };

    // --- Event Listeners ---

    // Open main history modal
    historyModalEl.addEventListener('show.bs.modal', function (event) {
        currentEmployerId = event.relatedTarget?.getAttribute('data-employer-id');
        if (searchInput) searchInput.value = '';
        if (tableBody) tableBody.innerHTML = '';
        fetchAndRenderHistory(currentEmployerId, '');
    });

    // Live search
    searchInput?.addEventListener('keyup', () => fetchAndRenderHistory(currentEmployerId, searchInput.value.trim()));

    // Delegated listener for Restore/Delete form submissions
    tableBody?.addEventListener('submit', function(event) {
        event.preventDefault();
        const form = event.target;
        formToSubmit = form; // Store the form

        if (form.classList.contains('js-delete-form')) {
            forceDeleteBootstrapModal.show();
        } else if (form.classList.contains('js-restore-form')) {
            restoreBootstrapModal.show();
        }
    });

    // Final confirmation button listeners
    confirmDeleteBtn.addEventListener('click', () => {
        if (formToSubmit) {
            submitFormAndRefresh(formToSubmit);
            formToSubmit = null;
            forceDeleteBootstrapModal.hide();
        }
    });

    confirmRestoreBtn.addEventListener('click', () => {
        if (formToSubmit) {
            submitFormAndRefresh(formToSubmit);
            formToSubmit = null;
            restoreBootstrapModal.hide();
        }
    });


    // --- DEFINITIVE MODAL STACKING FIX ---
    // When a confirmation modal is shown, add 'modal-behind' to the main history modal
    // to lower its z-index, allowing the new modal to appear on top.
    restoreModalEl.addEventListener('show.bs.modal', function () {
        historyModalEl.classList.add('modal-behind');
    });
    forceDeleteModalEl.addEventListener('show.bs.modal', function () {
        historyModalEl.classList.add('modal-behind');
    });

    // When a confirmation modal is hidden, remove the 'modal-behind' class to restore
    // the main history modal's original z-index.
    restoreModalEl.addEventListener('hidden.bs.modal', function () {
        historyModalEl.classList.remove('modal-behind');
    });
    forceDeleteModalEl.addEventListener('hidden.bs.modal', function () {
        historyModalEl.classList.remove('modal-behind');
    });
});