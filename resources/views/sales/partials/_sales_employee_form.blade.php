{{--
    Partial: Full Employee Form for Sales Lead context
    Usage: @include('sales.partials._sales_employee_form', ['lead' => $lead, 'emp' => $emp])
    Variables:
        $lead - SalesLead model (always required)
        $emp  - SalesLeadEmployee model or null (null = create mode)
--}}
@php $sid = $lead->id; @endphp

{{-- Category 1: Personal Information --}}
<h6 class="fw-bold text-primary"><i class="bi bi-person-badge me-1"></i> 1. ข้อมูลส่วนตัว (Personal Information)</h6>
<hr class="mt-1 mb-3">
<div class="row g-2">
    {{-- Left Column --}}
    <div class="col-md-8">
        <div class="row g-2">
            <div class="col-md-6 mb-2">
                <label class="form-label small">คำนำหน้าชื่อ (ไทย)</label>
                <select class="form-select form-select-sm" id="employeeTitleTh-sl{{ $sid }}" name="employeeTitleTh">
                    <option value="">-- เลือก --</option>
                    <option value="นาย" {{ old('employeeTitleTh', $emp->employeeTitleTh ?? '') == 'นาย' ? 'selected' : '' }}>นาย</option>
                    <option value="นางสาว" {{ old('employeeTitleTh', $emp->employeeTitleTh ?? '') == 'นางสาว' ? 'selected' : '' }}>นางสาว</option>
                    <option value="นาง" {{ old('employeeTitleTh', $emp->employeeTitleTh ?? '') == 'นาง' ? 'selected' : '' }}>นาง</option>
                </select>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label small">ชื่อ-สกุล (ไทย)</label>
                <input type="text" class="form-control form-control-sm" id="employeeNameTh-sl{{ $sid }}" name="employeeNameTh" value="{{ old('employeeNameTh', $emp->employeeNameTh ?? '') }}">
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-6 mb-2">
                <label class="form-label small">Prefix (EN)</label>
                <select class="form-select form-select-sm" id="employeeTitleEn-sl{{ $sid }}" name="employeeTitleEn">
                    <option value="">-- Select --</option>
                    <option value="Mr." {{ old('employeeTitleEn', $emp->employeeTitleEn ?? '') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                    <option value="Miss" {{ old('employeeTitleEn', $emp->employeeTitleEn ?? '') == 'Miss' ? 'selected' : '' }}>Miss</option>
                    <option value="Mrs." {{ old('employeeTitleEn', $emp->employeeTitleEn ?? '') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                </select>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label small">Full Name (EN) <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm" id="employeeNameEn-sl{{ $sid }}" name="employeeNameEn" value="{{ old('employeeNameEn', $emp ? ($emp->getRawOriginal('employeeNameEn') ?? '') : '') }}" required>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-6 mb-2">
                <label class="form-label small">ต่อท้ายชื่อ (EN)</label>
                <input type="text" class="form-control form-control-sm" id="name_suffix-sl{{ $sid }}" name="name_suffix" value="{{ old('name_suffix', $emp->name_suffix ?? '') }}" placeholder="ข้อความต่อท้ายชื่อภาษาอังกฤษ">
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label small text-muted">ตัวอย่าง</label>
                <p class="form-control-plaintext small text-muted mb-0" style="padding-top: 0.25rem;">{{ ($emp->employeeTitleEn ?? 'Mr.') }} {{ ($emp ? ($emp->getRawOriginal('employeeNameEn') ?? 'SOMCHAI') : 'SOMCHAI') }} {{ ($emp->name_suffix ?? '') ? '(' . $emp->name_suffix . ')' : '' }}</p>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-6 mb-2">
                <label class="form-label small">ส่วนสูง (cm)</label>
                <input type="text" class="form-control form-control-sm" id="height-sl{{ $sid }}" name="height" value="{{ old('height', $emp->height ?? '') }}">
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label small">น้ำหนัก (kg)</label>
                <input type="text" class="form-control form-control-sm" id="weight-sl{{ $sid }}" name="weight" value="{{ old('weight', $emp->weight ?? '') }}">
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-6 mb-2">
                <label class="form-label small">ชื่อพ่อ</label>
                <input type="text" class="form-control form-control-sm" id="father_name-sl{{ $sid }}" name="father_name" value="{{ old('father_name', $emp->father_name ?? '') }}">
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label small">ชื่อแม่</label>
                <input type="text" class="form-control form-control-sm" id="mother_name-sl{{ $sid }}" name="mother_name" value="{{ old('mother_name', $emp->mother_name ?? '') }}">
            </div>
        </div>
        <div class="row g-2">
            <div class="col-md-4 mb-2">
                <label class="form-label small">เพศ</label>
                <input type="text" class="form-control form-control-sm" id="employeeGender-sl{{ $sid }}" name="employeeGender" value="{{ old('employeeGender', $emp->employeeGender ?? '') }}" readonly>
            </div>
            <div class="col-md-5 mb-2">
                <label class="form-label small">วันเดือนปีเกิด</label>
                <input type="date" class="form-control form-control-sm" id="employeeDob-sl{{ $sid }}" name="employeeDob" value="{{ old('employeeDob', ($emp && $emp->employeeDob) ? $emp->employeeDob->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label small">อายุ</label>
                <input type="text" class="form-control form-control-sm" id="employeeAge-sl{{ $sid }}" name="employeeAge" value="{{ old('employeeAge', $emp->employeeAge ?? '') }}" readonly>
            </div>
        </div>
    </div>
    {{-- Right Column: Photo --}}
    <div class="col-md-4 d-flex flex-column align-items-center">
        <label class="form-label small">รูปภาพพนักงาน</label>
    <div class="sales-photo-upload">
        <img id="photoPreview-sl{{ $sid }}" src="{{ $emp && $emp->photo_path ? asset('storage/'.$emp->photo_path) : 'https://placehold.co/150x180/f8f9fa/6c757d?text=Photo' }}" class="img-thumbnail mb-2" style="width:120px;height:150px;object-fit:cover;">
        <div class="input-group input-group-sm">
            <input type="file" class="form-control form-control-sm" id="photoInput-sl{{ $sid }}" name="photo" accept="image/*" onchange="previewSalesPhoto(this)">
            <button type="button" class="btn btn-outline-secondary" title="สแกน/ถ่ายรูป" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'photoInput-sl{{ $sid }}' } }))">
                <i class="bi bi-camera"></i>
            </button>
        </div>
    </div>
        @if($emp && $emp->photo_path)
            <a href="{{ asset('storage/' . $emp->photo_path) }}" target="_blank" class="small text-info mt-1"><i class="bi bi-eye me-1"></i>ดูไฟล์ปัจจุบัน</a>
        @endif
    </div>
