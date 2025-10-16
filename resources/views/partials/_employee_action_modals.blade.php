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

{{-- CORRECTED AND FINAL EMPLOYMENT HISTORY MODAL --}}
<div class="modal fade" id="employmentHistoryModal" tabindex="-1" aria-labelledby="employmentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employmentHistoryModalLabel">ประวัติการจ้างงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- All other content for search, etc. will go here later --}}
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
                            {{-- Data will be loaded here by the script below --}}
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
