<form id="employeeEditForm" action="{{ route('employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="_previous" value="{{ url()->previous() }}">
    <input type="hidden" name="employer_id" value="{{ $employee->employer_id }}">

    {{-- Category 1: Personal Information --}}
    <h5><i class="bi bi-person-badge"></i> 1. ข้อมูลส่วนตัว (Personal Information)</h5>
    <hr class="mb-4">
    <div class="row">
        {{-- Left Column --}}
        <div class="col-md-8">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="employeeTitleTh" class="form-label">คำนำหน้าชื่อ (ไทย)
                        @if(isset($missingFields) && in_array('employeeTitleTh', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <select class="form-select" id="employeeTitleTh" name="employeeTitleTh">
                        <option value="นาย" @selected(old('employeeTitleTh', $employee->employeeTitleTh) == 'นาย')>นาย</option>
                        <option value="นางสาว" @selected(old('employeeTitleTh', $employee->employeeTitleTh) == 'นางสาว')>นางสาว</option>
                        <option value="นาง" @selected(old('employeeTitleTh', $employee->employeeTitleTh) == 'นาง')>นาง</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="employeeNameTh" class="form-label">ชื่อ-สกุล (ไทย)
                        @if(isset($missingFields) && in_array('employeeNameTh', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="text" class="form-control" id="employeeNameTh" name="employeeNameTh" value="{{ old('employeeNameTh', $employee->employeeNameTh) }}">
                </div>
            </div>

            <div class="row">
                    <div class="col-md-6 mb-3">
                    <label for="employeeTitleEn" class="form-label">Prefix (EN)
                        @if(isset($missingFields) && in_array('employeeTitleEn', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <select class="form-select" id="employeeTitleEn" name="employeeTitleEn">
                            <option value="Mr." @selected(old('employeeTitleEn', $employee->employeeTitleEn) == 'Mr.')>Mr.</option>
                            <option value="Miss" @selected(old('employeeTitleEn', $employee->employeeTitleEn) == 'Miss')>Miss</option>
                            <option value="Mrs." @selected(old('employeeTitleEn', $employee->employeeTitleEn) == 'Mrs.')>Mrs.</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="employeeNameEn" class="form-label">Full Name (EN) <span class="text-danger">*</span>
                        @if(isset($missingFields) && in_array('employeeNameEn', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="text" class="form-control" id="employeeNameEn" name="employeeNameEn" value="{{ old('employeeNameEn', $employee->employeeNameEn) }}" required>
                </div>
            </div>

                <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="father_name" class="form-label">ชื่อพ่อ
                        @if(isset($missingFields) && in_array('father_name', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="text" class="form-control" id="father_name" name="father_name" value="{{ old('father_name', $employee->father_name) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="mother_name" class="form-label">ชื่อแม่
                        @if(isset($missingFields) && in_array('mother_name', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="text" class="form-control" id="mother_name" name="mother_name" value="{{ old('mother_name', $employee->mother_name) }}">
                </div>
            </div>

            <div class="row">
                    <div class="col-md-4 mb-3">
                    <label for="employeeGender" class="form-label">เพศ</label>
                    <input type="text" class="form-control" id="employeeGender" name="employeeGender" value="{{ old('employeeGender', $employee->employeeGender) }}" readonly>
                    </div>
                    <div class="col-md-5 mb-3">
                    <label for="employeeDob" class="form-label">วันเดือนปีเกิด
                        @if(isset($missingFields) && in_array('employeeDob', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                    </label>
                    <input type="date" class="form-control" id="employeeDob" name="employeeDob" value="{{ old('employeeDob', optional($employee->employeeDob)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="employeeAge" class="form-label">อายุ</label>
                    <input type="text" class="form-control" id="employeeAge" name="employeeAge" value="{{ old('employeeAge', $employee->employeeAge) }}" readonly>
                </div>
            </div>
        </div>
        {{-- Right Column --}}
        <div class="col-md-4 d-flex flex-column justify-content-center align-items-center">
            <label class="form-label">รูปภาพพนักงาน
                @if(isset($missingFields) && in_array('employeePhoto', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <img id="employeePhotoPreview" src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/150x180/f8fafc/6c757d?text=Photo' }}" class="img-thumbnail mb-3" style="width: 150px; height: 180px; object-fit: cover;">
            <div class="d-grid gap-2 w-75">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('triggerFile').click();"><i class="bi bi-file-earmark-image me-1"></i> เลือกจากไฟล์</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('triggerCamera').click();"><i class="bi bi-camera-fill me-1"></i> ถ่ายภาพ</button>
            </div>
            {{-- Hidden file inputs --}}
            <input type="file" class="d-none" id="triggerFile" accept="image/*">
            <input type="file" class="d-none" id="triggerCamera" accept="image/*" capture="environment">
            {{-- Actual Input for Submission --}}
            <input type="file" class="d-none" id="employeePhotoInput" name="employeePhoto">
        </div>
    </div>


    {{-- Category 2: Contact & Nationality --}}
    <h5 class="mt-4"><i class="bi bi-telephone-fill"></i> 2. ข้อมูลการติดต่อและสัญชาติ (Contact & Nationality)</h5>
    <hr class="mb-4">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="employeePhone" class="form-label">เบอร์โทรศัพท์
                @if(isset($missingFields) && in_array('employeePhone', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="tel" class="form-control" id="employeePhone" name="employeePhone" value="{{ old('employeePhone', $employee->employeePhone) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="employeeNationality" class="form-label">สัญชาติ
                @if(isset($missingFields) && in_array('employeeNationality', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <select class="form-select" id="employeeNationality" name="employeeNationality">
                <option value="">-- เลือกสัญชาติ --</option>
                <option value="เมียนมา" @selected(old('employeeNationality', $employee->employeeNationality) == 'เมียนมา')>เมียนมา</option>
                <option value="ลาว" @selected(old('employeeNationality', $employee->employeeNationality) == 'ลาว')>ลาว</option>
                <option value="กัมพูชา" @selected(old('employeeNationality', $employee->employeeNationality) == 'กัมพูชา')>กัมพูชา</option>
                <option value="เวียดนาม" @selected(old('employeeNationality', $employee->employeeNationality) == 'เวียดนาม')>เวียดนาม</option>
            </select>
        </div>
            <div class="col-md-4 mb-3 d-none" id="passportTypeContainer">
            <label for="passportType" class="form-label">ประเภทหนังสือเดินทาง (สำหรับเมียนมา)
                @if(isset($missingFields) && in_array('passportType', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <select class="form-select" id="passportType" name="passportType">
                <option value="">-- เลือกประเภท --</option>
                <option value="PJ" @selected(old('passportType', $employee->passportType) == 'PJ')>เล่ม PJ</option>
                <option value="CI" @selected(old('passportType', $employee->passportType) == 'CI')>เล่ม CI</option>
            </select>
        </div>
        <div class="col-md-4 mb-3 d-none" id="passportTypeCambodiaContainer">
            <label for="passport_type_cambodia" class="form-label">ประเภทหนังสือเดินทาง (สำหรับกัมพูชา)
                @if(isset($missingFields) && in_array('passport_type_cambodia', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <select class="form-select" id="passport_type_cambodia" name="passport_type_cambodia">
                <option value="">-- เลือกประเภท --</option>
                <option value="เล่ม TD" @selected(old('passport_type_cambodia', $employee->passport_type_cambodia) == 'เล่ม TD')>เล่ม TD</option>
                <option value="เล่มอินเตอร์" @selected(old('passport_type_cambodia', $employee->passport_type_cambodia) == 'เล่มอินเตอร์')>เล่มอินเตอร์</option>
            </select>
        </div>
    </div>

    {{-- Category 3: Passport & Visa --}}
    <h5 class="mt-4"><i class="bi bi-passport"></i> 3. ข้อมูลหนังสือเดินทางและวีซ่า (Passport & Visa)</h5>
    <hr class="mb-4">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="employeePassport" class="form-label">เลขพาสปอร์ต
                @if(isset($missingFields) && in_array('employeePassport', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="employeePassport" name="employeePassport" value="{{ old('employeePassport', $employee->employeePassport) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="passport_issue_date" class="form-label">วันออกพาสปอร์ต
                @if(isset($missingFields) && in_array('passport_issue_date', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control" id="passport_issue_date" name="passport_issue_date" value="{{ old('passport_issue_date', optional($employee->passport_issue_date)->format('Y-m-d')) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="passportExpiryDate" class="form-label">วันหมดอายุพาสปอร์ต
                @if(isset($missingFields) && in_array('passportExpiryDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control" id="passportExpiryDate" name="passportExpiryDate" value="{{ old('passportExpiryDate', optional($employee->passportExpiryDate)->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="pinkCardNo" class="form-label">เลขบัตรชมพู
                @if(isset($missingFields) && in_array('pinkCardNo', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="pinkCardNo" name="pinkCardNo" value="{{ old('pinkCardNo', $employee->pinkCardNo) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="visaType" class="form-label">ประเภทวีซ่า
                @if(isset($missingFields) && in_array('visaType', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="visaType" name="visaType" value="{{ old('visaType', $employee->visaType) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="visaExpiryDate" class="form-label">วันหมดอายุวีซ่า
                @if(isset($missingFields) && in_array('visaExpiryDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control" id="visaExpiryDate" name="visaExpiryDate" value="{{ old('visaExpiryDate', optional($employee->visaExpiryDate)->format('Y-m-d')) }}">
        </div>
    </div>

    {{-- Category 4: Employment & Work IDs --}}
    <h5 class="mt-4"><i class="bi bi-briefcase-fill"></i> 4. ข้อมูลการจ้างงานและเอกสาร (Employment & Work IDs)</h5>
    <hr class="mb-4">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="job_title" class="form-label">ตำแหน่งงาน
                @if(isset($missingFields) && in_array('job_title', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="job_title" name="job_title" value="{{ old('job_title', $employee->job_title) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="job_description" class="form-label">ลักษณะงาน
                @if(isset($missingFields) && in_array('job_description', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="job_description" name="job_description" value="{{ old('job_description', $employee->job_description) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="startDate" class="form-label">วันที่เริ่มงาน
                @if(isset($missingFields) && in_array('startDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control" id="startDate" name="startDate" value="{{ old('startDate', optional($employee->startDate)->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="employeeWorkPermit" class="form-label">เลข Work Permit
                @if(isset($missingFields) && in_array('employeeWorkPermit', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="employeeWorkPermit" name="employeeWorkPermit" value="{{ old('employeeWorkPermit', $employee->employeeWorkPermit) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label for="workPermitExpiryDate" class="form-label">วันหมดอายุ Work Permit
                @if(isset($missingFields) && in_array('workPermitExpiryDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control" id="workPermitExpiryDate" name="workPermitExpiryDate" value="{{ old('workPermitExpiryDate', optional($employee->workPermitExpiryDate)->format('Y-m-d')) }}">
        </div>
            <div class="col-md-4 mb-3">
            <label for="ninetyDayReportDate" class="form-label">วันรายงานตัว 90 วัน
                @if(isset($missingFields) && in_array('ninetyDayReportDate', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="date" class="form-control" id="ninetyDayReportDate" name="ninetyDayReportDate" value="{{ old('ninetyDayReportDate', optional($employee->ninetyDayReportDate)->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="row">
            <div class="col-md-6 mb-3">
            <label for="workPermitType" class="form-label">ประเภทใบอนุญาตทำงาน
                @if(isset($missingFields) && in_array('workPermitMOUGroup', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <select class="form-select" id="workPermitMOUGroup" name="workPermitMOUGroup">
                <option value="">-- กรุณาเลือก --</option>
                <option value="MOU" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'MOU')>MOU</option>
                <option value="มติต่ออายุในประเทศ" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'มติต่ออายุในประเทศ')>มติต่ออายุในประเทศ</option>
                <option value="มติขึ้นทะเบียน" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'มติขึ้นทะเบียน')>มติขึ้นทะเบียน</option>
                <option value="อื่นๆ" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'อื่นๆ')>อื่นๆ ระบุ..</option>
            </select>
        </div>
        <div class="col-md-6 mb-3 d-none" id="workPermitMOUGroupOtherContainer">
            <label for="workPermitMOUGroupOther" class="form-label">ระบุประเภทอื่นๆ
                @if(isset($missingFields) && in_array('workPermitMOUGroupOther', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
                <input type="text" class="form-control" id="workPermitMOUGroupOther" name="workPermitMOUGroupOther" value="{{ old('workPermitMOUGroupOther', $employee->workPermitMOUGroupOther) }}">
        </div>
    </div>
    <div class="row">
            <div class="col-md-4 mb-3"><label for="name_list_number" class="form-label">เลข RA จากระบบ outsource
                @if(isset($missingFields) && in_array('name_list_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="name_list_number" name="name_list_number" value="{{ old('name_list_number', $employee->name_list_number) }}"></div>
            <div class="col-md-4 mb-3"><label for="request_number" class="form-label">เลขที่คำขอ
                @if(isset($missingFields) && in_array('request_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="request_number" name="request_number" value="{{ old('request_number', $employee->request_number) }}"></div>
            <div class="col-md-4 mb-3"><label for="employee_id_number" class="form-label">เลขประจำตัว
                @if(isset($missingFields) && in_array('employee_id_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="employee_id_number" name="employee_id_number" value="{{ old('employee_id_number', $employee->employee_id_number) }}"></div>
            <div class="col-md-4 mb-3"><label for="tax_id_number" class="form-label">เลขประจำตัวผู้เสียภาษี
                @if(isset($missingFields) && in_array('tax_id_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="tax_id_number" name="tax_id_number" value="{{ old('tax_id_number', $employee->tax_id_number) }}"></div>
            <div class="col-md-4 mb-3"><label for="employer_employee_id" class="form-label">รหัสคนงาน - ของนายจ้าง
                @if(isset($missingFields) && in_array('employer_employee_id', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="employer_employee_id" name="employer_employee_id" value="{{ old('employer_employee_id', $employee->employer_employee_id) }}"></div>
            <div class="col-md-4 mb-3"><label for="employee_reference_id" class="form-label">เลขอ้างอิงคนงาน
                @if(isset($missingFields) && in_array('employee_reference_id', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label><input type="text" class="form-control" id="employee_reference_id" name="employee_reference_id" value="{{ old('employee_reference_id', $employee->employee_reference_id) }}"></div>
    </div>


    {{-- Category 5: Health Insurance --}}
    <h5 class="mt-4"><i class="bi bi-heart-pulse"></i> 5. ข้อมูลประกันสุขภาพ (Health Insurance)</h5>
    <hr class="mb-4">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="insurance_type" class="form-label">ประเภทประกัน
                @if(isset($missingFields) && in_array('insurance_type', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <select class="form-select" id="insurance_type" name="insurance_type">
                <option value="">-- เลือกประเภท --</option>
                <option value="ประกันสังคม" @selected(old('insurance_type', $employee->insurance_type) == 'ประกันสังคม')>ประกันสังคม</option>
                <option value="ประกันโรงพยาบาล" @selected(old('insurance_type', $employee->insurance_type) == 'ประกันโรงพยาบาล')>ประกันโรงพยาบาล</option>
                <option value="ประกันเอกชน" @selected(old('insurance_type', $employee->insurance_type) == 'ประกันเอกชน')>ประกันเอกชน</option>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="medical_certificate_path" class="form-label">ใบรับรองแพทย์ (Medical Certificate)</label>
            @if($employee->medical_certificate_path)
                <div class="mb-2 d-flex gap-1">
                    <a href="{{ asset('storage/' . $employee->medical_certificate_path) }}" target="_blank" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์ปัจจุบัน</a>
                    <a href="{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'medical_certificate_path']) }}" target="_blank" class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </div>
            @endif
            <div class="input-group input-group-sm">
                <input type="file" class="form-control form-control-sm" id="medical_certificate_path" name="medical_certificate_path">
                <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'medical_certificate_path' } }))">
                    <i class="bi bi-camera"></i>
                </button>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="medical_hospital_name" class="form-label">โรงพยาบาลที่ตรวจโรค (Hospital Name)</label>
            <input type="text" class="form-control" id="medical_hospital_name" name="medical_hospital_name" value="{{ old('medical_hospital_name', $employee->medical_hospital_name) }}">
        </div>
    </div>

    {{-- Social Security Container --}}
    <div id="insuranceSocialSecurity" class="d-none">
            <div class="row">
            <div class="col-md-4 mb-3">
                <label for="social_security_number" class="form-label">เลขประกันสังคม
                    @if(isset($missingFields) && in_array('social_security_number', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" id="social_security_number" name="social_security_number" value="{{ old('social_security_number', $employee->social_security_number) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="insurance_detail" class="form-label">สิทธิ์โรงพยาบาล
                    @if(isset($missingFields) && in_array('insurance_detail', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" name="insurance_detail" value="{{ old('insurance_detail', $employee->insurance_detail) }}">
            </div>
            </div>
    </div>
    {{-- Hospital Insurance Container --}}
    <div id="insuranceHospital" class="d-none">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="insurance_detail_hospital" class="form-label">ชื่อโรงพยาบาล
                    @if(isset($missingFields) && in_array('insurance_detail_hospital', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" name="insurance_detail_hospital" value="{{ old('insurance_detail_hospital', $employee->insurance_detail_hospital) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="insurance_expiry_date_hospital" class="form-label">วันหมดอายุประกัน
                    @if(isset($missingFields) && in_array('insurance_expiry_date_hospital', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="date" class="form-control" name="insurance_expiry_date_hospital" value="{{ old('insurance_expiry_date_hospital', optional($employee->insurance_expiry_date_hospital)->format('Y-m-d')) }}">
            </div>
        </div>
    </div>
    {{-- Private Insurance Container --}}
    <div id="insurancePrivate" class="d-none">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="insurance_detail_private" class="form-label">บริษัทประกัน
                    @if(isset($missingFields) && in_array('insurance_detail_private', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="text" class="form-control" name="insurance_detail_private" value="{{ old('insurance_detail_private', $employee->insurance_detail_private) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="insurance_expiry_date_private" class="form-label">วันหมดอายุประกัน
                    @if(isset($missingFields) && in_array('insurance_expiry_date_private', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                <input type="date" class="form-control" name="insurance_expiry_date_private" value="{{ old('insurance_expiry_date_private', optional($employee->insurance_expiry_date_private)->format('Y-m-d')) }}">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="insurance_document_path_private" class="form-label">แนบไฟล์เอกสารประกัน
                @if(isset($missingFields) && in_array('insurance_document_path_private', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            @if($employee->insurance_document_path_private)
                <div class="mb-2 d-flex gap-1">
                    <a href="{{ asset('storage/' . $employee->insurance_document_path_private) }}" target="_blank" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์ปัจจุบัน</a>
                    <a href="{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'insurance_document_path_private']) }}" target="_blank" class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                </div>
            @endif
            <input type="file" class="form-control form-control-sm" name="insurance_document_path_private">
        </div>
    </div>

    {{-- Category 6: Login Information --}}
    <h5 class="mt-4"><i class="bi bi-lock-fill"></i> 6. ข้อมูลการเข้าสู่ระบบ (Login Information)</h5>
    <hr class="mb-4">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="employeeEmail" class="form-label">อีเมล
                @if(isset($missingFields) && in_array('email', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="email" class="form-control" id="employeeEmail" name="employeeEmail" value="{{ old('employeeEmail', $employee->email) }}">
        </div>
        <div class="col-md-6 mb-3">
            <label for="password" class="form-label">รหัสผ่าน (Password)
                @if(isset($missingFields) && in_array('password', $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
            </label>
            <input type="text" class="form-control" id="password" name="password"
                    value="{{ $employee->password }}" placeholder="กรอกรหัสผ่าน">
            {{-- Note: type="text" creates a plain text box as requested --}}
        </div>
    </div>

    {{-- Category 7: File Attachments --}}
    <h5 class="mt-4"><i class="bi bi-file-earmark-arrow-up-fill"></i> 7. ส่วนแนบไฟล์เอกสาร (File Attachments)</h5>
    <hr class="mb-4">
    @php
        $docSlots = [
            1 => 'พาสปอร์ต', 2 => 'วีซ่า', 3 => 'ใบเสร็จ Work Permit', 4 => 'บัตรชมพู',
            5 => 'ทร. 38', 6 => 'รายงานตัว 90 วัน', 7 => 'ใบแจ้งที่พักอาศัย', 8 => 'เอกสารบ้านเกิด',
            9 => 'เอกสารอื่นๆ 1', 10 => 'เอกสารอื่นๆ 2', 11 => 'เอกสารอื่นๆ 3', 12 => 'เอกสารอื่นๆ 4',
            13 => 'เอกสารอื่นๆ 5', 14 => 'เอกสารอื่นๆ 6', 15 => 'เอกสารอื่นๆ 7', 16 => 'เอกสารอื่นๆ 8',
            17 => 'เอกสารอื่นๆ 9', 18 => 'เอกสารอื่นๆ 10'
        ];
        $descSlots = [9, 10, 11, 12, 13, 14, 15, 16, 17, 18];
    @endphp
    <div class="row">
        @foreach($docSlots as $i => $label)
            @php
                $docField = 'employee_doc_' . $i;
                $descField = 'other_doc_' . ($i - 8) . '_desc';
            @endphp
            <div class="{{ in_array($i, $descSlots) ? 'col-md-6' : 'col-md-4' }} mb-3">
                <label for="{{ $docField }}" class="form-label fw-bold">{{ $i }}. {{ $label }}
                    @if(isset($missingFields) && in_array($docField, $missingFields)) <i class="bi bi-exclamation-circle-fill text-warning ms-1" title="Required"></i> @endif
                </label>
                @if($employee->{$docField})
                    <div class="mb-2 d-flex gap-1">
                        <a href="{{ asset('storage/' . $employee->{$docField}) }}" target="_blank" class="btn btn-success btn-sm text-white"><i class="bi bi-eye-fill"></i> ดูไฟล์ปัจจุบัน</a>
                        <a href="{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => $docField]) }}" target="_blank" class="btn btn-danger btn-sm text-white"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                    </div>
                @endif
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control form-control-sm" id="{{ $docField }}" name="{{ $docField }}">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: '{{ $docField }}' } }))">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
                @if(in_array($i, $descSlots))
                    <input type="text" class="form-control form-control-sm mt-2" name="{{ $descField }}" placeholder="คำอธิบาย..." value="{{ old($descField, $employee->{$descField}) }}">
                @endif
            </div>
        @endforeach
    </div>


    <div class="mt-4 d-flex justify-content-end">
        <button type="button" class="btn btn-secondary me-2 btn-cancel-edit">ยกเลิก</button>
        <button type="submit" class="btn btn-primary">บันทึกข้อมูลพนักงาน</button>
    </div>
</form>
