{{-- resources/views/tickets/partials/_new_employee_modal.blade.php --}}
<div class="modal fade" id="newEmployeeModal" tabindex="-1" aria-labelledby="newEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form @submit.prevent="submitNewEmployeeForm" id="newEmployeeActualForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newEmployeeModalLabel">กรอกข้อมูลลูกจ้างใหม่ (แจ้งเข้า)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- V2.5-S3 FINAL --}}

                <h5><i class="bi bi-person-badge"></i> 1. ข้อมูลส่วนตัว</h5>
                <hr class="mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_employeeTitleTh" class="form-label">คำนำหน้าชื่อ (ไทย)</label>
                                <select class="form-select" id="modal_employeeTitleTh" x-model="newEmployeeForm.employeeTitleTh">
                                    <option value="นาย">นาย</option>
                                    <option value="นางสาว">นางสาว</option>
                                    <option value="นาง">นาง</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_employeeNameTh" class="form-label">ชื่อ-สกุล (ไทย) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modal_employeeNameTh" x-model="newEmployeeForm.employeeNameTh" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_employeeTitleEn" class="form-label">Prefix (EN)</label>
                                <select class="form-select" id="modal_employeeTitleEn" x-model="newEmployeeForm.employeeTitleEn">
                                    <option value="Mr.">Mr.</option>
                                    <option value="Miss">Miss</option>
                                    <option value="Mrs.">Mrs.</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_employeeNameEn" class="form-label">Full Name (EN)</label>
                                <input type="text" class="form-control" id="modal_employeeNameEn" x-model="newEmployeeForm.employeeNameEn">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_father_name" class="form-label">ชื่อพ่อ</label>
                                <input type="text" class="form-control" id="modal_father_name" x-model="newEmployeeForm.father_name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_mother_name" class="form-label">ชื่อแม่</label>
                                <input type="text" class="form-control" id="modal_mother_name" x-model="newEmployeeForm.mother_name">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="modal_employeeGender" class="form-label">เพศ</label>
                                <input type="text" class="form-control" id="modal_employeeGender" x-model="newEmployeeForm.employeeGender" readonly>
                            </div>
                            <div class="col-md-5 mb-3">
                                <label for="modal_employeeDob" class="form-label">วันเดือนปีเกิด</label>
                                <input type="date" class="form-control" id="modal_employeeDob" x-model="newEmployeeForm.employeeDob">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="modal_employeeAge" class="form-label">อายุ</label>
                                <input type="text" class="form-control" id="modal_employeeAge" x-model="newEmployeeForm.employeeAge" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex flex-column justify-content-center align-items-center">
                        <label for="modal_employeePhoto" class="form-label">รูปภาพพนักงาน</label>
                        <img id="modal_employeePhotoPreview" :src="newEmployeeForm.photo_preview_url || 'https://placehold.co/150x180/f8fafc/6c757d?text=Photo'" class="img-thumbnail mb-2" style="width: 150px; height: 180px; object-fit: cover;">
                        <input type="file" class="form-control form-control-sm w-75" id="modal_employeePhoto" accept="image/*" @change="handleFileUpload($event, 'employeePhoto', '#modal_employeePhotoPreview')">
                    </div>
                </div>

                <h5 class="mt-4"><i class="bi bi-telephone-fill"></i> 2. ข้อมูลการติดต่อและสัญชาติ</h5>
                <hr class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="modal_employeePhone" class="form-label">เบอร์โทรศัพท์</label>
                        <input type="tel" class="form-control" id="modal_employeePhone" x-model="newEmployeeForm.employeePhone">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="modal_employeeNationality" class="form-label">สัญชาติ</label>
                        <select class="form-select" id="modal_employeeNationality" x-model="newEmployeeForm.employeeNationality">
                            <option value="">-- เลือกสัญชาติ --</option>
                            <option value="เมียนมา">เมียนมา</option>
                            <option value="ลาว">ลาว</option>
                            <option value="กัมพูชา">กัมพูชา</option>
                            <option value="เวียดนาม">เวียดนาม</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" :class="{'d-none': newEmployeeForm.employeeNationality !== 'เมียนมา'}">
                        <label for="modal_passportType" class="form-label">ประเภทหนังสือเดินทาง (เมียนมา)</label>
                        <select class="form-select" id="modal_passportType" x-model="newEmployeeForm.passportType">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="PJ">เล่ม PJ</option>
                            <option value="CI">เล่ม CI</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" :class="{'d-none': newEmployeeForm.employeeNationality !== 'กัมพูชา'}">
                        <label for="modal_passport_type_cambodia" class="form-label">ประเภทหนังสือเดินทาง (กัมพูชา)</label>
                        <select class="form-select" id="modal_passport_type_cambodia" x-model="newEmployeeForm.passport_type_cambodia">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="เล่ม TD">เล่ม TD</option>
                            <option value="เล่มอินเตอร์">เล่มอินเตอร์</option>
                        </select>
                    </div>
                </div>

                <h5 class="mt-4"><i class="bi bi-passport"></i> 3. ข้อมูลหนังสือเดินทางและวีซ่า</h5>
                <hr class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="modal_employeePassport" class="form-label">เลขพาสปอร์ต</label>
                        <input type="text" class="form-control" id="modal_employeePassport" x-model="newEmployeeForm.employeePassport">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="modal_passport_issue_date" class="form-label">วันออกพาสปอร์ต</label>
                        <input type="date" class="form-control" id="modal_passport_issue_date" x-model="newEmployeeForm.passport_issue_date">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="modal_passportExpiryDate" class="form-label">วันหมดอายุพาสปอร์ต</label>
                        <input type="date" class="form-control" id="modal_passportExpiryDate" x-model="newEmployeeForm.passportExpiryDate">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="modal_pinkCardNo" class="form-label">เลขบัตรชมพู</label>
                        <input type="text" class="form-control" id="modal_pinkCardNo" x-model="newEmployeeForm.pinkCardNo">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="modal_visaType" class="form-label">ประเภทวีซ่า</label>
                        <input type="text" class="form-control" id="modal_visaType" x-model="newEmployeeForm.visaType">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="modal_visaExpiryDate" class="form-label">วันหมดอายุวีซ่า</label>
                        <input type="date" class="form-control" id="modal_visaExpiryDate" x-model="newEmployeeForm.visaExpiryDate">
                    </div>
                </div>

                <h5 class="mt-4"><i class="bi bi-briefcase-fill"></i> 4. ข้อมูลการจ้างงานและเอกสาร</h5>
                <hr class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="modal_job_title" class="form-label">ตำแหน่งงาน</label>
                        <input type="text" class="form-control" id="modal_job_title" x-model="newEmployeeForm.job_title">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="modal_job_description" class="form-label">ลักษณะงาน</label>
                        <input type="text" class="form-control" id="modal_job_description" x-model="newEmployeeForm.job_description">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="modal_startDate" class="form-label">วันที่เริ่มงาน</label>
                        <input type="date" class="form-control" id="modal_startDate" x-model="newEmployeeForm.startDate">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="modal_employeeWorkPermit" class="form-label">เลข Work Permit</label>
                        <input type="text" class="form-control" id="modal_employeeWorkPermit" x-model="newEmployeeForm.employeeWorkPermit">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="modal_workPermitExpiryDate" class="form-label">วันหมดอายุ Work Permit</label>
                        <input type="date" class="form-control" id="modal_workPermitExpiryDate" x-model="newEmployeeForm.workPermitExpiryDate">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="modal_ninetyDayReportDate" class="form-label">วันรายงานตัว 90 วัน</label>
                        <input type="date" class="form-control" id="modal_ninetyDayReportDate" x-model="newEmployeeForm.ninetyDayReportDate">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="modal_workPermitMOUGroup" class="form-label">ประเภทใบอนุญาตทำงาน</label>
                        <select class="form-select" id="modal_workPermitMOUGroup" x-model="newEmployeeForm.workPermitMOUGroup">
                            <option value="">-- กรุณาเลือก --</option>
                            <option value="MOU">MOU</option>
                            <option value="มติต่ออายุในประเทศ">มติต่ออายุในประเทศ</option>
                            <option value="มติขึ้นทะเบียน">มติขึ้นทะเบียน</option>
                            <option value="อื่นๆ">อื่นๆ ระบุ..</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3" :class="{'d-none': newEmployeeForm.workPermitMOUGroup !== 'อื่นๆ'}">
                        <label for="modal_workPermitMOUGroupOther" class="form-label">ระบุประเภทอื่นๆ</label>
                        <input type="text" class="form-control" id="modal_workPermitMOUGroupOther" x-model="newEmployeeForm.workPermitMOUGroupOther">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">เลข RA จากระบบ outsource</label><input type="text" class="form-control" x-model="newEmployeeForm.name_list_number"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">เลขที่คำขอ</label><input type="text" class="form-control" x-model="newEmployeeForm.request_number"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">เลขประจำตัว</label><input type="text" class="form-control" x-model="newEmployeeForm.employee_id_number"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">เลขประจำตัวผู้เสียภาษี</label><input type="text" class="form-control" x-model="newEmployeeForm.tax_id_number"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">รหัสคนงาน - ของนายจ้าง</label><input type="text" class="form-control" x-model="newEmployeeForm.employer_employee_id"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">เลขอ้างอิงคนงาน</label><input type="text" class="form-control" x-model="newEmployeeForm.employee_reference_id"></div>
                </div>

                <h5 class="mt-4"><i class="bi bi-heart-pulse"></i> 5. ข้อมูลประกันสุขภาพ</h5>
                <hr class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="modal_insurance_type" class="form-label">ประเภทประกัน</label>
                        <select class="form-select" id="modal_insurance_type" x-model="newEmployeeForm.insurance_type">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="ประกันสังคม">ประกันสังคม</option>
                            <option value="ประกันโรงพยาบาล">ประกันโรงพยาบาล</option>
                            <option value="ประกันเอกชน">ประกันเอกชน</option>
                        </select>
                    </div>
                </div>
                <div :class="{'d-none': newEmployeeForm.insurance_type !== 'ประกันสังคม'}">
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">เลขประกันสังคม</label><input type="text" class="form-control" x-model="newEmployeeForm.social_security_number"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">สิทธิ์โรงพยาบาล</label><input type="text" class="form-control" x-model="newEmployeeForm.insurance_detail_social"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">แนบไฟล์เอกสาร</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'insurance_document_path_social')"></div>
                    </div>
                </div>
                <div :class="{'d-none': newEmployeeForm.insurance_type !== 'ประกันโรงพยาบาล'}">
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">ชื่อโรงพยาบาล</label><input type="text" class="form-control" x-model="newEmployeeForm.insurance_detail_hospital"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">วันหมดอายุ</label><input type="date" class="form-control" x-model="newEmployeeForm.insurance_expiry_date_hospital"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">แนบไฟล์เอกสาร</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'insurance_document_path_hospital')"></div>
                    </div>
                </div>
                <div :class="{'d-none': newEmployeeForm.insurance_type !== 'ประกันเอกชน'}">
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">บริษัทประกัน</label><input type="text" class="form-control" x-model="newEmployeeForm.insurance_detail_private"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">วันหมดอายุ</label><input type="date" class="form-control" x-model="newEmployeeForm.insurance_expiry_date_private"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">แนบไฟล์เอกสาร</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'insurance_document_path_private')"></div>
                    </div>
                </div>

                <h5 class="mt-4"><i class="bi bi-lock-fill"></i> 6. ข้อมูลการเข้าสู่ระบบ</h5>
                <hr class="mb-4">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">อีเมล</label><input type="email" class="form-control" x-model="newEmployeeForm.employeeEmail"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">รหัสผ่าน</label><input type="text" class="form-control" x-model="newEmployeeForm.employeePassword"></div>
                </div>

                <h5 class="mt-4"><i class="bi bi-file-earmark-arrow-up-fill"></i> 7. ส่วนแนบไฟล์เอกสาร</h5>
                <hr class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">1. พาสปอร์ต</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_1')"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">2. วีซ่า</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_2')"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">3. ใบเสร็จ Work Permit</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_3')"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">4. บัตรชมพู</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_4')"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">5. ทร. 38</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_5')"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">6. รายงานตัว 90 วัน</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_6')"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">7. ใบแจ้งที่พักอาศัย</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_7')"></div>
                    <div class="col-md-4 mb-3"><label class="form-label">8. เอกสารบ้านเกิด</label><input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_8')"></div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">9. เอกสารอื่นๆ 1</label>
                        <input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_9')">
                        <input type="text" class="form-control form-control-sm mt-2" x-model="newEmployeeForm.other_doc_1_desc" placeholder="คำอธิบาย...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">10. เอกสารอื่นๆ 2</label>
                        <input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_10')">
                        <input type="text" class="form-control form-control-sm mt-2" x-model="newEmployeeForm.other_doc_2_desc" placeholder="คำอธิบาย...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">11. เอกสารอื่นๆ 3</label>
                        <input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_11')">
                        <input type="text" class="form-control form-control-sm mt-2" x-model="newEmployeeForm.other_doc_3_desc" placeholder="คำอธิบาย...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">12. เอกสารอื่นๆ 4</label>
                        <input type="file" class="form-control form-control-sm" @change="handleFileUpload($event, 'employee_doc_12')">
                        <input type="text" class="form-control form-control-sm mt-2" x-model="newEmployeeForm.other_doc_4_desc" placeholder="คำอธิบาย...">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-person-plus me-1"></i> เพิ่มเข้าตะกร้า
                </button>
            </div>
        </form>
    </div>
</div>
