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
    // If modal doesn't exist, we might be on the standalone page, but this script is primarily for the modal.
    // However, we should check if we need to run safely.

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let currentEmployerId = null;
    let transferModalInstance = null;
    let isBulkTransfer = false; // Flag to track transfer mode

    // --- DOM ELEMENT SELECTORS ---
    const tableBody = document.getElementById('historyTableBody');
    const searchInput = document.getElementById('history-search-input');

    // REFACTOR: The bulk action bar is now a component. We don't manage its visibility manually.
    // We just need to find the "Transfer" button which is now inside the component's dropdown (or slot).
    // The ID we gave it in the blade file is 'history-bulk-transfer-btn'.
    const bulkTransferBtn = document.getElementById('history-bulk-transfer-btn');

    // We still have the table header checkbox
    const tableSelectAllCheckbox = document.getElementById('history-select-all-checkbox-table');

    const transferModalEl = document.getElementById('transferEmployeeModal');
    const employeeToTransferIdInput = document.getElementById('employee-to-transfer-id');
    const employeeToTransferNameSpan = document.getElementById('employee-to-transfer-name');
    const employerSearchInput = document.getElementById('employer-search-input');
    const employerSearchResultsDiv = document.getElementById('employer-search-results');
    const selectedEmployerDisplay = document.getElementById('selected-employer-display');
    const selectedEmployerNameSpan = document.getElementById('selected-employer-name');
    const confirmTransferBtn = document.getElementById('confirm-transfer-btn');

    let selectedEmployer = null; // To store {id, name} of the selected employer

    // --- RENDERING LOGIC ---
    const fetchAndRenderHistory = (employerId, searchTerm = '') => {
        if (!employerId) {
            if(tableBody) tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error: Employer ID not found.</td></tr>`;
            return;
        }
        if (searchTerm === '') {
            if(tableBody) tableBody.innerHTML = `<tr><td colspan="6" class="text-center">Loading...</td></tr>`;
        }

        const fetchUrl = `/employers/${employerId}/history?search=${encodeURIComponent(searchTerm)}`;

        fetch(fetchUrl)
            .then(response => response.ok ? response.json() : Promise.reject(response.status))
            .then(data => {
                if (!tableBody) return;
                tableBody.innerHTML = '';
                if (!data.data || data.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center">No employment history found ${searchTerm ? 'matching search' : ''}.</td></tr>`;
                    // Ensure component hides itself if it was showing (by unchecking everything)
                    // The component logic listens to changes, so if we clear checkboxes, we might need to trigger an update.
                    // But since the DOM elements are gone, the component's `querySelectorAll` will find nothing.
                    // We can trigger a fake change event on body to force component update?
                    // Or just rely on the user not being able to select anything.
                     document.body.dispatchEvent(new Event('change'));
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
                            <td><input class="form-check-input history-employee-checkbox" type="checkbox" value="${employee.id}" data-employee-id="${employee.id}"></td>
                            <td>${index + 1}</td>
                            <td>${employeeCellHTML}</td>
                            <td>${terminatedDate}${daysSinceTerminationText}</td>
                            <td>${employee.termination_reason || '-'}</td>
                            <td><div class="d-flex gap-1">${restoreButton} ${trashButton} ${transferButton} ${createJobButton}</div></td>
                        </tr>`;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
                // Trigger update for the component to see new checkboxes (it calculates count)
                document.dispatchEvent(new Event('bulk-action-bar:update'));
            })
            .catch(error => {
                console.error('Error fetching employment history:', error);
                if(tableBody) tableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error loading data.</td></tr>`;
            });
    };

    // --- MODAL OPEN LOGIC ---
    const openTransferModal = () => {
        employerSearchInput.value = '';
        employerSearchResultsDiv.innerHTML = '';
        employerSearchResultsDiv.style.display = 'block'; // Show search results
        selectedEmployer = null;
        selectedEmployerDisplay.style.display = 'none';
        confirmTransferBtn.disabled = true;

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

    // --- UTILITY FUNCTIONS ---
    const debounce = (func, delay) => {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    };

    // --- EVENT LISTENERS ---
    if (historyModalEl) {
        historyModalEl.addEventListener('show.bs.modal', function (event) {
            currentEmployerId = event.relatedTarget?.getAttribute('data-employer-id');
            if(searchInput) searchInput.value = '';
            if(tableBody) tableBody.innerHTML = '';
            fetchAndRenderHistory(currentEmployerId, '');
        });
    }

    if(searchInput) searchInput.addEventListener('keyup', () => fetchAndRenderHistory(currentEmployerId, searchInput.value.trim()));

    if(tableBody) {
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
    }

    // Sync table header checkbox with component
    if (tableSelectAllCheckbox) {
        tableSelectAllCheckbox.addEventListener('change', (event) => {
            const isChecked = event.target.checked;
            document.querySelectorAll('.history-employee-checkbox').forEach(cb => {
                cb.checked = isChecked;
                // Dispatch change event so the component updates
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    }

    // Updated Bulk Transfer Listener for the component's button
    if (bulkTransferBtn) {
        bulkTransferBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const selectedCheckboxes = document.querySelectorAll('.history-employee-checkbox:checked');
            if (selectedCheckboxes.length === 0) return;
            isBulkTransfer = true;
            const employeeIds = Array.from(selectedCheckboxes).map(cb => cb.dataset.employeeId || cb.value);
            employeeToTransferIdInput.value = JSON.stringify(employeeIds);
            employeeToTransferNameSpan.textContent = `คุณกำลังจะย้ายลูกจ้างที่เลือกจำนวน ${selectedCheckboxes.length} คน`;
            openTransferModal();
        });
    }

    const handleEmployerSearch = debounce(() => {
        const searchTerm = employerSearchInput.value.trim();
        if (searchTerm.length < 2) {
            employerSearchResultsDiv.innerHTML = '';
            return;
        }
        employerSearchResultsDiv.innerHTML = '<p class="text-muted p-2">กำลังค้นหา...</p>';

        fetch(`/api-web/employers/list?search=${encodeURIComponent(searchTerm)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                employerSearchResultsDiv.innerHTML = data.length === 0 ? '<p class="text-muted p-2">ไม่พบข้อมูลนายจ้าง</p>' : '';
                data.forEach(employer => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                    item.innerHTML = `
                        <div>
                            <strong class="mb-1">${employer.employerNameTh}</strong>
                            <small class="d-block text-muted">ID: ${employer.employerId}</small>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    `;
                    item.dataset.employerId = employer.id;
                    item.dataset.employerName = employer.employerNameTh; // Store name for selection
                    employerSearchResultsDiv.appendChild(item);
                });
            })
            .catch(error => {
                console.error('Error fetching employers:', error);
                employerSearchResultsDiv.innerHTML = '<p class="text-danger p-2">เกิดข้อผิดพลาดในการค้นหา</p>';
            });
    }, 300);

    if(employerSearchInput) employerSearchInput.addEventListener('keyup', handleEmployerSearch);

    if(employerSearchResultsDiv) {
        employerSearchResultsDiv.addEventListener('click', (event) => {
            const target = event.target.closest('button');
            if (!target) return;

            // Store selected employer and update UI
            selectedEmployer = {
                id: target.dataset.employerId,
                name: target.dataset.employerName // Use the stored name
            };

            selectedEmployerNameSpan.textContent = selectedEmployer.name;
            selectedEmployerDisplay.style.display = 'block';
            confirmTransferBtn.disabled = false;
            employerSearchResultsDiv.style.display = 'none'; // Hide search results
            employerSearchResultsDiv.innerHTML = ''; // Clear search results
            employerSearchInput.value = ''; // Clear search input
        });
    }

    if(confirmTransferBtn) {
        confirmTransferBtn.addEventListener('click', () => {
            if (!selectedEmployer) {
                showToast('กรุณาเลือกนายจ้างใหม่ก่อน', 'danger');
                return;
            }

            const { id: newEmployerId, name: newEmployerName } = selectedEmployer;

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
    }

    // Modal stacking fix
    if (transferModalEl && historyModalEl) {
        transferModalEl.addEventListener('show.bs.modal', () => historyModalEl.classList.add('modal-behind'));
        transferModalEl.addEventListener('hidden.bs.modal', () => historyModalEl.classList.remove('modal-behind'));
    }
});