</div>

{{-- Category 2: Contact & Nationality --}}
<h6 class="fw-bold text-primary mt-3"><i class="bi bi-telephone-fill me-1"></i> 2. ข้อมูลการติดต่อและสัญชาติ (Contact & Nationality)</h6>
<hr class="mt-1 mb-3">
<div class="row g-2">
    <div class="col-md-4 mb-2">
        <label class="form-label small">เบอร์โทรศัพท์</label>
        <input type="tel" class="form-control form-control-sm" id="employeePhone-sl{{ $sid }}" name="employeePhone" value="{{ old('employeePhone', $emp->employeePhone ?? '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">สัญชาติ</label>
        <select class="form-select form-select-sm" id="employeeNationality-sl{{ $sid }}" name="employeeNationality">
            <option value="">-- เลือกสัญชาติ --</option>
            <option value="เมียนมา" {{ old('employeeNationality', $emp->employeeNationality ?? '') == 'เมียนมา' ? 'selected' : '' }}>เมียนมา</option>
            <option value="ลาว" {{ old('employeeNationality', $emp->employeeNationality ?? '') == 'ลาว' ? 'selected' : '' }}>ลาว</option>
            <option value="กัมพูชา" {{ old('employeeNationality', $emp->employeeNationality ?? '') == 'กัมพูชา' ? 'selected' : '' }}>กัมพูชา</option>
            <option value="เวียดนาม" {{ old('employeeNationality', $emp->employeeNationality ?? '') == 'เวียดนาม' ? 'selected' : '' }}>เวียดนาม</option>
        </select>
    </div>
    <div class="col-md-4 mb-2 d-none" id="passportTypeContainer-sl{{ $sid }}">
        <label class="form-label small">ประเภทหนังสือเดินทาง (เมียนมา)</label>
        <select class="form-select form-select-sm" id="passportType-sl{{ $sid }}" name="passportType">
            <option value="">-- เลือกประเภท --</option>
            <option value="PJ" {{ old('passportType', $emp->passportType ?? '') == 'PJ' ? 'selected' : '' }}>เล่ม PJ</option>
            <option value="CI" {{ old('passportType', $emp->passportType ?? '') == 'CI' ? 'selected' : '' }}>เล่ม CI</option>
        </select>
    </div>
    <div class="col-md-4 mb-2 d-none" id="passportTypeCambodiaContainer-sl{{ $sid }}">
        <label class="form-label small">ประเภทหนังสือเดินทาง (กัมพูชา)</label>
        <select class="form-select form-select-sm" id="passport_type_cambodia-sl{{ $sid }}" name="passport_type_cambodia">
            <option value="">-- เลือกประเภท --</option>
            <option value="เล่ม TD" {{ old('passport_type_cambodia', $emp->passport_type_cambodia ?? '') == 'เล่ม TD' ? 'selected' : '' }}>เล่ม TD</option>
            <option value="เล่มอินเตอร์" {{ old('passport_type_cambodia', $emp->passport_type_cambodia ?? '') == 'เล่มอินเตอร์' ? 'selected' : '' }}>เล่มอินเตอร์</option>
        </select>
    </div>
