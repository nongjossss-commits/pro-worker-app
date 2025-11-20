<!-- resources/views/components/download-modal.blade.php -->
<div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true" x-data="downloadModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="downloadModalLabel">ดาวน์โหลดไฟล์เอกสาร</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">รูปแบบการดาวน์โหลด</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="downloadMode" id="modeZip" value="zip" x-model="mode" checked>
                        <label class="form-check-label" for="modeZip">
                            ดาวน์โหลดแยกไฟล์ (ZIP) - แยกโฟลเดอร์ตามชื่อลูกจ้าง
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="downloadMode" id="modeMerge" value="merge" x-model="mode">
                        <label class="form-check-label" for="modeMerge">
                            ดาวน์โหลดรวมไฟล์ (PDF) - รวมเป็นไฟล์เดียว (ทำงานเบื้องหลัง)
                        </label>
                    </div>
                </div>

                <hr>

                <div class="mb-3">
                    <label class="form-label fw-bold">เลือกเอกสารที่ต้องการ</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="photo" id="filePhoto" x-model="files">
                        <label class="form-check-label" for="filePhoto">รูปภาพ (Photo)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="passport" id="filePassport" x-model="files">
                        <label class="form-check-label" for="filePassport">หนังสือเดินทาง (Passport)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="visa" id="fileVisa" x-model="files">
                        <label class="form-check-label" for="fileVisa">วีซ่า (Visa)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="work_permit" id="fileWorkPermit" x-model="files">
                        <label class="form-check-label" for="fileWorkPermit">ใบอนุญาตทำงาน (Work Permit)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="pink_card" id="filePinkCard" x-model="files">
                        <label class="form-check-label" for="filePinkCard">บัตรชมพู (Pink Card)</label>
                    </div>
                     <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="insurance" id="fileInsurance" x-model="files">
                        <label class="form-check-label" for="fileInsurance">เอกสารประกัน (Insurance)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="tor_ror_38" id="fileTorRor38" x-model="files">
                        <label class="form-check-label" for="fileTorRor38">ทร. 38</label>
                    </div>
                     <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="90_day_report" id="file90Day" x-model="files">
                        <label class="form-check-label" for="file90Day">รายงานตัว 90 วัน</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" @click="startDownload" :disabled="files.length === 0 || isLoading">
                    <span x-show="isLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span x-show="!isLoading">เริ่มดาวน์โหลด</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('downloadModal', () => ({
            mode: 'zip',
            files: [],
            employeeIds: [],
            isLoading: false,
            bsModal: null,

            init() {
                this.bsModal = new bootstrap.Modal(document.getElementById('downloadModal'));

                // Listen for trigger event
                window.addEventListener('open-download-modal', (event) => {
                    this.employeeIds = event.detail.employeeIds;
                    // Default selections
                    this.files = ['photo', 'passport', 'visa', 'work_permit', 'pink_card'];
                    this.mode = 'zip';
                    this.bsModal.show();
                });
            },

            startDownload() {
                if (this.files.length === 0) return;

                this.isLoading = true;

                fetch('{{ route("downloads.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        employee_ids: this.employeeIds,
                        file_types: this.files,
                        mode: this.mode
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    this.isLoading = false;
                    this.bsModal.hide();

                    // Dispatch event to refresh the menu
                    window.dispatchEvent(new Event('download-started'));

                    Swal.fire({
                        icon: 'success',
                        title: 'กำลังดำเนินการ',
                        text: 'ระบบกำลังเตรียมไฟล์ให้คุณ คุณสามารถตรวจสอบสถานะได้ที่เมนูดาวน์โหลด',
                        timer: 3000,
                        showConfirmButton: false
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.isLoading = false;
                     Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเริ่มการดาวน์โหลดได้ กรุณาลองใหม่อีกครั้ง',
                    });
                });
            }
        }));
    });
</script>
@endpush
