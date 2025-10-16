/**
 * Centralized script for handling the Employment History modal.
 *
 * This script listens for the 'show.bs.modal' event on the #employmentHistoryModal.
 * It retrieves the employer ID from the button that triggered the modal,
 * fetches the employment history from the server, and populates the modal's
 * table with the data. It also dynamically creates "Restore" and "Force Delete"
 * buttons based on the user's permissions, as indicated by the API response.
 */
document.addEventListener('DOMContentLoaded', () => {
    const historyModalEl = document.getElementById('employmentHistoryModal');

    if (!historyModalEl) {
        return; // Do nothing if the modal is not on the page.
    }

    historyModalEl.addEventListener('show.bs.modal', function (event) {
        // Get the button that triggered the modal
        const button = event.relatedTarget;
        if (!button) {
            return; // Should not happen if triggered by a data-bs-* button
        }

        // Get employer ID from the button's data attribute
        const employerId = button.getAttribute('data-employer-id');
        const tableBody = document.getElementById('historyTableBody');

        if (!employerId || !tableBody) {
            console.error('Employment History Modal Error: Could not find employerId on the trigger button or the #historyTableBody element.');
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">เกิดข้อผิดพลาดในการตั้งค่า: ไม่พบ ID ของนายจ้าง</td></tr>';
            return;
        }

        // Set initial loading state
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td></tr>';

        // Construct the URL and fetch data
        const fetchUrl = `/employers/${employerId}/history`;

        fetch(fetchUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Network response was not ok, status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                tableBody.innerHTML = ''; // Clear loading state

                if (!data.data || data.data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลประวัติการจ้างงาน</td></tr>';
                    return;
                }

                data.data.forEach((employee, index) => {
                    const terminatedDate = employee.terminated_at ? new Date(employee.terminated_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';

                    // Conditionally create buttons based on permissions from the API response
                    const restoreButton = employee.can_restore
                        ? `<button class="btn btn-sm btn-success js-restore-btn" data-employee-id="${employee.id}" title="กู้คืน"><i class="bi bi-arrow-counterclockwise"></i></button>`
                        : '';
                    const forceDeleteButton = employee.can_force_delete
                        ? `<button class="btn btn-sm btn-danger js-force-delete-btn" data-employee-id="${employee.id}" title="ลบถาวร"><i class="bi bi-trash3-fill"></i></button>`
                        : '';

                    const row = `
                        <tr id="history-row-${employee.id}">
                            <td>${index + 1}</td>
                            <td>${employee.employeeNameTh || 'N/A'}</td>
                            <td>${employee.employeePosition || '-'}</td>
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
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
            });
    });
});