</div>

{{-- Category 3: Passport & Visa --}}
<h6 class="fw-bold text-primary mt-3"><i class="bi bi-passport me-1"></i> 3. ข้อมูลหนังสือเดินทางและวีซ่า (Passport & Visa)</h6>
<hr class="mt-1 mb-3">
<div class="row g-2">
    <div class="col-md-3 mb-2">
        <label class="form-label small">เลขพาสปอร์ต</label>
        <input type="text" class="form-control form-control-sm" id="employeePassport-sl{{ $sid }}" name="employeePassport" value="{{ old('employeePassport', $emp->employeePassport ?? '') }}">
    </div>
    <div class="col-md-3 mb-2">
        <label class="form-label small">สถานที่ออกพาสปอร์ต</label>
        <input type="text" class="form-control form-control-sm" id="passport_issue_place-sl{{ $sid }}" name="passport_issue_place" value="{{ old('passport_issue_place', $emp->passport_issue_place ?? '') }}">
    </div>
    <div class="col-md-3 mb-2">
        <label class="form-label small">วันออกพาสปอร์ต</label>
        <input type="date" class="form-control form-control-sm" id="passport_issue_date-sl{{ $sid }}" name="passport_issue_date" value="{{ old('passport_issue_date', ($emp && $emp->passport_issue_date) ? $emp->passport_issue_date->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-3 mb-2">
        <label class="form-label small">วันหมดอายุพาสปอร์ต</label>
        <input type="date" class="form-control form-control-sm" id="passportExpiryDate-sl{{ $sid }}" name="passportExpiryDate" value="{{ old('passportExpiryDate', ($emp && $emp->passportExpiryDate) ? $emp->passportExpiryDate->format('Y-m-d') : '') }}">
    </div>
</div>
<div class="row g-2">
    <div class="col-md-3 mb-2">
        <label class="form-label small">เลขบัตรชมพู</label>
        <input type="text" class="form-control form-control-sm" id="pinkCardNo-sl{{ $sid }}" name="pinkCardNo" value="{{ old('pinkCardNo', $emp->pinkCardNo ?? '') }}">
    </div>
    <div class="col-md-3 mb-2">
        <label class="form-label small">ประเภทวีซ่า</label>
        <input type="text" class="form-control form-control-sm" id="visaType-sl{{ $sid }}" name="visaType" value="{{ old('visaType', $emp->visaType ?? '') }}">
    </div>
    <div class="col-md-3 mb-2">
        <label class="form-label small">สถานที่ออกวีซ่า</label>
        <input type="text" class="form-control form-control-sm" id="visa_issue_place-sl{{ $sid }}" name="visa_issue_place" value="{{ old('visa_issue_place', $emp->visa_issue_place ?? '') }}">
    </div>
    <div class="col-md-3 mb-2">
        <label class="form-label small fw-bold text-danger">🔴 วันหมดอายุวีซ่า</label>
        <input type="date" class="form-control form-control-sm border-danger" style="background-color:#fff5f5" id="visaExpiryDate-sl{{ $sid }}" name="visaExpiryDate" value="{{ old('visaExpiryDate', ($emp && $emp->visaExpiryDate) ? $emp->visaExpiryDate->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-3 mb-2">
        <label class="form-label small text-info">🔵 วันที่ตรวจลงตราวีซ่า (ประทับ)</label>
        <input type="date" class="form-control form-control-sm border-info" style="background-color:#f0f9ff" id="visaEndorsementDate-sl{{ $sid }}" name="visaEndorsementDate" value="{{ old('visaEndorsementDate', ($emp && $emp->visaEndorsementDate) ? $emp->visaEndorsementDate->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-3 mb-2">
        <label class="form-label small text-info">🔵 เลขที่ตรวจลงตราวีซ่า</label>
        <input type="text" class="form-control form-control-sm border-info" style="background-color:#f0f9ff" id="visaEndorsementNo-sl{{ $sid }}" name="visaEndorsementNo" maxlength="50" value="{{ old('visaEndorsementNo', $emp->visaEndorsementNo ?? '') }}">
    </div>
