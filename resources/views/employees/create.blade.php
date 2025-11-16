@extends('layouts.app')

@section('title', 'เพิ่มพนักงานใหม่')

@section('content')
<div class="content-section">
    @if(isset($employer) && $employer)
        <h2 class="mb-4">เพิ่มพนักงานสำหรับ {{ $employer->employerNameTh }}</h2>
    @else
        <h2 class="mb-4">เพิ่มพนักงานใหม่</h2>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        @if(request()->has('employer_id'))
            <input type="hidden" name="source_employer_id" value="{{ request('employer_id') }}">
        @endif

        @if(isset($employer) && $employer)
            <input type="hidden" name="employer_id" value="{{ $employer->id }}">
        @else
            <div class="row mb-4">
                <div class="col-md-12">
                    <label for="employer_id" class="form-label">เลือกนายจ้าง <span class="text-danger">*</span></label>
                    <select class="form-select" id="employer_id" name="employer_id" required>
                        <option value="">-- กรุณาเลือกนายจ้าง --</option>
                        @foreach($employers as $emp)
                            <option value="{{ $emp->id }}" {{ old('employer_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->employerNameTh }} ({{ $emp->employerNameEn }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        {{-- Category 1: Personal Information --}}
        <h5><i class="bi bi-person-badge"></i> 1. ข้อมูลส่วนตัว (Personal Information)</h5>
        <hr class="mb-4">
        <div class="row">
            {{-- Left Column --}}
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="employeeTitleTh" class="form-label">คำนำหน้าชื่อ (ไทย) <span class="text-danger">*</span></label>
                        <select class="form-select" id="employeeTitleTh" name="employeeTitleTh" required>
                            <option value="นาย" {{ old('employeeTitleTh') == 'นาย' ? 'selected' : '' }}>นาย</option>
                            <option value="นางสาว" {{ old('employeeTitleTh') == 'นางสาว' ? 'selected' : '' }}>นางสาว</option>
                            <option value="นาง" {{ old('employeeTitleTh') == 'นาง' ? 'selected' : '' }}>นาง</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="employeeNameTh" class="form-label">ชื่อ-สกุล (ไทย) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="employeeNameTh" name="employeeNameTh" value="{{ old('employeeNameTh') }}" required>
                    </div>
                </div>

                <div class="row">
                     <div class="col-md-6 mb-3">
                        <label for="employeeTitleEn" class="form-label">Prefix (EN)</label>
                        <select class="form-select" id="employeeTitleEn" name="employeeTitleEn">
                             <option value="Mr." {{ old('employeeTitleEn') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                             <option value="Miss" {{ old('employeeTitleEn') == 'Miss' ? 'selected' : '' }}>Miss</option>
                             <option value="Mrs." {{ old('employeeTitleEn') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="employeeNameEn" class="form-label">Full Name (EN)</label>
                        <input type="text" class="form-control" id="employeeNameEn" name="employeeNameEn" value="{{ old('employeeNameEn') }}">
                    </div>
                </div>

                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="father_name" class="form-label">ชื่อพ่อ</label>
                        <input type="text" class="form-control" id="father_name" name="father_name" value="{{ old('father_name') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="mother_name" class="form-label">ชื่อแม่</label>
                        <input type="text" class="form-control" id="mother_name" name="mother_name" value="{{ old('mother_name') }}">
                    </div>
                </div>

                <div class="row">
                     <div class="col-md-4 mb-3">
                        <label for="employeeGender" class="form-label">เพศ</label>
                        <input type="text" class="form-control" id="employeeGender" name="employeeGender" value="{{ old('employeeGender') }}" readonly>
                     </div>
                     <div class="col-md-5 mb-3">
                        <label for="employeeDob" class="form-label">วันเดือนปีเกิด</label>
                        <input type="date" class="form-control" id="employeeDob" name="employeeDob" value="{{ old('employeeDob') }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="employeeAge" class="form-label">อายุ</label>
                        <input type="text" class="form-control" id="employeeAge" name="employeeAge" value="{{ old('employeeAge') }}" readonly>
                    </div>
                </div>
            </div>
            {{-- Right Column --}}
            <div class="col-md-4 text-center">
                <label for="employeePhoto" class="form-label">รูปภาพพนักงาน</label>
                <img id="employeePhotoPreview" src="https://placehold.co/150x180/f8fafc/6c757d?text=Photo" class="img-thumbnail mb-2" style="max-width: 150px; height: 180px; object-fit: cover;">
                <input type="file" class="form-control form-control-sm" id="employeePhoto" name="employeePhoto" accept="image/*" capture="environment">
            </div>
        </div>


        {{-- Category 2: Contact & Nationality --}}
        <h5 class="mt-4"><i class="bi bi-telephone-fill"></i> 2. ข้อมูลการติดต่อและสัญชาติ (Contact & Nationality)</h5>
        <hr class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="employeePhone" class="form-label">เบอร์โทรศัพท์</label>
                <input type="tel" class="form-control" id="employeePhone" name="employeePhone" value="{{ old('employeePhone') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="employeeNationality" class="form-label">สัญชาติ</label>
                <select class="form-select" id="employeeNationality" name="employeeNationality">
                    <option value="">-- เลือกสัญชาติ --</option>
                    <option value="เมียนมา" {{ old('employeeNationality') == 'เมียนมา' ? 'selected' : '' }}>เมียนมา</option>
                    <option value="ลาว" {{ old('employeeNationality') == 'ลาว' ? 'selected' : '' }}>ลาว</option>
                    <option value="กัมพูชา" {{ old('employeeNationality') == 'กัมพูชา' ? 'selected' : '' }}>กัมพูชา</option>
                    <option value="เวียดนาม" {{ old('employeeNationality') == 'เวียดนาม' ? 'selected' : '' }}>เวียดนาม</option>
                </select>
            </div>
             <div class="col-md-4 mb-3 d-none" id="passportTypeContainer">
                <label for="passportType" class="form-label">ประเภทหนังสือเดินทาง (สำหรับเมียนมา)</label>
                <select class="form-select" id="passportType" name="passportType">
                    <option value="">-- เลือกประเภท --</option>
                    <option value="PJ" {{ old('passportType') == 'PJ' ? 'selected' : '' }}>เล่ม PJ</option>
                    <option value="CI" {{ old('passportType') == 'CI' ? 'selected' : '' }}>เล่ม CI</option>
                </select>
            </div>
            <div class="col-md-4 mb-3 d-none" id="passportTypeCambodiaContainer">
                <label for="passport_type_cambodia" class="form-label">ประเภทหนังสือเดินทาง (สำหรับกัมพูชา)</label>
                <select class="form-select" id="passport_type_cambodia" name="passport_type_cambodia">
                    <option value="">-- เลือกประเภท --</option>
                    <option value="เล่ม TD" {{ old('passport_type_cambodia') == 'เล่ม TD' ? 'selected' : '' }}>เล่ม TD</option>
                    <option value="เล่มอินเตอร์" {{ old('passport_type_cambodia') == 'เล่มอินเตอร์' ? 'selected' : '' }}>เล่มอินเตอร์</option>
                </select>
            </div>
        </div>

        {{-- Category 3: Passport & Visa --}}
        <h5 class="mt-4"><i class="bi bi-passport"></i> 3. ข้อมูลหนังสือเดินทางและวีซ่า (Passport & Visa)</h5>
        <hr class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="employeePassport" class="form-label">เลขพาสปอร์ต</label>
                <input type="text" class="form-control" id="employeePassport" name="employeePassport" value="{{ old('employeePassport') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="passport_issue_date" class="form-label">วันออกพาสปอร์ต</label>
                <input type="date" class="form-control" id="passport_issue_date" name="passport_issue_date" value="{{ old('passport_issue_date') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="passportExpiryDate" class="form-label">วันหมดอายุพาสปอร์ต</label>
                <input type="date" class="form-control" id="passportExpiryDate" name="passportExpiryDate" value="{{ old('passportExpiryDate') }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="pinkCardNo" class="form-label">เลขบัตรชมพู</label>
                <input type="text" class="form-control" id="pinkCardNo" name="pinkCardNo" value="{{ old('pinkCardNo') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="visaType" class="form-label">ประเภทวีซ่า</label>
                <input type="text" class="form-control" id="visaType" name="visaType" value="{{ old('visaType') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="visaExpiryDate" class="form-label">วันหมดอายุวีซ่า</label>
                <input type="date" class="form-control" id="visaExpiryDate" name="visaExpiryDate" value="{{ old('visaExpiryDate') }}">
            </div>
        </div>

        {{-- Category 4: Employment & Work IDs --}}
        <h5 class="mt-4"><i class="bi bi-briefcase-fill"></i> 4. ข้อมูลการจ้างงานและเอกสาร (Employment & Work IDs)</h5>
        <hr class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="job_title" class="form-label">ตำแหน่งงาน</label>
                <input type="text" class="form-control" id="job_title" name="job_title" value="{{ old('job_title') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="job_description" class="form-label">ลักษณะงาน</label>
                <input type="text" class="form-control" id="job_description" name="job_description" value="{{ old('job_description') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="startDate" class="form-label">วันที่เริ่มงาน</label>
                <input type="date" class="form-control" id="startDate" name="startDate" value="{{ old('startDate') }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="employeeWorkPermit" class="form-label">เลข Work Permit</label>
                <input type="text" class="form-control" id="employeeWorkPermit" name="employeeWorkPermit" value="{{ old('employeeWorkPermit') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="workPermitExpiryDate" class="form-label">วันหมดอายุ Work Permit</label>
                <input type="date" class="form-control" id="workPermitExpiryDate" name="workPermitExpiryDate" value="{{ old('workPermitExpiryDate') }}">
            </div>
             <div class="col-md-4 mb-3">
                <label for="ninetyDayReportDate" class="form-label">วันรายงานตัว 90 วัน</label>
                <input type="date" class="form-control" id="ninetyDayReportDate" name="ninetyDayReportDate" value="{{ old('ninetyDayReportDate') }}">
            </div>
        </div>
        <div class="row">
             <div class="col-md-6 mb-3">
                <label for="workPermitType" class="form-label">ประเภทใบอนุญาตทำงาน</label>
                <select class="form-select" id="workPermitMOUGroup" name="workPermitMOUGroup">
                    <option value="">-- กรุณาเลือก --</option>
                    <option value="MOU" {{ old('workPermitMOUGroup') == 'MOU' ? 'selected' : '' }}>MOU</option>
                    <option value="มติต่ออายุในประเทศ" {{ old('workPermitMOUGroup') == 'มติต่ออายุในประเทศ' ? 'selected' : '' }}>มติต่ออายุในประเทศ</option>
                    <option value="มติขึ้นทะเบียน" {{ old('workPermitMOUGroup') == 'มติขึ้นทะเบียน' ? 'selected' : '' }}>มติขึ้นทะเบียน</option>
                    <option value="อื่นๆ" {{ old('workPermitMOUGroup') == 'อื่นๆ' ? 'selected' : '' }}>อื่นๆ ระบุ..</option>
                </select>
            </div>
            <div class="col-md-6 mb-3 d-none" id="workPermitMOUGroupOtherContainer">
                <label for="workPermitMOUGroupOther" class="form-label">ระบุประเภทอื่นๆ</label>
                 <input type="text" class="form-control" id="workPermitMOUGroupOther" name="workPermitMOUGroupOther" value="{{ old('workPermitMOUGroupOther') }}">
            </div>
        </div>
        <div class="row">
             <div class="col-md-4 mb-3"><label for="name_list_number" class="form-label">เลข Name List</label><input type="text" class="form-control" id="name_list_number" name="name_list_number" value="{{ old('name_list_number') }}"></div>
             <div class="col-md-4 mb-3"><label for="request_number" class="form-label">เลขที่คำขอ</label><input type="text" class="form-control" id="request_number" name="request_number" value="{{ old('request_number') }}"></div>
             <div class="col-md-4 mb-3"><label for="employee_id_number" class="form-label">เลขประจำตัว</label><input type="text" class="form-control" id="employee_id_number" name="employee_id_number" value="{{ old('employee_id_number') }}"></div>
             <div class="col-md-4 mb-3"><label for="tax_id_number" class="form-label">เลขประจำตัวผู้เสียภาษี</label><input type="text" class="form-control" id="tax_id_number" name="tax_id_number" value="{{ old('tax_id_number') }}"></div>
             <div class="col-md-4 mb-3"><label for="employer_employee_id" class="form-label">รหัสคนงาน - ของนายจ้าง</label><input type="text" class="form-control" id="employer_employee_id" name="employer_employee_id" value="{{ old('employer_employee_id') }}"></div>
             <div class="col-md-4 mb-3"><label for="employee_reference_id" class="form-label">เลขอ้างอิงคนงาน</label><input type="text" class="form-control" id="employee_reference_id" name="employee_reference_id" value="{{ old('employee_reference_id') }}"></div>
        </div>


        {{-- Category 5: Health Insurance --}}
        <h5 class="mt-4"><i class="bi bi-heart-pulse"></i> 5. ข้อมูลประกันสุขภาพ (Health Insurance)</h5>
        <hr class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="insurance_type" class="form-label">ประเภทประกัน</label>
                <select class="form-select" id="insurance_type" name="insurance_type">
                    <option value="">-- เลือกประเภท --</option>
                    <option value="ประกันสังคม" {{ old('insurance_type') == 'ประกันสังคม' ? 'selected' : '' }}>ประกันสังคม</option>
                    <option value="ประกันโรงพยาบาล" {{ old('insurance_type') == 'ประกันโรงพยาบาล' ? 'selected' : '' }}>ประกันโรงพยาบาล</option>
                    <option value="ประกันเอกชน" {{ old('insurance_type') == 'ประกันเอกชน' ? 'selected' : '' }}>ประกันเอกชน</option>
                </select>
            </div>
        </div>
        {{-- Social Security Container --}}
        <div id="insuranceSocialSecurity" class="d-none">
             <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="social_security_number" class="form-label">เลขประกันสังคม</label>
                    <input type="text" class="form-control" id="social_security_number" name="social_security_number" value="{{ old('social_security_number') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="insurance_detail_social" class="form-label">สิทธิ์โรงพยาบาล</label>
                    <input type="text" class="form-control" name="insurance_detail_social" value="{{ old('insurance_detail_social') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="insurance_document_path_social" class="form-label">แนบไฟล์เอกสารประกัน</label>
                    <input type="file" class="form-control form-control-sm" name="insurance_document_path_social">
                </div>
             </div>
        </div>
        {{-- Hospital Insurance Container --}}
        <div id="insuranceHospital" class="d-none">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="insurance_detail_hospital" class="form-label">ชื่อโรงพยาบาล</label>
                    <input type="text" class="form-control" name="insurance_detail_hospital" value="{{ old('insurance_detail_hospital') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="insurance_expiry_date_hospital" class="form-label">วันหมดอายุประกัน</label>
                    <input type="date" class="form-control" name="insurance_expiry_date_hospital" value="{{ old('insurance_expiry_date_hospital') }}">
                </div>
                 <div class="col-md-4 mb-3">
                    <label for="insurance_document_path_hospital" class="form-label">แนบไฟล์เอกสารประกัน</label>
                    <input type="file" class="form-control form-control-sm" name="insurance_document_path_hospital">
                </div>
            </div>
        </div>
        {{-- Private Insurance Container --}}
        <div id="insurancePrivate" class="d-none">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="insurance_detail_private" class="form-label">บริษัทประกัน</label>
                    <input type="text" class="form-control" name="insurance_detail_private" value="{{ old('insurance_detail_private') }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="insurance_expiry_date_private" class="form-label">วันหมดอายุประกัน</label>
                    <input type="date" class="form-control" name="insurance_expiry_date_private" value="{{ old('insurance_expiry_date_private') }}">
                </div>
                 <div class="col-md-4 mb-3">
                    <label for="insurance_document_path_private" class="form-label">แนบไฟล์เอกสารประกัน</label>
                    <input type="file" class="form-control form-control-sm" name="insurance_document_path_private">
                </div>
            </div>
        </div>


        {{-- Category 6: Login Information --}}
        <h5 class="mt-4"><i class="bi bi-lock-fill"></i> 6. ข้อมูลการเข้าสู่ระบบ (Login Information)</h5>
        <hr class="mb-4">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="employeeEmail" class="form-label">อีเมล</label>
                <input type="email" class="form-control" id="employeeEmail" name="employeeEmail" value="{{ old('employeeEmail') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label for="employeePassword" class="form-label">รหัสผ่าน</label>
                <input type="text" class="form-control" id="employeePassword" name="employeePassword">
            </div>
        </div>

        {{-- Category 7: File Attachments --}}
        <h5 class="mt-4"><i class="bi bi-file-earmark-arrow-up-fill"></i> 7. ส่วนแนบไฟล์เอกสาร (File Attachments)</h5>
        <hr class="mb-4">
        <div class="row">
            <div class="col-md-4 mb-3"><label for="employee_doc_1" class="form-label">1. พาสปอร์ต</label><input type="file" class="form-control form-control-sm" id="employee_doc_1" name="employee_doc_1"></div>
            <div class="col-md-4 mb-3"><label for="employee_doc_2" class="form-label">2. วีซ่า</label><input type="file" class="form-control form-control-sm" id="employee_doc_2" name="employee_doc_2"></div>
            <div class="col-md-4 mb-3"><label for="employee_doc_3" class="form-label">3. ใบเสร็จ Work Permit</label><input type="file" class="form-control form-control-sm" id="employee_doc_3" name="employee_doc_3"></div>
            <div class="col-md-4 mb-3"><label for="employee_doc_4" class="form-label">4. บัตรชมพู</label><input type="file" class="form-control form-control-sm" id="employee_doc_4" name="employee_doc_4"></div>
            <div class="col-md-4 mb-3"><label for="employee_doc_5" class="form-label">5. ทร. 38</label><input type="file" class="form-control form-control-sm" id="employee_doc_5" name="employee_doc_5"></div>
            <div class="col-md-4 mb-3"><label for="employee_doc_6" class="form-label">6. รายงานตัว 90 วัน</label><input type="file" class="form-control form-control-sm" id="employee_doc_6" name="employee_doc_6"></div>
            <div class="col-md-4 mb-3"><label for="employee_doc_7" class="form-label">7. ใบแจ้งที่พักอาศัย</label><input type="file" class="form-control form-control-sm" id="employee_doc_7" name="employee_doc_7"></div>
            <div class="col-md-4 mb-3"><label for="employee_doc_8" class="form-label">8. เอกสารบ้านเกิด</label><input type="file" class="form-control form-control-sm" id="employee_doc_8" name="employee_doc_8"></div>
            <div class="col-md-6 mb-3">
                <label for="employee_doc_9" class="form-label">9. เอกสารอื่นๆ 1</label>
                <input type="file" class="form-control form-control-sm" id="employee_doc_9" name="employee_doc_9">
                <input type="text" class="form-control form-control-sm mt-2" name="other_doc_1_desc" placeholder="คำอธิบาย..." value="{{ old('other_doc_1_desc') }}">
            </div>
             <div class="col-md-6 mb-3">
                <label for="employee_doc_10" class="form-label">10. เอกสารอื่นๆ 2</label>
                <input type="file" class="form-control form-control-sm" id="employee_doc_10" name="employee_doc_10">
                <input type="text" class="form-control form-control-sm mt-2" name="other_doc_2_desc" placeholder="คำอธิบาย..." value="{{ old('other_doc_2_desc') }}">
            </div>
             <div class="col-md-6 mb-3">
                <label for="employee_doc_11" class="form-label">11. เอกสารอื่นๆ 3</label>
                <input type="file" class="form-control form-control-sm" id="employee_doc_11" name="employee_doc_11">
                <input type="text" class="form-control form-control-sm mt-2" name="other_doc_3_desc" placeholder="คำอธิบาย..." value="{{ old('other_doc_3_desc') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label for="employee_doc_12" class="form-label">12. เอกสารอื่นๆ 4</label>
                <input type="file" class="form-control form-control-sm" id="employee_doc_12" name="employee_doc_12">
                <input type="text" class="form-control form-control-sm mt-2" name="other_doc_4_desc" placeholder="คำอธิบาย..." value="{{ old('other_doc_4_desc') }}">
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end">
            <a href="{{ isset($employer) ? route('employers.edit', $employer->id) : route('employees.index') }}" class="btn btn-secondary me-2">ยกเลิก</a>
            <button type="submit" class="btn btn-primary">บันทึกข้อมูลพนักงาน</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- V6: Get all required elements ---
    const titleTh = document.getElementById('employeeTitleTh');
    const titleEn = document.getElementById('employeeTitleEn');
    const genderInput = document.getElementById('employeeGender');
    const dobInput = document.getElementById('employeeDob');
    const ageInput = document.getElementById('employeeAge');
    const nationalitySelect = document.getElementById('employeeNationality');
    const mouGroupSelect = document.getElementById('workPermitMOUGroup');
    const employeePhotoInput = document.getElementById('employeePhoto');
    const employeePhotoPreview = document.getElementById('employeePhotoPreview');

    // Containers for conditional logic
    const myanmarPassportContainer = document.getElementById('passportTypeContainer');
    const cambodiaPassportContainer = document.getElementById('passportTypeCambodiaContainer');
    const mouGroupOtherContainer = document.getElementById('workPermitMOUGroupOtherContainer');
    const insuranceSelect = document.getElementById('insurance_type');
    const socialContainer = document.getElementById('insuranceSocialSecurity');
    const hospitalContainer = document.getElementById('insuranceHospital');
    const privateContainer = document.getElementById('insurancePrivate');


    // --- Logic Block 1: Title & Gender Sync ---
    const thToEnMap = { 'นาย': 'Mr.', 'นางสาว': 'Miss', 'นาง': 'Mrs.' };
    const enToThMap = { 'Mr.': 'นาย', 'Miss': 'นางสาว', 'Mrs.': 'นาง' };

    function syncTitles(source) {
        if (source === 'th') {
            const selectedTh = titleTh.value;
            if (thToEnMap[selectedTh]) {
                titleEn.value = thToEnMap[selectedTh];
            }
        } else {
            const selectedEn = titleEn.value;
            if (enToThMap[selectedEn]) {
                titleTh.value = enToThMap[selectedEn];
            }
        }
        updateGender();
    }

    function updateGender() {
        const selectedTh = titleTh.value;
        if (selectedTh === 'นาย') {
            genderInput.value = 'ชาย';
        } else if (selectedTh === 'นางสาว' || selectedTh === 'นาง') {
            genderInput.value = 'หญิง';
        } else {
            genderInput.value = '';
        }
    }

    titleTh.addEventListener('change', () => syncTitles('th'));
    titleEn.addEventListener('change', () => syncTitles('en'));


    // --- Logic Block 2: Age Calculation ---
    function calculateAge() {
        const dob = new Date(dobInput.value);
        if (!isNaN(dob.getTime())) {
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            ageInput.value = age > 0 ? age : 0;
        } else {
            ageInput.value = '';
        }
    }
    dobInput.addEventListener('change', calculateAge);


    // --- Logic Block 3: Nationality Conditional Fields ---
    function toggleNationalityFields() {
        // Myanmar
        myanmarPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'เมียนมา');
        // Cambodia
        cambodiaPassportContainer.classList.toggle('d-none', nationalitySelect.value !== 'กัมพูชา');
    }
    nationalitySelect.addEventListener('change', toggleNationalityFields);


    // --- Logic Block 4: MOU "Other" Field ---
     function toggleMouGroupOther() {
        mouGroupOtherContainer.classList.toggle('d-none', mouGroupSelect.value !== 'อื่นๆ');
    }
    mouGroupSelect.addEventListener('change', toggleMouGroupOther);


    // --- V6: Logic Block 5: Insurance Conditional Fields ---
    function toggleInsuranceVisibility() {
        const selectedType = insuranceSelect.value;
        socialContainer.classList.toggle('d-none', selectedType !== 'ประกันสังคม');
        hospitalContainer.classList.toggle('d-none', selectedType !== 'ประกันโรงพยาบาล');
        privateContainer.classList.toggle('d-none', selectedType !== 'ประกันเอกชน');
    }
    insuranceSelect.addEventListener('change', toggleInsuranceVisibility);


    // --- Logic Block 6: Photo Preview ---
    employeePhotoInput.addEventListener('change', function(event) {
        const [file] = event.target.files;
        if (file) {
            employeePhotoPreview.src = URL.createObjectURL(file);
        }
    });

    // --- Initial State Setup on Page Load ---
    updateGender();
    calculateAge();
    toggleNationalityFields();
    toggleMouGroupOther();
    toggleInsuranceVisibility();

});
</script>
@endpush
