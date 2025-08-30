@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลพนักงาน')

@section('content')
<div class="content-section">
    <h2 class="mb-4">แก้ไขข้อมูลพนักงานสำหรับ {{ $employer->employerNameTh }}</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('employers.employees.update', [$employer, $employee]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <h5>ข้อมูลส่วนตัว</h5>
        <hr>
        <div class="row">
            <div class="col-md-8">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employeeNameTh" class="form-label">ชื่อพนักงาน (ไทย)</label>
                        <div class="input-group">
                            <select class="form-select" id="employeeTitleTh" name="employeeTitleTh" style="max-width: 100px;">
                                <option value="นาย" {{ old('employeeTitleTh', $employee->employeeTitleTh) == 'นาย' ? 'selected' : '' }}>นาย</option>
                                <option value="นางสาว" {{ old('employeeTitleTh', $employee->employeeTitleTh) == 'นางสาว' ? 'selected' : '' }}>นางสาว</option>
                                <option value="นาง" {{ old('employeeTitleTh', $employee->employeeTitleTh) == 'นาง' ? 'selected' : '' }}>นาง</option>
                            </select>
                            <input type="text" class="form-control" id="employeeNameTh" name="employeeNameTh" value="{{ old('employeeNameTh', $employee->employeeNameTh) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="employeeNameEn" class="form-label">ชื่อพนักงาน (อังกฤษ)</label>
                        <div class="input-group">
                            <select class="form-select" id="employeeTitleEn" name="employeeTitleEn" style="max-width: 100px;">
                                <option value="Mr." {{ old('employeeTitleEn', $employee->employeeTitleEn) == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                <option value="Miss" {{ old('employeeTitleEn', $employee->employeeTitleEn) == 'Miss' ? 'selected' : '' }}>Miss</option>
                                <option value="Mrs." {{ old('employeeTitleEn', $employee->employeeTitleEn) == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                            </select>
                            <input type="text" class="form-control" id="employeeNameEn" name="employeeNameEn" value="{{ old('employeeNameEn', $employee->employeeNameEn) }}">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employeeDob" class="form-label">วันเดือนปีเกิด</label>
                        <input type="date" class="form-control" id="employeeDob" name="employeeDob" value="{{ old('employeeDob', $employee->employeeDob) }}">
                    </div>
                     <div class="col-md-6">
                        <label for="employeeNationality" class="form-label">สัญชาติ</label>
                        <input type="text" class="form-control" id="employeeNationality" name="employeeNationality" value="{{ old('employeeNationality', $employee->employeeNationality) }}">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employeePassport" class="form-label">เลขหนังสือเดินทาง (Passport No.)</label>
                        <input type="text" class="form-control" id="employeePassport" name="employeePassport" value="{{ old('employeePassport', $employee->employeePassport) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="passportType" class="form-label">ประเภทหนังสือเดินทาง</label>
                        <select class="form-select" id="passportType" name="passportType">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="PJ" {{ old('passportType', $employee->passportType) == 'PJ' ? 'selected' : '' }}>เล่ม PJ</option>
                            <option value="CI" {{ old('passportType', $employee->passportType) == 'CI' ? 'selected' : '' }}>เล่ม CI</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <label for="employeePhoto" class="form-label">รูปภาพพนักงาน</label>
                @if ($employee->employeePhoto)
                    <img id="employeePhotoPreview" src="{{ asset('storage/' . $employee->employeePhoto) }}" alt="Employee Photo" class="employee-photo-preview mb-2">
                @else
                    <img id="employeePhotoPreview" src="https://placehold.co/120x120/f8fafc/6c757d?text=Photo" class="employee-photo-preview mb-2">
                @endif
                <input type="file" class="form-control form-control-sm" id="employeePhoto" name="employeePhoto" accept="image/*">
                 @if ($employee->employeePhoto)
                    <p class="form-text">อัปโหลดไฟล์ใหม่เพื่อแทนที่ไฟล์เดิม</p>
                @endif
            </div>
        </div>

        <h5 class="mt-4">ข้อมูลการจ้างงานและเอกสารราชการ</h5>
        <hr>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="namelistNo" class="form-label">เลขที่ Namelist</label>
                <input type="text" class="form-control" id="namelistNo" name="namelistNo" value="{{ old('namelistNo', $employee->namelistNo) }}">
            </div>
            <div class="col-md-4">
                <label for="requestNo" class="form-label">เลขที่คำขอ</label>
                <input type="text" class="form-control" id="requestNo" name="requestNo" value="{{ old('requestNo', $employee->requestNo) }}">
            </div>
            <div class="col-md-4">
                <label for="workerRefNo" class="form-label">เลขอ้างอิงคนงาน</label>
                <input type="text" class="form-control" id="workerRefNo" name="workerRefNo" value="{{ old('workerRefNo', $employee->workerRefNo) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="personalId" class="form-label">เลขประจำตัว</label>
                <input type="text" class="form-control" id="personalId" name="personalId" value="{{ old('personalId', $employee->personalId) }}">
            </div>
            <div class="col-md-4">
                <label for="companyWorkerId" class="form-label">รหัสคนงาน (บริษัท)</label>
                <input type="text" class="form-control" id="companyWorkerId" name="companyWorkerId" value="{{ old('companyWorkerId', $employee->companyWorkerId) }}">
            </div>
            <div class="col-md-4">
                <label for="pinkCardNo" class="form-label">เลขบัตรชมพู</label>
                <input type="text" class="form-control" id="pinkCardNo" name="pinkCardNo" value="{{ old('pinkCardNo', $employee->pinkCardNo) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="socialSecurityNo" class="form-label">เลขประกันสังคม</label>
                <input type="text" class="form-control" id="socialSecurityNo" name="socialSecurityNo" value="{{ old('socialSecurityNo', $employee->socialSecurityNo) }}">
            </div>
            <div class="col-md-4">
                <label for="taxIdNo" class="form-label">เลขประจำตัวผู้เสียภาษี</label>
                <input type="text" class="form-control" id="taxIdNo" name="taxIdNo" value="{{ old('taxIdNo', $employee->taxIdNo) }}">
            </div>
            <div class="col-md-4">
                <label for="designatedHospital" class="form-label">โรงพยาบาลตามสิทธิ</label>
                <input type="text" class="form-control" id="designatedHospital" name="designatedHospital" value="{{ old('designatedHospital', $employee->designatedHospital) }}">
            </div>
        </div>
        <hr>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="passportExpiryDate" class="form-label">วันหมดอายุหนังสือเดินทาง</label>
                <input type="date" class="form-control" id="passportExpiryDate" name="passportExpiryDate" value="{{ old('passportExpiryDate', $employee->passportExpiryDate) }}">
            </div>
            <div class="col-md-6">
                <label for="workPermitExpiryDate" class="form-label">วันหมดอายุใบอนุญาตทำงาน</label>
                <input type="date" class="form-control" id="workPermitExpiryDate" name="workPermitExpiryDate" value="{{ old('workPermitExpiryDate', $employee->workPermitExpiryDate) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="workPermitMOUGroup" class="form-label">ประเภทใบอนุญาตทำงาน(กลุ่ม มติ.)</label>
                <select class="form-select" id="workPermitMOUGroup" name="workPermitMOUGroup">
                    <option value="">-- กรุณาเลือก --</option>
                    <option value="MOU" {{ old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'MOU' ? 'selected' : '' }}>MOU</option>
                    <option value="มติต่ออายุในประเทศ" {{ old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'มติต่ออายุในประเทศ' ? 'selected' : '' }}>มติต่ออายุในประเทศ</option>
                    <option value="มติขึ้นทะเบียน" {{ old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'มติขึ้นทะเบียน' ? 'selected' : '' }}>มติขึ้นทะเบียน</option>
                    <option value="อื่นๆ" {{ old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'อื่นๆ' ? 'selected' : '' }}>อื่นๆ ระบุ..</option>
                </select>
                <input type="text" class="form-control mt-2 {{ old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'อื่นๆ' ? '' : 'd-none' }}" id="workPermitMOUGroupOther" name="workPermitMOUGroupOther" value="{{ old('workPermitMOUGroupOther', $employee->workPermitMOUGroupOther) }}" placeholder="ระบุประเภทใบอนุญาตทำงาน">
            </div>
            <div class="col-md-6">
                <label for="visaExpiryDate" class="form-label">วันหมดอายุวีซ่า</label>
                <input type="date" class="form-control" id="visaExpiryDate" name="visaExpiryDate" value="{{ old('visaExpiryDate', $employee->visaExpiryDate) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                 <label for="employeeWorkPermit" class="form-label">ใบอนุญาตทำงาน (Work Permit No.)</label>
                <input type="text" class="form-control" id="employeeWorkPermit" name="employeeWorkPermit" value="{{ old('employeeWorkPermit', $employee->employeeWorkPermit) }}">
            </div>
            <div class="col-md-6">
                <label for="ninetyDayReportDate" class="form-label">วันหมดอายุรายงานตัว 90 วัน</label>
                <input type="date" class="form-control" id="ninetyDayReportDate" name="ninetyDayReportDate" value="{{ old('ninetyDayReportDate', $employee->ninetyDayReportDate) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="startDate" class="form-label">วันเริ่มงาน</label>
                <input type="date" class="form-control" id="startDate" name="startDate" value="{{ old('startDate', $employee->startDate) }}">
            </div>
            <div class="col-md-6">
                <label for="employeePhone" class="form-label">เบอร์โทรศัพท์</label>
                <input type="tel" class="form-control" id="employeePhone" name="employeePhone" value="{{ old('employeePhone', $employee->employeePhone) }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="employeePosition" class="form-label">ตำแหน่ง</label>
                <input type="text" class="form-control" id="employeePosition" name="employeePosition" value="{{ old('employeePosition', $employee->employeePosition) }}">
            </div>
        </div>

        <h5 class="mt-4">เอกสารแนบ</h5>
        <hr>
        <div class="row">
            <div class="col-md-4">
                <label for="employeePassportFile" class="form-label">ไฟล์ Passport</label>
                <input type="file" class="form-control form-control-sm" id="employeePassportFile" name="employeePassportFile">
                @if ($employee->employeePassportFile)
                    <div class="file-upload-display mt-1">
                        <a href="{{ asset('storage/' . $employee->employeePassportFile) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                    </div>
                @endif
            </div>
            <div class="col-md-4">
                <label for="employeeWorkPermitFile" class="form-label">ไฟล์ใบอนุญาตทำงาน</label>
                <input type="file" class="form-control form-control-sm" id="employeeWorkPermitFile" name="employeeWorkPermitFile">
                 @if ($employee->employeeWorkPermitFile)
                    <div class="file-upload-display mt-1">
                        <a href="{{ asset('storage/' . $employee->employeeWorkPermitFile) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                    </div>
                @endif
            </div>
            <div class="col-md-4">
                <label for="pinkCardFile" class="form-label">ไฟล์บัตรชมพู</label>
                <input type="file" class="form-control form-control-sm" id="pinkCardFile" name="pinkCardFile">
                @if ($employee->pinkCardFile)
                    <div class="file-upload-display mt-1">
                        <a href="{{ asset('storage/' . $employee->pinkCardFile) }}" target="_blank">ดูไฟล์ปัจจุบัน</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">อัปเดตข้อมูลพนักงาน</button>
            <a href="{{ route('employers.edit', $employer) }}" class="btn btn-secondary">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
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

    const employeePhotoInput = document.getElementById('employeePhoto');
    const employeePhotoPreview = document.getElementById('employeePhotoPreview');

    employeePhotoInput.addEventListener('change', function(event) {
        const [file] = event.target.files;
        if (file) {
            employeePhotoPreview.src = URL.createObjectURL(file);
        }
    });
});
</script>
@endpush