</div>

{{-- Category 4: Employment & Work IDs --}}
<h6 class="fw-bold text-primary mt-3"><i class="bi bi-briefcase-fill me-1"></i> 4. ข้อมูลการจ้างงานและเอกสาร (Employment & Work IDs)</h6>
<hr class="mt-1 mb-3">
<div class="row g-2">
    <div class="col-md-4 mb-2">
        <label class="form-label small">ตำแหน่งงาน</label>
        <input type="text" class="form-control form-control-sm" id="job_title-sl{{ $sid }}" name="job_title" value="{{ old('job_title', $emp->job_title ?? '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">ลักษณะงาน</label>
        <input type="text" class="form-control form-control-sm" id="job_description-sl{{ $sid }}" name="job_description" value="{{ old('job_description', $emp->job_description ?? '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">วันที่เริ่มงาน</label>
        <input type="date" class="form-control form-control-sm" id="startDate-sl{{ $sid }}" name="startDate" value="{{ old('startDate', ($emp && $emp->startDate) ? $emp->startDate->format('Y-m-d') : '') }}">
    </div>
</div>
<div class="row g-2">
    <div class="col-md-4 mb-2">
        <label class="form-label small">เลข Work Permit</label>
        <input type="text" class="form-control form-control-sm" id="employeeWorkPermit-sl{{ $sid }}" name="employeeWorkPermit" value="{{ old('employeeWorkPermit', $emp->employeeWorkPermit ?? '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">วันหมดอายุ Work Permit</label>
        <input type="date" class="form-control form-control-sm" id="workPermitExpiryDate-sl{{ $sid }}" name="workPermitExpiryDate" value="{{ old('workPermitExpiryDate', ($emp && $emp->workPermitExpiryDate) ? $emp->workPermitExpiryDate->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">วันรายงานตัว 90 วัน</label>
        <input type="date" class="form-control form-control-sm" id="ninetyDayReportDate-sl{{ $sid }}" name="ninetyDayReportDate" value="{{ old('ninetyDayReportDate', ($emp && $emp->ninetyDayReportDate) ? $emp->ninetyDayReportDate->format('Y-m-d') : '') }}">
    </div>
</div>
<div class="row g-2">
    <div class="col-md-6 mb-2">
        <label class="form-label small">ประเภทใบอนุญาตทำงาน</label>
        <select class="form-select form-select-sm" id="workPermitMOUGroup-sl{{ $sid }}" name="workPermitMOUGroup">
            <option value="">-- กรุณาเลือก --</option>
            <option value="MOU" {{ old('workPermitMOUGroup', $emp->workPermitMOUGroup ?? '') == 'MOU' ? 'selected' : '' }}>MOU</option>
            <option value="MOU 2 ปีหลัง" {{ old('workPermitMOUGroup', $emp->workPermitMOUGroup ?? '') == 'MOU 2 ปีหลัง' ? 'selected' : '' }}>MOU 2 ปีหลัง</option>
            <option value="มติต่ออายุในประเทศ" {{ old('workPermitMOUGroup', $emp->workPermitMOUGroup ?? '') == 'มติต่ออายุในประเทศ' ? 'selected' : '' }}>มติต่ออายุในประเทศ</option>
            <option value="มติขึ้นทะเบียน" {{ old('workPermitMOUGroup', $emp->workPermitMOUGroup ?? '') == 'มติขึ้นทะเบียน' ? 'selected' : '' }}>มติขึ้นทะเบียน</option>
            <option value="อื่นๆ" {{ old('workPermitMOUGroup', $emp->workPermitMOUGroup ?? '') == 'อื่นๆ' ? 'selected' : '' }}>อื่นๆ ระบุ..</option>
        </select>
    </div>
    <div class="col-md-6 mb-2 d-none" id="workPermitMOUGroupOtherContainer-sl{{ $sid }}">
        <label class="form-label small">ระบุประเภทอื่นๆ</label>
        <input type="text" class="form-control form-control-sm" id="workPermitMOUGroupOther-sl{{ $sid }}" name="workPermitMOUGroupOther" value="{{ old('workPermitMOUGroupOther', $emp->workPermitMOUGroupOther ?? '') }}">
    </div>
