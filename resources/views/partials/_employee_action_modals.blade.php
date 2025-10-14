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

{{-- CORRECTED EMPLOYMENT HISTORY MODAL --}}
<div class="modal fade" id="employmentHistoryModal" tabindex="-1" aria-labelledby="employmentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employmentHistoryModalLabel">ประวัติการจ้างงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
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
                        <tbody id="historyTableBody"> {{-- <-- The ID is confirmed here --}}
                            {{-- Data will be loaded here --}}
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
            const tableBody = document.getElementById('historyTableBody'); // <-- Script now targets the correct ID
            const employerId = {{ $employer->id ?? 'null' }};

            if (!employerId || !tableBody) return;

            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">กำลังโหลดข้อมูล...</td></tr>';

            fetch(`/employers/${employerId}/history`)
                .then(response => response.json())
                .then(data => {
                    tableBody.innerHTML = '';
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลประวัติการจ้างงาน</td></tr>';
                        return;
                    }
                    data.forEach((employee, index) => {
                        const terminatedDate = new Date(employee.terminated_at).toLocaleDateString('th-TH');
                        const row = `<tr>
                                        <td>${index + 1}</td>
                                        <td>${employee.employeeNameTh || '-'}</td>
                                        <td>${employee.employeePosition || '-'}</td>
                                        <td>${terminatedDate}</td>
                                        <td>${employee.termination_reason || '-'}</td>
                                        <td>{{-- Actions --}}</td>
                                    </tr>`;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                })
                .catch(error => {
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>';
                });
        });
    }
});
</script>
@endpush