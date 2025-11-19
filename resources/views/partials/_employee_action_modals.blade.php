{{-- This file now contains a standardized, central delete confirmation modal --}}
{{-- All other modals were either removed or are managed by specific JS files (like employment-history) --}}

<div class="modal fade" id="centralDeleteConfirmationModal" tabindex="-1" aria-labelledby="centralDeleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="centralDeleteConfirmationModalLabel">ยืนยันการดำเนินการ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="central-delete-modal-message">คุณแน่ใจหรือไม่ที่จะดำเนินการนี้?</p>
            </div>
            <div class="modal-footer">
                <form id="central-delete-form" method="POST" action="">
                    @csrf
                    {{-- The method will be dynamically set, defaulting to DELETE for soft deletes --}}
                    <input type="hidden" name="_method" value="DELETE" id="central-delete-form-method">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    {{-- The button's text and class will be set dynamically --}}
                    <button type="submit" class="btn" id="central-delete-confirm-btn">ยืนยัน</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const terminateModal = document.getElementById('terminateEmployeeModal');
    if (terminateModal) {
        const terminateForm = document.getElementById('terminateEmployeeForm');
        const employeeNameEl = document.getElementById('terminateEmployeeName');

        terminateModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const employeeId = button.getAttribute('data-employee-id');
            const employeeName = button.getAttribute('data-employee-name');

            const actionUrl = `/employees/${employeeId}/terminate`;
            terminateForm.action = actionUrl;
            employeeNameEl.textContent = employeeName;
        });

        terminateForm.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to terminate this employee?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, terminate them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.submit();
                }
            });
        });
    }
});
</script>
@endpush

{{-- All-purpose Employment History Modal --}}
<div class="modal fade" id="employmentHistoryModal" tabindex="-1" aria-labelledby="employmentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employmentHistoryModalLabel">ประวัติการจ้างงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <input type="text" id="history-search-input" class="form-control" style="max-width: 300px;" placeholder="ค้นหาตามชื่อ หรือ เลขพาสปอร์ต...">
                </div>

                <div id="history-bulk-action-bar" class="d-flex justify-content-between align-items-center bg-light p-2 rounded mb-3" style="display: none;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="history-select-all-checkbox-main">
                        <label class="form-check-label" for="history-select-all-checkbox-main">
                            เลือกทั้งหมด (<span id="history-selected-count">0</span>)
                        </label>
                    </div>
                    <button id="history-bulk-action-btn" class="btn btn-sm btn-primary" disabled>
                        <i class="bi bi-person-check-fill me-1"></i> ดำเนินการกับรายการที่เลือก
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 1%;"><input class="form-check-input" type="checkbox" id="history-select-all-checkbox-table"></th>
                                <th>#</th>
                                <th style="width: 40%;">พนักงาน</th>
                                <th>วันที่แจ้งออก</th>
                                <th>เหตุผล</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            {{-- Data will be loaded here by script --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>


{{-- Transfer Employee Modal (Child of History Modal) --}}
<div class="modal fade" id="transferEmployeeModal" tabindex="-1" aria-labelledby="transferEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transferEmployeeModalLabel">ย้ายนายจ้างสำหรับลูกจ้าง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="employee-to-transfer-id">
                <p>คุณกำลังจะย้ายลูกจ้าง: <strong id="employee-to-transfer-name"></strong></p>
                <div class="mb-3">
                    <label for="employer-search-input" class="form-label">ค้นหานายจ้างใหม่</label>
                    <input type="text" id="employer-search-input" class="form-control" placeholder="พิมพ์เพื่อค้นหาชื่อนายจ้าง...">
                </div>
                <div id="employer-search-results" class="list-group mb-3" style="max-height: 250px; overflow-y: auto;">
                    {{-- Employer search results will be populated here --}}
                </div>
                <div id="selected-employer-display" class="alert alert-info" style="display: none;">
                    <strong>นายจ้างที่เลือก:</strong> <span id="selected-employer-name"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="confirm-transfer-btn" disabled>ยืนยันการย้าย</button>
            </div>
        </div>
    </div>
</div>

{{-- Terminate Employee Modal --}}
<div class="modal fade" id="terminateEmployeeModal" tabindex="-1" aria-labelledby="terminateEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="terminateEmployeeForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="terminateEmployeeModalLabel">Terminate Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to terminate <strong id="terminateEmployeeName"></strong>?</p>
                    <input type="hidden" id="terminate_employee_id" name="employee_id">
                    <div class="mb-3">
                        <label for="termination_date" class="form-label">Termination Date</label>
                        <input type="date" class="form-control" id="termination_date" name="termination_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="termination_reason" class="form-label">Reason for Termination</label>
                        <textarea class="form-control" id="termination_reason" name="termination_reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Terminate</button>
                </div>
            </form>
        </div>
    </div>
</div>
