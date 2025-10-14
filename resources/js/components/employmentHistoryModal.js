export function initializeHistoryModal() {
    const historyModal = document.getElementById('historyModal');
    if (!historyModal) {
        return;
    }

    historyModal.addEventListener('show.bs.modal', async function (event) {
        const button = event.relatedTarget;
        const employerId = button.getAttribute('data-employer-id');
        const historyTableBody = document.getElementById('historyTableBody');
        const loadingRow = `
            <tr>
                <td colspan="5" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `;
        historyTableBody.innerHTML = loadingRow;

        try {
            const response = await fetch(`/employers/${employerId}/history`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            historyTableBody.innerHTML = '';

            if (data.history && data.history.length > 0) {
                data.history.forEach(employee => {
                    const row = document.createElement('tr');
                    const canRestore = data.permissions.can_restore;
                    const canForceDelete = data.permissions.can_force_delete;

                    let actionButtons = '';
                    if (canRestore) {
                        actionButtons += `<button class="btn btn-sm btn-outline-success js-restore-btn" data-employee-id="${employee.id}" data-employee-name="${employee.employeeNameTh}">Restore</button> `;
                    }
                    if (canForceDelete) {
                        actionButtons += `<button class="btn btn-sm btn-outline-danger js-force-delete-btn" data-employee-id="${employee.id}" data-employee-name="${employee.employeeNameTh}">Delete Permanently</button>`;
                    }
                    if (!actionButtons) {
                        actionButtons = 'No actions available';
                    }

                    row.innerHTML = `
                        <td>${employee.id}</td>
                        <td>${employee.employeeNameTh}</td>
                        <td>${new Date(employee.termination_date).toLocaleDateString()}</td>
                        <td>${employee.termination_reason || 'N/A'}</td>
                        <td>${actionButtons}</td>
                    `;
                    historyTableBody.appendChild(row);
                });
            } else {
                historyTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No employment history found.</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching employment history:', error);
            historyTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load history. Please try again.</td></tr>';
        }
    });
}