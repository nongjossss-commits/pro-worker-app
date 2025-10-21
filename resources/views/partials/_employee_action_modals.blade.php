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

{{-- All-purpose Employment History Modal --}}
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
