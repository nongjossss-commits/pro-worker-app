/**
 * Centralized script for handling the Employment History modal.
 *
 * This script manages:
 * 1. Fetching and rendering terminated employees with checkboxes.
 * 2. Live search functionality.
 * 3. Bulk selection and a bulk action bar for transferring multiple employees.
 * 4. A "Transfer Employee" feature for single or multiple employees.
 * 5. Handling "Restore" and "Move to Trash" actions.
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- STATE AND CONSTANTS ---
    const historyModalEl = document.getElementById('employmentHistoryModal');
    if (!historyModalEl) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let currentEmployerId = null;
    let transferModalInstance = null;
    let isBulkTransfer = false; // Flag to track transfer mode

    // --- DOM ELEMENT SELECTORS ---
    const tableBody = document.getElementById('historyTableBody');
    const searchInput = document.getElementById('history-search-input');
    const bulkActionBar = document.getElementById('history-bulk-action-bar');
    const selectedCountSpan = document.getElementById('history-selected-count');
    const mainSelectAllCheckbox = document.getElementById('history-select-all-checkbox-main');
    const tableSelectAllCheckbox = document.getElementById('history-select-all-checkbox-table');
    const bulkActionButton = document.getElementById('history-bulk-action-btn');
    const transferModalEl = document.getElementById('transferEmployeeModal');
    const employeeToTransferIdInput = document.getElementById('employee-to-transfer-id');
    const employeeToTransferNameSpan = document.getElementById('employee-to-transfer-name');
    const employerSearchInput = document.getElementById('employer-search-input');
    const employerSearchResultsDiv = document.getElementById('employer-search-results');

    // --- RENDERING LOGIC ---
    const fetchAndRenderHistory = (employerId, searchTerm = '') => {
        if (!employerId) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error: Employer ID not found.</td></tr>`;
            return;
        }
        if (searchTerm === '') {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center">Loading...</td></tr>`;
        }

        const fetchUrl = `/employers/${employerId}/history?search=${encodeURIComponent(searchTerm)}`;

        fetch(fetchUrl)
            .then(response => response.ok ? response.json() : Promise.reject(response.status))
            .then(data => {
                tableBody.innerHTML = '';
                if (!data.data || data.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center">No employment history found ${searchTerm ? 'matching search' : ''}.</td></tr>`;
                    return;
                }
                data.data.forEach((employee, index) => {
                    // FIX: Use the new days_since_termination and photo_url from controller
                    const terminatedDate = employee.terminated_at ? new Date(employee.terminated_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
                    const daysSinceTerminationText = ` <span class="badge bg-secondary">${employee.days_since_termination} วัน</span>`;
                    const employeeFullName = `${employee.employeeTitleTh || ''} ${employee.employeeNameTh || 'N/A'}`.trim();

                    const restoreButton = `<button class="btn btn-sm btn-success btn-reinstate" title="Restore" data-employee-id="${employee.id}"><i class="bi bi-arrow-counterclockwise"></i></button>`;
                    const trashButton = `<button class="btn btn-sm btn-danger btn-move-to-trash" title="Move to Trash" data-employee-id="${employee.id}"><i class="bi bi-trash3-fill"></i></button>`;
                    const transferButton = `<button class="btn btn-sm btn-info btn-transfer-employee" title="Transfer Employer" data-employee-id="${employee.id}" data-employee-name="${employeeFullName}"><i class="bi bi-person-up"></i></button>`;
                    const createJobButton = `<button class="btn btn-sm btn-secondary btn-create-job-ticket" title="Create Job Ticket" disabled><i class="bi bi-ticket-detailed"></i></button>`;

                    const employeeCellHTML = `
                        <div class="d-flex align-items-center">
                            <img src="${employee.photo_url}" alt="Photo" class="employee-photo-thumb">
                            <div>
                                <strong>${employee.employeeNameEn || 'No English Name'}</strong>
                                <div class="text-muted small">${employeeFullName} (${employee.job_title || 'N/A'})</div>
                                <div class="text-muted small">Passport: ${employee.employeePassport || 'N/A'}</div>
                            </div>
                        </div>`;

                    const row = `
                        <tr id="history-row-${employee.id}">
                            <td><input class="form-check-input history-employee-checkbox" type="checkbox" data-employee-id="${employee.id}"></td>
                            <td>${index + 1}</td>
                            <td>${employeeCellHTML}</td>
                            <td>${terminatedDate}${daysSinceTerminationText}</td>
                            <td>${employee.termination_reason || '-'}</td>
                            <td><div class="d-flex gap-1">${restoreButton} ${trashButton} ${transferButton} ${createJobButton}</div></td>
                        </tr>`;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
                updateBulkActionBar();
            })
            .catch(error => {
                console.error('Error fetching employment history:', error);
                tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error loading data.</td></tr>`;
            });
    };

    // --- BULK ACTION & TRANSFER MODAL LOGIC ---
    const updateBulkActionBar = () => {
        const selectedCheckboxes = document.querySelectorAll('.history-employee-checkbox:checked');
        const count = selectedCheckboxes.length;
        bulkActionBar.style.display = count > 0 ? 'flex' : 'none';
        selectedCountSpan.textContent = count;
        bulkActionButton.disabled = count === 0;
        const allCheckboxes = document.querySelectorAll('.history-employee-checkbox');
        const isAllSelected = allCheckboxes.length > 0 && count === allCheckboxes.length;
        mainSelectAllCheckbox.checked = isAllSelected;
        tableSelectAllCheckbox.checked = isAllSelected;
    };

    const openTransferModal = () => {
        employerSearchInput.value = '';
        employerSearchResultsDiv.innerHTML = '';
        if (!transferModalInstance) {
            transferModalInstance = new bootstrap.Modal(transferModalEl);
        }
        transferModalInstance.show();
    };

    // --- API CALLS ---
    const performAction = (url, body = {}) => {
        body._token = csrfToken;
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                window.location.reload();
            } else {
                showToast(data.message || 'An unknown error occurred.', 'danger');
            }
        })
        .catch(error => {
            console.error(`Error performing action to ${url}:`, error);
            showToast('An error occurred while communicating with the server.', 'danger');
        });
    };

    // --- EVENT LISTENERS ---
    historyModalEl.addEventListener('show.bs.modal', function (event) {
        currentEmployerId = event.relatedTarget?.getAttribute('data-employer-id');
        searchInput.value = '';
        tableBody.innerHTML = '';
        fetchAndRenderHistory(currentEmployerId, '');
        updateBulkActionBar();
    });

    searchInput?.addEventListener('keyup', () => fetchAndRenderHistory(currentEmployerId, searchInput.value.trim()));

    tableBody.addEventListener('click', (event) => {
        const target = event.target.closest('button');
        if (!target) return;
        const employeeId = target.dataset.employeeId;
        if (target.classList.contains('btn-reinstate')) {
            Swal.fire({ title: 'ยืนยันการคืนสถานะ', text: "ลูกจ้างจะถูกย้ายกลับไปอยู่ในรายชื่อลูกจ้างปัจจุบัน", icon: 'question', showCancelButton: true, confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก' })
                .then(result => result.isConfirmed && performAction(`/employees/${employeeId}/reinstate`));
        } else if (target.classList.contains('btn-move-to-trash')) {
            Swal.fire({ title: 'ยืนยันการย้ายไปถังขยะ', text: "ลูกจ้างจะถูกย้ายไปที่ถังขยะส่วนกลาง", icon: 'warning', showCancelButton: true, confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#d33' })
                .then(result => result.isConfirmed && performAction(`/employees/${employeeId}`, { _method: 'DELETE' }));
        } else if (target.classList.contains('btn-transfer-employee')) {
            isBulkTransfer = false;
            employeeToTransferIdInput.value = employeeId;
            employeeToTransferNameSpan.textContent = `คุณกำลังจะย้ายลูกจ้าง: ${target.dataset.employeeName}`;
            openTransferModal();
        }
    });

    tableBody.addEventListener('change', (event) => {
        if (event.target.classList.contains('history-employee-checkbox')) {
            updateBulkActionBar();
        }
    });

    [mainSelectAllCheckbox, tableSelectAllCheckbox].forEach(checkbox => {
        checkbox.addEventListener('change', (event) => {
            const isChecked = event.target.checked;
            document.querySelectorAll('.history-employee-checkbox').forEach(cb => cb.checked = isChecked);
            mainSelectAllCheckbox.checked = isChecked;
            tableSelectAllCheckbox.checked = isChecked;
            updateBulkActionBar();
        });
    });

    bulkActionButton.addEventListener('click', () => {
        const selectedCheckboxes = document.querySelectorAll('.history-employee-checkbox:checked');
        if (selectedCheckboxes.length === 0) return;
        isBulkTransfer = true;
        const employeeIds = Array.from(selectedCheckboxes).map(cb => cb.dataset.employeeId);
        employeeToTransferIdInput.value = JSON.stringify(employeeIds);
        employeeToTransferNameSpan.textContent = `คุณกำลังจะย้ายลูกจ้างที่เลือกจำนวน ${selectedCheckboxes.length} คน`;
        openTransferModal();
    });

    employerSearchInput.addEventListener('keyup', () => {
        const searchTerm = employerSearchInput.value.trim();
        if (searchTerm.length < 2) {
            employerSearchResultsDiv.innerHTML = '';
            return;
        }
        fetch(`/api-web/employers/list?search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                employerSearchResultsDiv.innerHTML = data.length === 0 ? '<p class="text-muted p-2">ไม่พบข้อมูลนายจ้าง</p>' : '';
                data.forEach(employer => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    item.textContent = `${employer.employerNameTh} (${employer.employerId})`;
                    item.dataset.employerId = employer.id;
                    employerSearchResultsDiv.appendChild(item);
                });
            });
    });

    employerSearchResultsDiv.addEventListener('click', (event) => {
        const target = event.target.closest('button');
        if (!target) return;
        const newEmployerId = target.dataset.employerId;
        const newEmployerName = target.textContent;
        const swalHtml = isBulkTransfer
            ? `คุณต้องการย้ายลูกจ้างที่เลือกทั้งหมดไปยัง <strong>${newEmployerName}</strong> ใช่หรือไม่?`
            : `คุณต้องการย้ายลูกจ้างไปยัง <strong>${newEmployerName}</strong> ใช่หรือไม่?`;

        Swal.fire({ title: 'ยืนยันการย้ายนายจ้าง', html: swalHtml, icon: 'warning', showCancelButton: true, confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก' })
            .then(result => {
                if (result.isConfirmed) {
                    if (isBulkTransfer) {
                        const employeeIds = JSON.parse(employeeToTransferIdInput.value);
                        performAction('/employees/bulk-transfer', { employee_ids: employeeIds, new_employer_id: newEmployerId });
                    } else {
                        const employeeId = employeeToTransferIdInput.value;
                        performAction(`/employees/${employeeId}/transfer`, { new_employer_id: newEmployerId });
                    }
                }
            });
    });

    // Modal stacking fix
    if (transferModalEl) {
        transferModalEl.addEventListener('show.bs.modal', () => historyModalEl.classList.add('modal-behind'));
        transferModalEl.addEventListener('hidden.bs.modal', () => historyModalEl.classList.remove('modal-behind'));
    }
});
