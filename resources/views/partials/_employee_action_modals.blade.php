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
                        <tbody>
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

    // --- Modal and Form Element References ---
    const terminateModalEl = document.getElementById('terminateEmployeeModal');
    const terminateModal = terminateModalEl ? new bootstrap.Modal(terminateModalEl) : null;
    const terminateForm = document.getElementById('terminate-form');

    // --- AJAX form submission for Terminate Modal ---
    // This listener is separate because it handles a form 'submit' event, not a button 'click'.
    // It will be triggered after the terminate modal is shown and the user confirms.
    if (terminateForm) {
        terminateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this; // Use 'form' for clarity, as in the user's request
            const modal = terminateModalEl; // Use the correct modal element reference

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: new FormData(form) // Send the form data
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const employeeId = form.getAttribute('data-employee-id');
                    const employeeCard = document.getElementById('employee-card-' + employeeId);
                    const employeeRow = document.getElementById('employee-row-' + employeeId);

                    if (employeeCard) employeeCard.remove();
                    if (employeeRow) employeeRow.remove();

                    bootstrap.Modal.getInstance(modal).hide();

                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: 'แจ้งออกลูกจ้างเรียบร้อยแล้ว',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    // Use the error message from the server's JSON response
                    throw new Error(data.message || 'An unknown error occurred.');
                }
            })
            .catch(error => {
                console.error('Termination Error:', error);
                bootstrap.Modal.getInstance(modal).hide();
                Swal.fire({
                    title: 'ผิดพลาด!',
                    text: 'ไม่สามารถส่งข้อมูลได้ กรุณาลองใหม่อีกครั้ง',
                    icon: 'error'
                });
            });
        });
    }

    // --- CENTRALIZED EVENT DELEGATION FOR ALL ACTION BUTTONS ---
    document.body.addEventListener('click', function(e) {
        // Find the closest button with an action class
        const button = e.target.closest('.js-terminate-btn, .js-restore-btn, .js-force-delete-btn');
        if (!button) return;

        e.preventDefault();
        const employeeId = button.dataset.employeeId;
        if (!employeeId) return;

        // --- Handle Terminate Button Click (Show Modal) ---
        if (button.matches('.js-terminate-btn')) {
            if (terminateModal && terminateForm) {
                const url = `{{ url('employees') }}/${employeeId}/terminate`;
                terminateForm.setAttribute('action', url);
                terminateForm.setAttribute('data-employee-id', employeeId);
                terminateModal.show();
            }
        }

        // --- Handle Restore Button Click ---
        if (button.matches('.js-restore-btn')) {
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
        if (button.matches('.js-force-delete-btn')) {
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