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
    // Ensure SweetAlert2 and CSRF token are available
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded.');
        return;
    }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        console.error('CSRF token not found.');
        return;
    }

    // --- SweetAlert2 Helpers ---
    const showToast = (message, type = 'success') => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({
            icon: type,
            title: message
        });
    };
    const showSuccess = (message) => showToast(message, 'success');
    const showError = (message) => showToast(message, 'error');

    // --- Terminate Modal Logic ---
    const terminateModalEl = document.getElementById('terminateEmployeeModal');
    if (terminateModalEl) {
        const terminateForm = document.getElementById('terminate-form');

        // 1. Set form action when modal is shown
        terminateModalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const employeeId = button.getAttribute('data-employee-id');
            if (employeeId) {
                const url = `{{ url('employees') }}/${employeeId}/terminate`;
                terminateForm.setAttribute('action', url);
            }
        });

        // 2. Handle form submission with Fetch API for a smoother experience
        terminateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const actionUrl = this.getAttribute('action');

            fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const modalInstance = bootstrap.Modal.getInstance(terminateModalEl);
                modalInstance.hide();
                if (data.success) {
                    Swal.fire('สำเร็จ!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('ผิดพลาด!', data.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด!', 'ไม่สามารถส่งข้อมูลได้', 'error');
            });
        });
    }

    // --- Delegated Event Listeners for Dynamic Buttons (Restore, Force Delete) ---
    document.body.addEventListener('click', function(e) {
        const button = e.target.closest('.btn-restore, .btn-force-delete');
        if (!button) return;

        e.preventDefault();
        const employeeId = button.dataset.employeeId;

        // --- Handle Restore Button Click ---
        if (button.matches('.btn-restore')) {
            Swal.fire({
                title: 'คุณต้องการกู้คืนพนักงานคนนี้ใช่หรือไม่?',
                text: 'พนักงานจะกลับสู่สถานะ "กำลังจ้างงาน"',
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
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess(data.message);
                            // Remove row from history modal and reload the page for full update
                            document.getElementById(`history-row-${employeeId}`)?.remove();
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showError(data.message || 'กู้คืนข้อมูลไม่สำเร็จ');
                        }
                    }).catch(() => showError('เกิดข้อผิดพลาดในการสื่อสารกับเซิร์ฟเวอร์'));
                }
            });
        }

        // --- Handle Force Delete Button Click ---
        if (button.matches('.btn-force-delete')) {
             Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                html: "การกระทำนี้จะลบข้อมูลพนักงานและเอกสารที่เกี่ยวข้องทั้งหมดอย่าง<b>ถาวร</b><br>และไม่สามารถย้อนกลับได้!",
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
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showSuccess(data.message);
                            // Just remove the row from the UI, no need to reload
                            document.getElementById(`history-row-${employeeId}`)?.remove();
                            document.getElementById(`employee-card-${employeeId}`)?.remove();
                            document.getElementById(`employee-row-${employeeId}`)?.remove();
                        } else {
                            showError(data.message || 'ลบข้อมูลไม่สำเร็จ');
                        }
                    }).catch(() => showError('เกิดข้อผิดพลาดในการสื่อสารกับเซิร์ฟเวอร์'));
                }
            });
        }
    });
});
</script>
@endpush