</div>
<div class="row g-2">
    <div class="col-md-4 mb-2">
        <label class="form-label small">เลข RA จากระบบ outsource</label>
        <input type="text" class="form-control form-control-sm" id="name_list_number-sl{{ $sid }}" name="name_list_number" value="{{ old('name_list_number', $emp->name_list_number ?? '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">เลขที่คำขอ</label>
        <input type="text" class="form-control form-control-sm" id="request_number-sl{{ $sid }}" name="request_number" value="{{ old('request_number', $emp->request_number ?? '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">เลขประจำตัว</label>
        <input type="text" class="form-control form-control-sm" id="employee_id_number-sl{{ $sid }}" name="employee_id_number" value="{{ old('employee_id_number', $emp->employee_id_number ?? '') }}">
    </div>
</div>
<div class="row g-2">
    <div class="col-md-4 mb-2">
        <label class="form-label small">เลขประจำตัวผู้เสียภาษี</label>
        <input type="text" class="form-control form-control-sm" id="tax_id_number-sl{{ $sid }}" name="tax_id_number" value="{{ old('tax_id_number', $emp->tax_id_number ?? '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">รหัสคนงาน - ของนายจ้าง</label>
        <input type="text" class="form-control form-control-sm" id="employer_employee_id-sl{{ $sid }}" name="employer_employee_id" value="{{ old('employer_employee_id', $emp->employer_employee_id ?? '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">แผนก (Department)</label>
        <input type="text" class="form-control form-control-sm" id="department-sl{{ $sid }}" name="department" value="{{ old('department', $emp->department ?? '') }}">
    </div>
</div>
<div class="row g-2">
    <div class="col-md-4 mb-2">
        <label class="form-label small">เลขอ้างอิงคนงาน</label>
        <input type="text" class="form-control form-control-sm" id="employee_reference_id-sl{{ $sid }}" name="employee_reference_id" value="{{ old('employee_reference_id', $emp->employee_reference_id ?? '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">ชื่อธนาคาร</label>
        <input type="text" class="form-control form-control-sm" id="bank_name-sl{{ $sid }}" name="bank_name" value="{{ old('bank_name', $emp->bank_name ?? '') }}">
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">เลขบัญชีธนาคาร</label>
        <input type="text" class="form-control form-control-sm" id="bank_account_number-sl{{ $sid }}" name="bank_account_number" value="{{ old('bank_account_number', $emp->bank_account_number ?? '') }}">
    </div>
</div>

{{-- Category 5: Health Insurance --}}
<h6 class="fw-bold text-primary mt-3"><i class="bi bi-heart-pulse me-1"></i> 5. ข้อมูลประกันสุขภาพ (Health Insurance)</h6>
<hr class="mt-1 mb-3">
<div class="row g-2">
    <div class="col-md-4 mb-2">
        <label class="form-label small">ประเภทประกัน</label>
        <select class="form-select form-select-sm" id="insurance_type-sl{{ $sid }}" name="insurance_type">
            <option value="">-- เลือกประเภท --</option>
            <option value="ประกันสังคม" {{ old('insurance_type', $emp->insurance_type ?? '') == 'ประกันสังคม' ? 'selected' : '' }}>ประกันสังคม</option>
            <option value="ประกันโรงพยาบาล" {{ old('insurance_type', $emp->insurance_type ?? '') == 'ประกันโรงพยาบาล' ? 'selected' : '' }}>ประกันโรงพยาบาล</option>
            <option value="ประกันเอกชน" {{ old('insurance_type', $emp->insurance_type ?? '') == 'ประกันเอกชน' ? 'selected' : '' }}>ประกันเอกชน</option>
        </select>
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">ใบรับรองแพทย์</label>
        <input type="file" class="form-control form-control-sm" name="medical_certificate_path" accept=".pdf,.jpg,.jpeg,.png">
        @if($emp && $emp->medical_certificate_path)
            <a href="{{ asset('storage/' . $emp->medical_certificate_path) }}" target="_blank" class="small text-info"><i class="bi bi-eye me-1"></i>ดูไฟล์ปัจจุบัน</a>
        @endif
    </div>
    <div class="col-md-4 mb-2">
        <label class="form-label small">โรงพยาบาลที่ตรวจโรค</label>
        <input type="text" class="form-control form-control-sm" id="medical_hospital_name-sl{{ $sid }}" name="medical_hospital_name" value="{{ old('medical_hospital_name', $emp->medical_hospital_name ?? '') }}">
    </div>
</div>

