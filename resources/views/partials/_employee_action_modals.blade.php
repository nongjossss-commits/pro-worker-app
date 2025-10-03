{{-- ============== EMPLOYEE ACTION MODALS & SCRIPTS ============== --}}

{{-- Terminate Employee Modal --}}
<div class="modal fade z-3" id="terminateEmployeeModal" tabindex="-1" aria-labelledby="terminateModalLabel" aria-hidden="true">
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
<div class="modal fade z-3" id="employmentHistoryModal" tabindex="-1" aria-labelledby="employmentHistoryModalLabel" aria-hidden="true">
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
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ชื่อพนักงาน</th>
                            <th>วันที่แจ้งออก</th>
                            <th>เหตุผล</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="history-body">
                        {{-- Terminated employees will be loaded here via JavaScript --}}
                        <tr>
                            <td colspan="4" class="text-center">กำลังโหลด...</td>
                        </tr>
                    </tbody>
                </table>
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- SweetAlert2 Helpers ---
    const showSuccess = (message) => Swal.fire('สำเร็จ!', message, 'success');
    const showError = (message) => Swal.fire('ผิดพลาด!', message, 'error');

    // --- Terminate Modal Logic ---
    const terminateModal = document.getElementById('terminateEmployeeModal');
    if (terminateModal) {
        terminateModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const employeeId = button.getAttribute('data-employee-id');
            const url = `{{ url('employees') }}/${employeeId}/terminate`;
            const form = document.getElementById('terminate-form');
            form.setAttribute('action', url);
        });
    }

    // --- Event Delegation for ALL Action Buttons ---
    document.body.addEventListener('click', function(e) {
        const target = e.target.closest('button, a');
        if (!target) return;

        const employeeId = target.dataset.employeeId;

        // Restore Employee
        if (target.matches('.btn-restore')) {
            e.preventDefault();
            Swal.fire({
                title: 'คุณต้องการกู้คืนพนักงานคนนี้ใช่หรือไม่?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, กู้คืน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/employees/${employeeId}/restore`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            showSuccess(data.message).then(() => location.reload());
                        } else {
                            showError('กู้คืนข้อมูลไม่สำเร็จ');
                        }
                    });
                }
            });
        }

        // Force Delete Employee
        if (target.matches('.btn-force-delete')) {
            e.preventDefault();
             Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: "การกระทำนี้จะลบข้อมูลพนักงานอย่างถาวรและไม่สามารถย้อนกลับได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, ลบถาวร',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                     fetch(`/employees/${employeeId}/force-delete`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                    }).then(res => res.json()).then(data => {
                        if (data.success) {
                            showSuccess(data.message);
                            document.getElementById(`history-row-${employeeId}`).remove();
                        } else {
                            showError('ลบข้อมูลไม่สำเร็จ');
                        }
                    });
                }
            });
        }
    });
});
</script>
@endpush