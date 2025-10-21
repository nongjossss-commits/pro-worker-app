/**
 * Centralized script for handling the Employment History modal.
 *
 * This script manages:
 * 1. Fetching and rendering terminated employees.
 * 2. Live search functionality.
 * 3. Handling "Restore" and "Move to Trash" actions with SweetAlert2 confirmations.
 */
document.addEventListener('DOMContentLoaded', () => {
    const historyModalEl = document.getElementById('employmentHistoryModal');
    if (!historyModalEl) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const tableBody = document.getElementById('historyTableBody');
    const searchInput = document.getElementById('history-search-input');
    let currentEmployerId = null;

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

                data.data.forEach((employee, index) => {
                    const terminatedDate = employee.terminated_at ? new Date(employee.terminated_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';

                    const restoreForm = employee.can_restore ? `
                        <form action="/employees/${employee.id}/reinstate" method="POST" class="d-inline js-restore-form">
                            <input type="hidden" name="_token" value="${csrfToken}">
                            <button type="submit" class="btn btn-sm btn-success" title="Restore">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </form>` : '';

                    // Requirement: Change action to soft delete, update button text.
                    const moveToTrashForm = employee.can_force_delete ? `
                        <form action="/employees/${employee.id}" method="POST" class="d-inline js-delete-form">
                             <input type="hidden" name="_method" value="DELETE">
                             <input type="hidden" name="_token" value="${csrfToken}">
                             <button type="submit" class="btn btn-sm btn-danger" title="Move to Trash">
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
                            <td><div class="d-flex gap-1">${restoreForm} ${moveToTrashForm}</div></td>
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
     * Handles the AJAX submission for a given form (Restore or Delete).
     * @param {HTMLFormElement} form - The form to submit.
     */
    const submitFormAndRefresh = (form) => {
        const actionUrl = form.action;
        fetch(actionUrl, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // A full page reload is the most reliable way to refresh all states
                    // for both main list and history modal after an action.
                    window.location.reload();
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

    // Delegated listener for Restore/Delete form submissions to trigger SweetAlert
    tableBody?.addEventListener('submit', function(event) {
        event.preventDefault();
        const form = event.target;

        let confirmConfig;

        if (form.classList.contains('js-delete-form')) {
            confirmConfig = {
                title: 'Are you sure?',
                text: "This employee will be moved to the Central Trash.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, move to trash!'
            };
        } else if (form.classList.contains('js-restore-form')) {
             confirmConfig = {
                title: 'Are you sure?',
                text: "This employee will be restored to the active list.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, restore it!'
            };
        } else {
            return; // Not a form we're interested in
        }

        Swal.fire(confirmConfig).then((result) => {
            if (result.isConfirmed) {
                submitFormAndRefresh(form);
            }
        });
    });
});