{{-- Social Security Section --}}
<div id="insuranceSocialSecurity-sl{{ $sid }}" class="d-none">
    <div class="row g-2">
        <div class="col-md-4 mb-2">
            <label class="form-label small">เลขประกันสังคม</label>
            <input type="text" class="form-control form-control-sm" id="social_security_number-sl{{ $sid }}" name="social_security_number" value="{{ old('social_security_number', $emp->social_security_number ?? '') }}">
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label small">สิทธิ์โรงพยาบาล</label>
            <input type="text" class="form-control form-control-sm" name="insurance_detail_social" value="{{ old('insurance_detail_social', $emp->insurance_detail_social ?? '') }}">
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label small">แนบไฟล์เอกสารประกัน</label>
            <input type="file" class="form-control form-control-sm" name="insurance_document_path_social" accept=".pdf,.jpg,.jpeg,.png">
            @if($emp && $emp->insurance_document_path_social)
                <a href="{{ asset('storage/' . $emp->insurance_document_path_social) }}" target="_blank" class="small text-info"><i class="bi bi-eye me-1"></i>ดูไฟล์ปัจจุบัน</a>
            @endif
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label small">วันที่ออกบัตร (Issue Date)</label>
            <input type="date" class="form-control form-control-sm" id="sso_issue_date-sl{{ $sid }}" name="sso_issue_date" value="{{ old('sso_issue_date', ($emp && $emp->sso_issue_date) ? $emp->sso_issue_date->format('Y-m-d') : '') }}">
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label small">วันหมดอายุ (Expiry Date)</label>
            <input type="date" class="form-control form-control-sm" id="sso_expiry_date-sl{{ $sid }}" name="sso_expiry_date" value="{{ old('sso_expiry_date', ($emp && $emp->sso_expiry_date) ? $emp->sso_expiry_date->format('Y-m-d') : '') }}">
        </div>
    </div>
</div>

{{-- Hospital Insurance Section --}}
<div id="insuranceHospital-sl{{ $sid }}" class="d-none">
    <div class="row g-2">
        <div class="col-md-4 mb-2">
            <label class="form-label small">ชื่อโรงพยาบาล</label>
            <input type="text" class="form-control form-control-sm" name="insurance_detail_hospital" value="{{ old('insurance_detail_hospital', $emp->insurance_detail_hospital ?? '') }}">
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label small">วันหมดอายุประกัน</label>
            <input type="date" class="form-control form-control-sm" name="insurance_expiry_date_hospital" value="{{ old('insurance_expiry_date_hospital', ($emp && $emp->insurance_expiry_date_hospital) ? $emp->insurance_expiry_date_hospital->format('Y-m-d') : '') }}">
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label small">แนบไฟล์เอกสารประกัน</label>
            <input type="file" class="form-control form-control-sm" name="insurance_document_path_hospital" accept=".pdf,.jpg,.jpeg,.png">
            @if($emp && $emp->insurance_document_path_hospital)
                <a href="{{ asset('storage/' . $emp->insurance_document_path_hospital) }}" target="_blank" class="small text-info"><i class="bi bi-eye me-1"></i>ดูไฟล์ปัจจุบัน</a>
            @endif
        </div>
    </div>
</div>

{{-- Private Insurance Section --}}
<div id="insurancePrivate-sl{{ $sid }}" class="d-none">
    <div class="row g-2">
        <div class="col-md-4 mb-2">
            <label class="form-label small">บริษัทประกัน</label>
            <input type="text" class="form-control form-control-sm" name="insurance_detail_private" value="{{ old('insurance_detail_private', $emp->insurance_detail_private ?? '') }}">
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label small">วันหมดอายุประกัน</label>
            <input type="date" class="form-control form-control-sm" name="insurance_expiry_date_private" value="{{ old('insurance_expiry_date_private', ($emp && $emp->insurance_expiry_date_private) ? $emp->insurance_expiry_date_private->format('Y-m-d') : '') }}">
        </div>
        <div class="col-md-4 mb-2">
            <label class="form-label small">แนบไฟล์เอกสารประกัน</label>
            <input type="file" class="form-control form-control-sm" name="insurance_document_path_private" accept=".pdf,.jpg,.jpeg,.png">
            @if($emp && $emp->insurance_document_path_private)
                <a href="{{ asset('storage/' . $emp->insurance_document_path_private) }}" target="_blank" class="small text-info"><i class="bi bi-eye me-1"></i>ดูไฟล์ปัจจุบัน</a>
            @endif
        </div>
    </div>
</div>

