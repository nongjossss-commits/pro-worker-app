@extends('layouts.app')

@section('title', 'เพิ่มพนักงาน')

@section('content')
<div class="content-section">
    <h2 class="mb-4">เพิ่มพนักงานสำหรับ {{ $employer->employerNameTh }}</h2>

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
        <input type="hidden" name="employer_id" value="{{ $employer->id }}">
        <h5>ข้อมูลส่วนตัว</h5>
        <hr>
        <div class="row">
            <div class="col-md-8">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employeeNameTh" class="form-label">ชื่อพนักงาน (ไทย)</label>
                        <div class="input-group">
                            <select class="form-select" id="employeeTitleTh" name="employeeTitleTh" style="max-width: 100px;">
                                <option value="นาย" {{ old('employeeTitleTh') == 'นาย' ? 'selected' : '' }}>นาย</option>
                                <option value="นางสาว" {{ old('employeeTitleTh') == 'นางสาว' ? 'selected' : '' }}>นางสาว</option>
                                <option value="นาง" {{ old('employeeTitleTh') == 'นาง' ? 'selected' : '' }}>นาง</option>
                            </select>
                            <input type="text" class="form-control" id="employeeNameTh" name="employeeNameTh" value="{{ old('employeeNameTh') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="employeeNameEn" class="form-label">ชื่อพนักงาน (อังกฤษ)</label>
                        <div class="input-group">
                            <select class="form-select" id="employeeTitleEn" name="employeeTitleEn" style="max-width: 100px;">
                                <option value="Mr." {{ old('employeeTitleEn') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                <option value="Miss" {{ old('employeeTitleEn') == 'Miss' ? 'selected' : '' }}>Miss</option>
                                <option value="Mrs." {{ old('employeeTitleEn') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                            </select>
                            <input type="text" class="form-control" id="employeeNameEn" name="employeeNameEn" value="{{ old('employeeNameEn') }}">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="employeeDob" class="form-label">วันเดือนปีเกิด</label>
                        <input type="date" class="form-control" id="employeeDob" name="employeeDob" value="{{ old('employeeDob') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="employeeAge" class="form-label">อายุ</label>
                        <input type="text" class="form-control" id="employeeAge" name="employeeAge" readonly>
                    </div>
                    <div class="col-md-6">
                        <label for="employeeNationality" class="form-label">สัญชาติ</label>
                        <select class="form-select" id="employeeNationality" name="employeeNationality">
                            <option value="">-- เลือกสัญชาติ --</option>
                            <option value="ลาว" {{ old('employeeNationality') == 'ลาว' ? 'selected' : '' }}>ลาว</option>
                            <option value="กัมพูชา" {{ old('employeeNationality') == 'กัมพูชา' ? 'selected' : '' }}>กัมพูชา</option>
                            <option value="เมียนมา" {{ old('employeeNationality') == 'เมียนมา' ? 'selected' : '' }}>เมียนมา</option>
                            <option value="เวียดนาม" {{ old('employeeNationality') == 'เวียดนาม' ? 'selected' : '' }}>เวียดนาม</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employeePassport" class="form-label">เลขหนังสือเดินทาง (Passport No.)</label>
                        <input type="text" class="form-control" id="employeePassport" name="employeePassport" value="{{ old('employeePassport') }}" required>
                    </div>
                    <div class="col-md-6 d-none" id="passportTypeContainer">
                        <label for="passportType" class="form-label">ประเภทหนังสือเดินทาง</label>
                        <select class="form-select" id="passportType" name="passportType">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="PJ" {{ old('passportType') == 'PJ' ? 'selected' : '' }}>เล่ม PJ</option>
                            <option value="CI" {{ old('passportType') == 'CI' ? 'selected' : '' }}>เล่ม CI</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <label for="employeePhoto" class="form-label">รูปภาพพนักงาน</label>
                <img id="employeePhotoPreview" src="https://placehold.co/120x120/f8fafc/6c757d?text=Photo" class="employee-photo-preview mb-2" style="max-width: 150px; height: auto;">
                <input type="file" class="form-control form-control-sm" id="employeePhoto" name="employeePhoto" accept="image/*">
            </div>
        </div>

        <h5 class="mt-4">ข้อมูลการจ้างงานและเอกสารราชการ</h5>
        <hr>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="namelistNo" class="form-label">เลขที่ Namelist</label>
                <input type="text" class="form-control" id="namelistNo" name="namelistNo" value="{{ old('namelistNo') }}">
            </div>
            <div class="col-md-4">
                <label for="requestNo" class="form-label">เลขที่คำขอ</label>
                <input type="text" class="form-control" id="requestNo" name="requestNo" value="{{ old('requestNo') }}">
            </div>
            <div class="col-md-4">
                <label for="workerRefNo" class="form-label">เลขอ้างอิงคนงาน</label>
                <input type="text" class="form-control" id="workerRefNo" name="workerRefNo" value="{{ old('workerRefNo') }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="personalId" class="form-label">เลขประจำตัว</label>
                <input type="text" class="form-control" id="personalId" name="personalId" value="{{ old('personalId') }}">
            </div>
            <div class="col-md-4">
                <label for="companyWorkerId" class="form-label">รหัสคนงาน (บริษัท)</label>
                <input type="text" class="form-control" id="companyWorkerId" name="companyWorkerId" value="{{ old('companyWorkerId') }}">
            </div>
            <div class="col-md-4">
                <label for="pinkCardNo" class="form-label">เลขบัตรชมพู</label>
                <input type="text" class="form-control" id="pinkCardNo" name="pinkCardNo" value="{{ old('pinkCardNo') }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="socialSecurityNo" class="form-label">เลขประกันสังคม</label>
                <input type="text" class="form-control" id="socialSecurityNo" name="socialSecurityNo" value="{{ old('socialSecurityNo') }}">
            </div>
            <div class="col-md-4">
                <label for="taxIdNo" class="form-label">เลขประจำตัวผู้เสียภาษี</label>
                <input type="text" class="form-control" id="taxIdNo" name="taxIdNo" value="{{ old('taxIdNo') }}">
            </div>
            <div class="col-md-4">
                <label for="designatedHospital" class="form-label">โรงพยาบาลตามสิทธิ</label>
                <input type="text" class="form-control" id="designatedHospital" name="designatedHospital" value="{{ old('designatedHospital') }}">
            </div>
        </div>
        <hr>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="passportExpiryDate" class="form-label">วันหมดอายุหนังสือเดินทาง</label>
                <input type="date" class="form-control" id="passportExpiryDate" name="passportExpiryDate" value="{{ old('passportExpiryDate') }}">
            </div>
            <div class="col-md-6">
                <label for="workPermitExpiryDate" class="form-label">วันหมดอายุใบอนุญาตทำงาน</label>
                <input type="date" class="form-control" id="workPermitExpiryDate" name="workPermitExpiryDate" value="{{ old('workPermitExpiryDate') }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="workPermitMOUGroup" class="form-label">ประเภทใบอนุญาตทำงาน(กลุ่ม มติ.)</label>
                <select class="form-select" id="workPermitMOUGroup" name="workPermitMOUGroup">
                    <option value="">-- กรุณาเลือก --</option>
                    <option value="MOU" {{ old('workPermitMOUGroup') == 'MOU' ? 'selected' : '' }}>MOU</option>
                    <option value="มติต่ออายุในประเทศ" {{ old('workPermitMOUGroup') == 'มติต่ออายุในประเทศ' ? 'selected' : '' }}>มติต่ออายุในประเทศ</option>
                    <option value="มติขึ้นทะเบียน" {{ old('workPermitMOUGroup') == 'มติขึ้นทะเบียน' ? 'selected' : '' }}>มติขึ้นทะเบียน</option>
                    <option value="อื่นๆ" {{ old('workPermitMOUGroup') == 'อื่นๆ' ? 'selected' : '' }}>อื่นๆ ระบุ..</option>
                </select>
                <input type="text" class="form-control mt-2 d-none" id="workPermitMOUGroupOther" name="workPermitMOUGroupOther" value="{{ old('workPermitMOUGroupOther') }}" placeholder="ระบุประเภทใบอนุญาตทำงาน">
            </div>
            <div class="col-md-6">
                <label for="visaExpiryDate" class="form-label">วันหมดอายุวีซ่า</label>
                <input type="date" class="form-control" id="visaExpiryDate" name="visaExpiryDate" value="{{ old('visaExpiryDate') }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employeeWorkPermit" class="form-label">ใบอนุญาตทำงาน (Work Permit No.)</label>
                <input type="text" class="form-control" id="employeeWorkPermit" name="employeeWorkPermit" value="{{ old('employeeWorkPermit') }}">
            </div>
            <div class="col-md-6">
                <label for="ninetyDayReportDate" class="form-label">วันหมดอายุรายงานตัว 90 วัน</label>
                <input type="date" class="form-control" id="ninetyDayReportDate" name="ninetyDayReportDate" value="{{ old('ninetyDayReportDate') }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="startDate" class="form-label">วันเริ่มงาน</label>
                <input type="date" class="form-control" id="startDate" name="startDate" value="{{ old('startDate') }}">
            </div>
            <div class="col-md-6">
                <label for="employeePhone" class="form-label">เบอร์โทรศัพท์</label>
                <input type="tel" class="form-control" id="employeePhone" name="employeePhone" value="{{ old('employeePhone') }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employeePosition" class="form-label">ตำแหน่ง</label>
                <input type="text" class="form-control" id="employeePosition" name="employeePosition" value="{{ old('employeePosition') }}">
            </div>
            <div class="col-md-6">
                <label for="nature_of_work" class="form-label">ลักษณะงาน (Nature of Work)</label>
                <textarea class="form-control" id="nature_of_work" name="nature_of_work" rows="3">{{ old('nature_of_work') }}</textarea>
            </div>
        </div>

        <h5 class="mt-4">เอกสารแนบ</h5>
        <hr>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="document_1" class="form-label">1. passport/visa/workpermit</label>
                <input type="file" class="form-control form-control-sm" id="document_1" name="document_1">
            </div>
            <div class="col-md-4 mb-3">
                <label for="document_2" class="form-label">2. บัตรชมพู</label>
                <input type="file" class="form-control form-control-sm" id="document_2" name="document_2">
            </div>
            <div class="col-md-4 mb-3">
                <label for="document_3" class="form-label">3. สัญญาแรงงาน</label>
                <input type="file" class="form-control form-control-sm" id="document_3" name="document_3">
            </div>

            <div class="col-md-6 mb-3">
                <label for="document_4" class="form-label">4. เอกสารอื่นๆ 1</label>
                <input type="file" class="form-control form-control-sm" id="document_4" name="document_4">
                <label for="document_description_4" class="form-label mt-2">คำอธิบาย</label>
                <input type="text" class="form-control form-control-sm" id="document_description_4" name="document_description_4" value="{{ old('document_description_4') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label for="document_5" class="form-label">5. เอกสารอื่นๆ 2</label>
                <input type="file" class="form-control form-control-sm" id="document_5" name="document_5">
                <label for="document_description_5" class="form-label mt-2">คำอธิบาย</label>
                <input type="text" class="form-control form-control-sm" id="document_description_5" name="document_description_5" value="{{ old('document_description_5') }}">
            </div>
            <div class="col-md-6 mb-3">
                <label for="document_6" class="form-label">6. เอกสารอื่นๆ 3</label>
                <input type="file" class="form-control form-control-sm" id="document_6" name="document_6">
                <label for="document_description_6" class="form-label mt-2">คำอธิบาย</label>
                <input type="text" class="form-control form-control-sm" id="document_description_6" name="document_description_6" value="{{ old('document_description_6') }}">
            </div>
            <div class="col-md-6 mb-3" id="myanmar-id-field" style="display: none;">
                <label for="myanmar_id" class="form-label">Myanmar ID</label>
                <input type="file" class="form-control form-control-sm" id="myanmar_id" name="myanmar_id">
            </div>

            <div class="col-md-6 mb-3" id="myanmar-house-reg-field" style="display: none;">
                <label for="myanmar_house_reg" class="form-label">Myanmar House Reg</label>
                <input type="file" class="form-control form-control-sm" id="myanmar_house_reg" name="myanmar_house_reg">
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">บันทึกข้อมูลพนักงาน</button>
            <a href="{{ route('employers.edit', $employer) }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Age calculation
    const dobInput = document.getElementById('employeeDob');
    const ageInput = document.getElementById('employeeAge');

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

    if(dobInput) {
        dobInput.addEventListener('change', calculateAge);
        calculateAge(); // Initial calculation
    }


    // Conditional fields for Myanmar nationality
    const nationalitySelect = document.getElementById('employeeNationality');
    const myanmarIdField = document.getElementById('myanmar-id-field');
    const myanmarHouseRegField = document.getElementById('myanmar-house-reg-field');

    function toggleMyanmarFields() {
        if (nationalitySelect.value === 'เมียนมา') {
            myanmarIdField.style.display = 'block';
            myanmarHouseRegField.style.display = 'block';
        } else {
            myanmarIdField.style.display = 'none';
            myanmarHouseRegField.style.display = 'none';
        }
    }

    nationalitySelect.addEventListener('change', toggleMyanmarFields);
    toggleMyanmarFields(); // Initial check

    // MOU Group Other field
    const mouGroupSelect = document.getElementById('workPermitMOUGroup');
    const mouGroupOtherInput = document.getElementById('workPermitMOUGroupOther');

    function toggleMouGroupOther() {
        if (mouGroupSelect.value === 'อื่นๆ') {
            mouGroupOtherInput.classList.remove('d-none');
        } else {
            mouGroupOtherInput.classList.add('d-none');
        }
    }

    mouGroupSelect.addEventListener('change', toggleMouGroupOther);
    toggleMouGroupOther(); // Initial check

    // Photo preview
    const employeePhotoInput = document.getElementById('employeePhoto');
    const employeePhotoPreview = document.getElementById('employeePhotoPreview');

    employeePhotoInput.addEventListener('change', function(event) {
        const [file] = event.target.files;
        if (file) {
            employeePhotoPreview.src = URL.createObjectURL(file);
        }
    });

    const titleTh = document.getElementById('employeeTitleTh');
    const titleEn = document.getElementById('employeeTitleEn');

    const thToEnMap = { 'นาย': 'Mr.', 'นางสาว': 'Miss', 'นาง': 'Mrs.' };
    const enToThMap = { 'Mr.': 'นาย', 'Miss': 'นางสาว', 'Mrs.': 'นาง' };

    titleTh.addEventListener('change', function() {
        const selectedTh = this.value;
        if (thToEnMap[selectedTh]) {
            titleEn.value = thToEnMap[selectedTh];
        }
    });

    titleEn.addEventListener('change', function() {
        const selectedEn = this.value;
        if (enToThMap[selectedEn]) {
            titleTh.value = enToThMap[selectedEn];
        }
    });

    // --- Conditional Passport Type Logic ---
    const passportTypeContainer = document.getElementById('passportTypeContainer');

    function togglePassportTypeVisibility() {
        if (nationalitySelect.value === 'เมียนมา') {
            passportTypeContainer.classList.remove('d-none');
        } else {
            passportTypeContainer.classList.add('d-none');
        }
    }

    nationalitySelect.addEventListener('change', togglePassportTypeVisibility);

    // Run on page load to set initial state
    togglePassportTypeVisibility();
});
</script>
@endpush
