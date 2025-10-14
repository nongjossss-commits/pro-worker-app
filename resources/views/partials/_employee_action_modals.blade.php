{{-- Terminate Employee Modal --}}
<div class="modal fade" id="terminateEmployeeModal" tabindex="-1" aria-labelledby="terminateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="terminate-form" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="terminateModalLabel">แจ้งออก / เลิกจ้าง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="terminated_at" class="form-label">วันที่แจ้งออก / เลิกจ้าง</label>
                        <input type="date" class="form-control" id="terminated_at" name="terminated_at" required>
                    </div>
                    <div class="mb-3">
                        <label for="termination_reason" class="form-label">เหตุผล (ถ้ามี)</label>
                        <textarea class="form-control" id="termination_reason" name="termination_reason" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ยืนยัน</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Employment History Modal --}}
<div class="modal fade" id="employmentHistoryModal" tabindex="-1" aria-labelledby="employmentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employmentHistoryModalLabel">ประวัติการจ้างงาน</h5>
                @isset($employer)
                <a href="{{ route('employers.exportHistory', $employer) }}" class="btn btn-sm btn-outline-success ms-auto">
                    <i class="bi bi-file-earmark-excel"></i> ส่งออก
                </a>
                @endisset
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="terminated-employees-list" class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>พนักงาน</th>
                                <th>ตำแหน่ง</th>
                                <th>วันที่แจ้งออก</th>
                                <th>เหตุผล</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            {{-- Data will be loaded here by JavaScript --}}
                            <tr>
                                <td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td>
                            </tr>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const historyModalEl = document.getElementById('employmentHistoryModal');

        if (historyModalEl) {
            historyModalEl.addEventListener('show.bs.modal', function (event) {
                const historyTableBody = document.getElementById('historyTableBody');
                const modal = this;
                const button = event.relatedTarget;
                const employerId = button ? button.dataset.employerId : modal.dataset.employerId;

                historyTableBody.innerHTML = '<tr><td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td></tr>';

                if (!employerId) {
                     historyTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">เกิดข้อผิดพลาด: ไม่พบรหัสผู้ประกอบการ</td></tr>';
                     return;
                }

                fetch(`/employers/${employerId}/history`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        historyTableBody.innerHTML = '';

                        if (data.length === 0) {
                            historyTableBody.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบประวัติการจ้างงาน</td></tr>';
                        } else {
                            data.forEach((employee, index) => {
                                const canRestore = employee.can_restore ? `<button class="btn btn-sm btn-outline-success js-restore-btn" data-employee-id="${employee.id}" data-employer-id="${employerId}">คืนสภาพ</button>` : '';
                                const canForceDelete = employee.can_force_delete ? `<button class="btn btn-sm btn-danger js-force-delete-btn" data-employee-id="${employee.id}">ลบถาวร</button>` : '';

                                const row = `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${employee.full_name_th} (${employee.full_name_en})</td>
                                        <td>${employee.employee_title || '-'}</td>
                                        <td>${employee.formatted_terminated_at}</td>
                                        <td>${employee.termination_reason || '-'}</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                ${canRestore}
                                                ${canForceDelete}
                                            </div>
                                        </td>
                                    </tr>`;
                                historyTableBody.insertAdjacentHTML('beforeend', row);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching employment history:', error);
                        historyTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล: ${error.message}</td></tr>`;
                    });
            });
        }
    });
</script>
@endpush