{{-- Category 6: File Attachments --}}
<h6 class="fw-bold text-primary mt-3"><i class="bi bi-file-earmark-arrow-up-fill me-1"></i> 6. ส่วนแนบไฟล์เอกสาร (File Attachments)</h6>
<hr class="mt-1 mb-3">
<div class="row g-2">
    @php
        $docs = [
            1 => '1. พาสปอร์ต',
            2 => '2. วีซ่า',
            3 => '3. ใบเสร็จ Work Permit',
            4 => '4. บัตรชมพู',
            5 => '5. ทร. 38',
            6 => '6. รายงานตัว 90 วัน',
            7 => '7. ใบแจ้งที่พักอาศัย',
            8 => '8. เอกสารบ้านเกิด'
        ];
    @endphp

    @foreach($docs as $i => $label)
        <div class="col-md-4 mb-2">
            <label class="form-label small">{{ $label }}</label>
            <div class="input-group input-group-sm">
                <input type="file" class="form-control form-control-sm" id="sl{{ $sid }}_doc_{{ $i }}" name="employee_doc_{{ $i }}" accept=".pdf,.jpg,.jpeg,.png">
                <button type="button" class="btn btn-outline-secondary" title="สแกนเอกสาร" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'sl{{ $sid }}_doc_{{ $i }}' } }))">
                    <i class="bi bi-camera"></i>
                </button>
            </div>
            @if($emp && $emp->{'employee_doc_'.$i})
                <a href="{{ asset('storage/' . $emp->{'employee_doc_'.$i}) }}" target="_blank" class="small text-info"><i class="bi bi-eye me-1"></i>ดูไฟล์ปัจจุบัน</a>
            @endif
        </div>
    @endforeach

    @for($i = 1; $i <= 10; $i++)
        @php
            $docIndex = $i + 8;
            $fieldName = "employee_doc_" . $docIndex;
            $descName = "other_doc_" . $i . "_desc";
        @endphp
        <div class="col-md-6 mb-2">
            <label class="form-label small">{{ $docIndex }}. เอกสารอื่นๆ {{ $i }}</label>
            <div class="input-group input-group-sm">
                <input type="file" class="form-control form-control-sm" id="sl{{ $sid }}_doc_{{ $docIndex }}" name="{{ $fieldName }}" accept=".pdf,.jpg,.jpeg,.png">
                <button type="button" class="btn btn-outline-secondary" title="สแกนเอกสาร" onclick="document.dispatchEvent(new CustomEvent('open-document-scanner', { detail: { inputId: 'sl{{ $sid }}_doc_{{ $docIndex }}' } }))">
                    <i class="bi bi-camera"></i>
                </button>
            </div>
            @if($emp && $emp->$fieldName)
                <a href="{{ asset('storage/' . $emp->$fieldName) }}" target="_blank" class="small text-info"><i class="bi bi-eye me-1"></i>ดูไฟล์ปัจจุบัน</a>
            @endif
            <input type="text" class="form-control form-control-sm mt-1" name="{{ $descName }}" value="{{ old($descName, $emp->$descName ?? '') }}" placeholder="ระบุรายละเอียดเอกสาร">
        </div>
    @endfor
</div>

{{-- JavaScript: Core init function (once) --}}
@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all sales employee forms on the page
    document.querySelectorAll('[id^="employeeTitleTh-sl"]').forEach(function(el) {
        var sid = el.id.replace('employeeTitleTh-sl', '');
        if (typeof initSalesEmployeeForm === 'function') initSalesEmployeeForm(sid);
    });
});

