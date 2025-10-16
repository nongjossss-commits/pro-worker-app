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

{{-- Force Delete Confirmation Modal --}}
<div class="modal fade" id="forceDeleteConfirmationModal" tabindex="-1" aria-labelledby="forceDeleteConfirmationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="forceDeleteConfirmationModalLabel">ยืนยันการลบถาวร</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        คุณแน่ใจหรือไม่ที่จะลบข้อมูลพนักงานนี้อย่างถาวร? การกระทำนี้ไม่สามารถย้อนกลับได้
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-danger" id="confirm-force-delete-btn">ยืนยันการลบ</button>
      </div>
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
                <div class="mb-3">
                    <input type="text" id="history-search-input" class="form-control" placeholder="ค้นหาตามชื่อ หรือ เลขพาสปอร์ต...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th style="width: 40%;">พนักงาน</th>
                                <th>วันที่แจ้งออก</th>
                                <th>เหตุผล</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            {{-- Data will be loaded here by the script --}}
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
