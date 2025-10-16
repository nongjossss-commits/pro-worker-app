/**
 * Centralized script for handling the Employment History modal.
 *
 * This script manages the fetching and rendering of an employer's terminated
 * employees. It features a live search functionality to filter results
 * dynamically and presents the data in a rich format within the modal.
 */
document.addEventListener('DOMContentLoaded', () => {
    const historyModalEl = document.getElementById('employmentHistoryModal');

    // Do nothing if the modal is not on the page.
    if (!historyModalEl) {
        return;
    }

    const tableBody = document.getElementById('historyTableBody');
    const searchInput = document.getElementById('history-search-input');
    let currentEmployerId = null; // To store the employer ID when the modal is opened

    /**
     * Fetches and renders the employment history for a given employer.
     * @param {string} employerId - The ID of the employer.
     * @param {string} [searchTerm=''] - The search term to filter results.
     */
    const fetchAndRenderHistory = (employerId, searchTerm = '') => {
        if (!employerId) {
            console.error('fetchAndRenderHistory called without employerId.');
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">เกิดข้อผิดพลาด: ไม่พบ ID ของนายจ้าง</td></tr>';
            return;
        }

        // Set initial loading state only on the first load (no search term)
        if (searchTerm === '') {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">กำลังโหลดข้อมูล...</td></tr>';
        }

        const fetchUrl = `/employers/${employerId}/history?search=${encodeURIComponent(searchTerm)}`;

        fetch(fetchUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Network response was not ok, status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                tableBody.innerHTML = ''; // Clear previous results or loading state

                if (!data.data || data.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center">ไม่พบข้อมูลประวัติการจ้างงาน ${searchTerm ? 'ที่ตรงกับคำค้นหา' : ''}</td></tr>`;
                    return;
                }

                data.data.forEach((employee, index) => {
                    const terminatedDate = employee.terminated_at ? new Date(employee.terminated_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';

                    // Conditionally create action buttons
                    const restoreButton = employee.can_restore
                        ? `<button class="btn btn-sm btn-success js-restore-btn" data-employee-id="${employee.id}" title="กู้คืน"><i class="bi bi-arrow-counterclockwise"></i></button>`
                        : '';
                    const forceDeleteButton = employee.can_force_delete
                        ? `<button class="btn btn-sm btn-danger js-force-delete-btn" data-employee-id="${employee.id}" title="ลบถาวร"><i class="bi bi-trash3-fill"></i></button>`
                        : '';

                    // Rich HTML for the employee cell
                    const employeeCellHTML = `
                        <div class="d-flex align-items-center">
                            <img src="${employee.employeePhoto ? '/storage/' + employee.employeePhoto : '/images/default-avatar.png'}" alt="Photo" class="employee-photo-thumb" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%; margin-right: 1rem;">
                            <div>
                                <div class="fw-bold">${employee.employeeNameEn || 'No English Name'}</div>
                                <div class="text-muted small">${employee.employeeNameTh || 'N/A'} (${employee.employeePosition || 'N/A'})</div>
                                <div class="text-muted small">Passport: ${employee.employeePassport || 'N/A'}</div>
                            </div>
                        </div>
                    `;

                    const row = `
                        <tr id="history-row-${employee.id}">
                            <td>${index + 1}</td>
                            <td>${employeeCellHTML}</td>
                            <td>${terminatedDate}</td>
                            <td>${employee.termination_reason || '-'}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    ${restoreButton}
                                    ${forceDeleteButton}
                                </div>
                            </td>
                        </tr>`;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            })
            .catch(error => {
                console.error('Error fetching employment history:', error);
                tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
            });
    };

    // Event listener for when the modal is shown
    historyModalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        currentEmployerId = button.getAttribute('data-employer-id');

        // Clear previous search and results
        if(searchInput) searchInput.value = '';
        if(tableBody) tableBody.innerHTML = '';

        // Initial fetch when modal opens
        fetchAndRenderHistory(currentEmployerId, '');
    });

    // Event listener for the live search input
    if (searchInput) {
        searchInput.addEventListener('keyup', () => {
            const searchTerm = searchInput.value.trim();
            // A simple debounce could be added here if needed, but for now, direct call is fine.
            fetchAndRenderHistory(currentEmployerId, searchTerm);
        });
    }
});