function initSalesEmployeeForm(sid) {
    var titleThEl = document.getElementById('employeeTitleTh-sl' + sid);
    var titleEnEl = document.getElementById('employeeTitleEn-sl' + sid);
    var genderEl = document.getElementById('employeeGender-sl' + sid);
    var dobEl = document.getElementById('employeeDob-sl' + sid);
    var ageEl = document.getElementById('employeeAge-sl' + sid);
    var nationalityEl = document.getElementById('employeeNationality-sl' + sid);
    var passportTypeContainer = document.getElementById('passportTypeContainer-sl' + sid);
    var passportTypeCambodiaContainer = document.getElementById('passportTypeCambodiaContainer-sl' + sid);
    var insuranceTypeEl = document.getElementById('insurance_type-sl' + sid);
    var insuranceSocial = document.getElementById('insuranceSocialSecurity-sl' + sid);
    var insuranceHospital = document.getElementById('insuranceHospital-sl' + sid);
    var insurancePrivate = document.getElementById('insurancePrivate-sl' + sid);
    var mouGroupEl = document.getElementById('workPermitMOUGroup-sl' + sid);
    var mouOtherContainer = document.getElementById('workPermitMOUGroupOtherContainer-sl' + sid);

    // Title TH → EN mapping
    var thToEnMap = { 'นาย': 'Mr.', 'นางสาว': 'Miss', 'นาง': 'Mrs.' };
    var enToThMap = { 'Mr.': 'นาย', 'Miss': 'นางสาว', 'Mrs.': 'นาง' };
    var thGenderMap = { 'นาย': 'ชาย', 'นางสาว': 'หญิง', 'นาง': 'หญิง' };
    var enGenderMap = { 'Mr.': 'Male', 'Miss': 'Female', 'Mrs.': 'Female' };

    // Thai title selected → sync English title + Gender
    function updateFromTh() {
        if (!titleThEl) return;
        var val = titleThEl.value;
        if (titleEnEl && thToEnMap[val]) titleEnEl.value = thToEnMap[val];
        if (genderEl && thGenderMap[val]) genderEl.value = thGenderMap[val];
        else if (genderEl) genderEl.value = '';
    }

    // English title selected → sync Thai title + Gender
    function updateFromEn() {
        if (!titleEnEl) return;
        var val = titleEnEl.value;
        if (titleThEl && enToThMap[val]) titleThEl.value = enToThMap[val];
        if (genderEl && enGenderMap[val]) genderEl.value = enGenderMap[val];
        else if (genderEl) genderEl.value = '';
    }

    if (titleThEl) {
        titleThEl.addEventListener('change', updateFromTh);
        if (titleThEl.value && !genderEl.value) updateFromTh();
    }
    if (titleEnEl) {
        titleEnEl.addEventListener('change', updateFromEn);
        if (titleEnEl.value && !genderEl.value) updateFromEn();
    }

    // Age calculation from DOB
    function calculateAge() {
        if (!dobEl || !ageEl) return;
        var dob = dobEl.value;
        if (!dob) { ageEl.value = ''; return; }
        var birthDate = new Date(dob);
        var today = new Date();
        var age = today.getFullYear() - birthDate.getFullYear();
        var m = today.getMonth() - birthDate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) age--;
        ageEl.value = age >= 0 ? age : '';
    }

    if (dobEl) {
        dobEl.addEventListener('change', calculateAge);
        if (dobEl.value) calculateAge();
    }

    // Nationality-dependent passport type toggle
    function togglePassportType() {
        if (!nationalityEl) return;
        var val = nationalityEl.value;
        if (passportTypeContainer) {
            if (val === 'เมียนมา') passportTypeContainer.classList.remove('d-none');
            else passportTypeContainer.classList.add('d-none');
        }
        if (passportTypeCambodiaContainer) {
            if (val === 'กัมพูชา') passportTypeCambodiaContainer.classList.remove('d-none');
            else passportTypeCambodiaContainer.classList.add('d-none');
        }
    }

    if (nationalityEl) {
        nationalityEl.addEventListener('change', togglePassportType);
        togglePassportType(); // Initial state
    }

    // Insurance type conditional sections
    function toggleInsuranceSections() {
        if (!insuranceTypeEl) return;
        var val = insuranceTypeEl.value;
        if (insuranceSocial) {
            if (val === 'ประกันสังคม') insuranceSocial.classList.remove('d-none');
            else insuranceSocial.classList.add('d-none');
        }
        if (insuranceHospital) {
            if (val === 'ประกันโรงพยาบาล') insuranceHospital.classList.remove('d-none');
            else insuranceHospital.classList.add('d-none');
        }
        if (insurancePrivate) {
            if (val === 'ประกันเอกชน') insurancePrivate.classList.remove('d-none');
            else insurancePrivate.classList.add('d-none');
        }
    }

    if (insuranceTypeEl) {
        insuranceTypeEl.addEventListener('change', toggleInsuranceSections);
        toggleInsuranceSections(); // Initial state
    }

    // MOU group "other" toggle
    function toggleMOUOther() {
        if (!mouGroupEl || !mouOtherContainer) return;
        if (mouGroupEl.value === 'อื่นๆ') mouOtherContainer.classList.remove('d-none');
        else mouOtherContainer.classList.add('d-none');
    }

    if (mouGroupEl) {
        mouGroupEl.addEventListener('change', toggleMOUOther);
        toggleMOUOther(); // Initial state
    }
}

// Generic photo preview — works for any lead by finding sibling preview img
function previewSalesPhoto(input) {
    var container = input.closest('.sales-photo-upload');
    if (!container) return;
    var preview = container.querySelector('img');
    if (input.files && input.files[0] && preview) {
        var reader = new FileReader();
        reader.onload = function(e) { preview.src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
@endonce
