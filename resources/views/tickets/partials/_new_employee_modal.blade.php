{{-- resources/views/tickets/partials/_new_employee_modal.blade.php --}}
{{-- CRITICAL: Use a <form> tag for structure and native validation (required attribute). Submission is intercepted by Alpine.js (@submit.prevent="submitNewEmployeeForm") --}}
<div class="modal fade" id="newEmployeeModal" tabindex="-1" aria-labelledby="newEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form @submit.prevent="submitNewEmployeeForm" id="newEmployeeActualForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newEmployeeModalLabel">กรอกข้อมูลลูกจ้างใหม่ (แจ้งเข้า)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Form Content (Simplified and adapted from employees/create.blade.php) --}}
                {{-- Inputs are bound using x-model to the newEmployeeForm state --}}
                <h5>ข้อมูลส่วนตัว</h5>
                <hr>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">ชื่อพนักงาน (ไทย) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select class="form-select" x-model="newEmployeeForm.employeeTitleTh" style="max-width: 100px;" required>
                                <option value="นาย">นาย</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="นาง">นาง</option>
                            </select>
                            <input type="text" class="form-control" x-model="newEmployeeForm.employeeNameTh" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ชื่อพนักงาน (อังกฤษ)</label>
                        <div class="input-group">
                            <select class="form-select" x-model="newEmployeeForm.employeeTitleEn" style="max-width: 100px;">
                                <option value="Mr.">Mr.</option>
                                <option value="Miss">Miss</option>
                                <option value="Mrs.">Mrs.</option>
                            </select>
                            <input type="text" class="form-control" x-model="newEmployeeForm.employeeNameEn">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">สัญชาติ <span class="text-danger">*</span></label>
                        <select class="form-select" x-model="newEmployeeForm.employeeNationality" required>
                            <option value="">-- เลือกสัญชาติ --</option>
                            <option value="ลาว">ลาว</option>
                            <option value="กัมพูชา">กัมพูชา</option>
                            <option value="เมียนมา">เมียนมา</option>
                            <option value="เวียดนาม">เวียดนาม</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">เลขหนังสือเดินทาง (Passport No.) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" x-model="newEmployeeForm.employeePassport" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ลักษณะงาน</label>
                        <input type="text" class="form-control" x-model="newEmployeeForm.nature_of_work" placeholder="เช่น ก่อสร้าง, แม่บ้าน">
                    </div>
                </div>

                <h5 class="mt-4">ไฟล์แนบ (V2.4-S6: Temp Upload Integration)</h5>
                <hr>
                <div class="row mb-3">
                    {{-- Employee Photo Upload --}}
                    <div class="col-md-6">
                        <label class="form-label">รูปถ่ายพนักงาน (JPEG, PNG, GIF)</label>
                        {{-- CRITICAL: Use handleFileUpload() Alpine function on change --}}
                        <input type="file" class="form-control" accept="image/jpeg,image/png,image/gif" @change="handleFileUpload($event, 'employeePhoto')">
                        {{-- Display Upload Status/Preview --}}
                        <div class="mt-2" x-show="uploadStatus.employeePhoto.loading || uploadStatus.employeePhoto.error || newEmployeeForm.employeePhoto">
                            <template x-if="uploadStatus.employeePhoto.loading">
                                <span class="text-muted"><i class="spinner-border spinner-border-sm me-2"></i>กำลังอัปโหลด...</span>
                            </template>
                            <template x-if="uploadStatus.employeePhoto.error">
                                <span class="text-danger" x-text="uploadStatus.employeePhoto.error"></span>
                            </template>
                            <template x-if="newEmployeeForm.employeePhoto && !uploadStatus.employeePhoto.loading && !uploadStatus.employeePhoto.error">
                                <a :href="uploadStatus.employeePhoto.url" target="_blank" class="text-success"><i class="bi bi-check-circle-fill me-2"></i>อัปโหลดสำเร็จ (ดูไฟล์)</a>
                            </template>
                        </div>
                    </div>
                    {{-- Document 1 (e.g., Passport Copy) --}}
                    <div class="col-md-6">
                        <label class="form-label">สำเนา Passport หรือเอกสารหลัก (JPEG, PNG, PDF)</label>
                        <input type="file" class="form-control" accept="image/jpeg,image/png,image/gif,application/pdf" @change="handleFileUpload($event, 'document_1')">
                        <div class="mt-2" x-show="uploadStatus.document_1.loading || uploadStatus.document_1.error || newEmployeeForm.document_1">
                            <template x-if="uploadStatus.document_1.loading">
                                <span class="text-muted"><i class="spinner-border spinner-border-sm me-2"></i>กำลังอัปโหลด...</span>
                            </template>
                            <template x-if="uploadStatus.document_1.error">
                                <span class="text-danger" x-text="uploadStatus.document_1.error"></span>
                            </template>
                            <template x-if="newEmployeeForm.document_1 && !uploadStatus.document_1.loading && !uploadStatus.document_1.error">
                                <a :href="uploadStatus.document_1.url" target="_blank" class="text-success"><i class="bi bi-check-circle-fill me-2"></i>อัปโหลดสำเร็จ (ดูไฟล์)</a>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                {{-- Submit Button (Triggers the @submit.prevent on the form tag) --}}
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-person-plus me-1"></i> เพิ่มเข้าตะกร้า
                </button>
            </div>
        </form>
    </div>
</div>
