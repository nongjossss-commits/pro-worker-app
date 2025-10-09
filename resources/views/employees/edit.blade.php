@extends('layouts.app')

@section('title', 'แก้ไขข้อมูลลูกจ้าง - ' . $employee->employeeNameTh)

@section('content')
<div class="content-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>แก้ไขข้อมูลลูกจ้าง: {{ $employee->employeeNameTh }}</h2>
        <a href="{{ route('employers.edit', $employee->employer_id) }}#employee-card-{{ $employee->id }}" class="btn btn-secondary">กลับไปที่นายจ้าง</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FIX: Added enctype="multipart/form-data" to enable ALL file uploads --}}
    <form action="{{ route('employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <input type="hidden" name="employer_id" value="{{ $employee->employer_id }}">

        {{-- Personal Information --}}
        <h5>ข้อมูลส่วนตัว</h5>
        <hr>
        <div class="row">
            <div class="col-md-8">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employeeNameTh" class="form-label">ชื่อพนักงาน (ไทย)</label>
                        <div class="input-group">
                            <select class="form-select" id="employeeTitleTh" name="employeeTitleTh" style="max-width: 100px;">
                                <option value="นาย" @selected(old('employeeTitleTh', $employee->employeeTitleTh) == 'นาย')>นาย</option>
                                <option value="นางสาว" @selected(old('employeeTitleTh', $employee->employeeTitleTh) == 'นางสาว')>นางสาว</option>
                                <option value="นาง" @selected(old('employeeTitleTh', $employee->employeeTitleTh) == 'นาง')>นาง</option>
                            </select>
                            <input type="text" class="form-control" id="employeeNameTh" name="employeeNameTh" value="{{ old('employeeNameTh', $employee->employeeNameTh) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="employeeNameEn" class="form-label">ชื่อพนักงาน (อังกฤษ)</label>
                         <div class="input-group">
                            <select class="form-select" id="employeeTitleEn" name="employeeTitleEn" style="max-width: 100px;">
                                <option value="Mr." @selected(old('employeeTitleEn', $employee->employeeTitleEn) == 'Mr.')>Mr.</option>
                                <option value="Miss" @selected(old('employeeTitleEn', $employee->employeeTitleEn) == 'Miss')>Miss</option>
                                <option value="Mrs." @selected(old('employeeTitleEn', $employee->employeeTitleEn) == 'Mrs.')>Mrs.</option>
                            </select>
                            <input type="text" class="form-control" id="employeeNameEn" name="employeeNameEn" value="{{ old('employeeNameEn', $employee->employeeNameEn) }}">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employeeDob" class="form-label">วันเดือนปีเกิด</label>
                        {{-- FIX: Added ->format('Y-m-d') to all date fields --}}
                        <input type="date" class="form-control" id="employeeDob" name="employeeDob" value="{{ old('employeeDob', optional($employee->employeeDob)->format('Y-m-d')) }}">
                    </div>
                     <div class="col-md-6">
                        <label for="employeeNationality" class="form-label">สัญชาติ</label>
                        <select class="form-select" id="employeeNationality" name="employeeNationality">
                            <option value="">-- เลือกสัญชาติ --</option>
                            <option value="เมียนมา" @selected(old('employeeNationality', $employee->employeeNationality) == 'เมียนมา')>เมียนมา</option>
                            <option value="ลาว" @selected(old('employeeNationality', $employee->employeeNationality) == 'ลาว')>ลาว</option>
                            <option value="กัมพูชา" @selected(old('employeeNationality', $employee->employeeNationality) == 'กัมพูชา')>กัมพูชา</option>
                            <option value="เวียดนาม" @selected(old('employeeNationality', $employee->employeeNationality) == 'เวียดนาม')>เวียดนาม</option>
                        </select>
                    </div>
                </div>
                 <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="employeePassport" class="form-label">เลขหนังสือเดินทาง (Passport No.)</label>
                        <input type="text" class="form-control" id="employeePassport" name="employeePassport" value="{{ old('employeePassport', $employee->employeePassport) }}">
                    </div>
                     <div class="col-md-6 {{ old('employeeNationality', $employee->employeeNationality) == 'เมียนมา' ? '' : 'd-none' }}" id="passportTypeContainer">
                        <label for="passportType" class="form-label">ประเภทหนังสือเดินทาง</label>
                        <select class="form-select" id="passportType" name="passportType">
                            <option value="">-- เลือกประเภท --</option>
                            <option value="PJ" @selected(old('passportType', $employee->passportType) == 'PJ')>เล่ม PJ</option>
                            <option value="CI" @selected(old('passportType', $employee->passportType) == 'CI')>เล่ม CI</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/120x120/f8fafc/6c757d?text=Photo' }}" class="img-thumbnail mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                <input type="file" class="form-control form-control-sm" id="employeePhoto" name="employeePhoto">
                 @if($employee->employeePhoto)
                    <div class="form-check mt-1 text-start">
                        <input class="form-check-input" type="checkbox" name="remove_employeePhoto" id="remove_employeePhoto">
                        <label class="form-check-label small" for="remove_employeePhoto">ลบรูปภาพนี้</label>
                    </div>
                @endif
            </div>
        </div>

        <hr class="my-4">
        <h5>ข้อมูลการจ้างงานและเอกสารราชการ</h5>
        <div class="row g-3">
             <div class="col-md-4"><label for="namelistNo" class="form-label">เลขที่ Namelist</label><input type="text" class="form-control" id="namelistNo" name="namelistNo" value="{{ old('namelistNo', $employee->namelistNo) }}"></div>
            <div class="col-md-4"><label for="requestNo" class="form-label">เลขที่คำขอ</label><input type="text" class="form-control" id="requestNo" name="requestNo" value="{{ old('requestNo', $employee->requestNo) }}"></div>
            <div class="col-md-4"><label for="workerRefNo" class="form-label">เลขอ้างอิงคนงาน</label><input type="text" class="form-control" id="workerRefNo" name="workerRefNo" value="{{ old('workerRefNo', $employee->workerRefNo) }}"></div>
            <div class="col-md-4"><label for="personalId" class="form-label">เลขประจำตัว</label><input type="text" class="form-control" id="personalId" name="personalId" value="{{ old('personalId', $employee->personalId) }}"></div>
            <div class="col-md-4"><label for="companyWorkerId" class="form-label">รหัสคนงาน (บริษัท)</label><input type="text" class="form-control" id="companyWorkerId" name="companyWorkerId" value="{{ old('companyWorkerId', $employee->companyWorkerId) }}"></div>
            <div class="col-md-4"><label for="pinkCardNo" class="form-label">เลขบัตรชมพู</label><input type="text" class="form-control" id="pinkCardNo" name="pinkCardNo" value="{{ old('pinkCardNo', $employee->pinkCardNo) }}"></div>
            <div class="col-md-4"><label for="socialSecurityNo" class="form-label">เลขประกันสังคม</label><input type="text" class="form-control" id="socialSecurityNo" name="socialSecurityNo" value="{{ old('socialSecurityNo', $employee->socialSecurityNo) }}"></div>
            <div class="col-md-4"><label for="taxIdNo" class="form-label">เลขประจำตัวผู้เสียภาษี</label><input type="text" class="form-control" id="taxIdNo" name="taxIdNo" value="{{ old('taxIdNo', $employee->taxIdNo) }}"></div>
            <div class="col-md-4"><label for="designatedHospital" class="form-label">โรงพยาบาลตามสิทธิ</label><input type="text" class="form-control" id="designatedHospital" name="designatedHospital" value="{{ old('designatedHospital', $employee->designatedHospital) }}"></div>

            <div class="col-md-4"><label for="passportExpiryDate" class="form-label">วันหมดอายุหนังสือเดินทาง</label><input type="date" class="form-control" id="passportExpiryDate" name="passportExpiryDate" value="{{ old('passportExpiryDate', optional($employee->passportExpiryDate)->format('Y-m-d')) }}"></div>
            <div class="col-md-4"><label for="workPermitExpiryDate" class="form-label">วันหมดอายุใบอนุญาตทำงาน</label><input type="date" class="form-control" id="workPermitExpiryDate" name="workPermitExpiryDate" value="{{ old('workPermitExpiryDate', optional($employee->workPermitExpiryDate)->format('Y-m-d')) }}"></div>
            <div class="col-md-4"><label for="workPermitMOUGroup" class="form-label">ประเภทใบอนุญาตทำงาน(กลุ่ม มติ.)</label><select class="form-select" id="workPermitMOUGroup" name="workPermitMOUGroup"><option value="">-- กรุณาเลือก --</option><option value="MOU" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'MOU')>MOU</option><option value="มติต่ออายุในประเทศ" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'มติต่ออายุในประเทศ')>มติต่ออายุในประเทศ</option><option value="มติขึ้นทะเบียน" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'มติขึ้นทะเบียน')>มติขึ้นทะเบียน</option><option value="อื่นๆ" @selected(old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'อื่นๆ')>อื่นๆ ระบุ..</option></select><input type="text" class="form-control mt-2 {{ old('workPermitMOUGroup', $employee->workPermitMOUGroup) == 'อื่นๆ' ? '' : 'd-none' }}" id="workPermitMOUGroupOther" name="workPermitMOUGroupOther" placeholder="ระบุประเภทใบอนุญาตทำงาน" value="{{ old('workPermitMOUGroupOther', $employee->workPermitMOUGroupOther) }}"></div>
            <div class="col-md-4"><label for="visaExpiryDate" class="form-label">วันหมดอายุวีซ่า</label><input type="date" class="form-control" id="visaExpiryDate" name="visaExpiryDate" value="{{ old('visaExpiryDate', optional($employee->visaExpiryDate)->format('Y-m-d')) }}"></div>
            <div class="col-md-4"><label for="employeeWorkPermit" class="form-label">ใบอนุญาตทำงาน (Work Permit No.)</label><input type="text" class="form-control" id="employeeWorkPermit" name="employeeWorkPermit" value="{{ old('employeeWorkPermit', $employee->employeeWorkPermit) }}"></div>
            <div class="col-md-4"><label for="ninetyDayReportDate" class="form-label">วันหมดอายุรายงานตัว 90 วัน</label><input type="date" class="form-control" id="ninetyDayReportDate" name="ninetyDayReportDate" value="{{ old('ninetyDayReportDate', optional($employee->ninetyDayReportDate)->format('Y-m-d')) }}"></div>
            <div class="col-md-4"><label for="startDate" class="form-label">วันเริ่มงาน</label><input type="date" class="form-control" id="startDate" name="startDate" value="{{ old('startDate', optional($employee->startDate)->format('Y-m-d')) }}"></div>
            <div class="col-md-4"><label for="employeePhone" class="form-label">เบอร์โทรศัพท์</label><input type="tel" class="form-control" id="employeePhone" name="employeePhone" value="{{ old('employeePhone', $employee->employeePhone) }}"></div>
            <div class="col-md-4"><label for="employeePosition" class="form-label">ตำแหน่ง</label><input type="text" class="form-control" id="employeePosition" name="employeePosition" value="{{ old('employeePosition', $employee->employeePosition) }}"></div>
            <div class="col-md-4"><label for="nature_of_work" class="form-label">ลักษณะงาน (Nature of Work)</label><input type="text" class="form-control" id="nature_of_work" name="nature_of_work" value="{{ old('nature_of_work', $employee->nature_of_work) }}"></div>
        </div>

    <hr class="my-4">
    <h5>เอกสารแนบ</h5>
    <div class="row g-3">
        @for ($i = 1; $i <= 8; $i++)
    @php
        $doc_field = 'document_' . $i;
        $desc_field = 'document_description_' . $i;
        $labels = [
            1 => '1. passport/visa/workpermit',
            2 => '2. บัตรชมพู',
            3 => '3. สัญญาจ้างงาน',
            4 => '4. บัตรประชาชนเมียนมา/ทะเบียนบ้าน',
            5 => 'เอกสารอื่นๆ 1',
            6 => 'เอกสารอื่นๆ 2',
            7 => 'เอกสารอื่นๆ 3',
            8 => 'เอกสารอื่นๆ 4',
        ];
        // Description fields are now available for fields 3, 4, 5, 6, 7, 8
        $has_description_field = in_array($i, [3, 4, 5, 6, 7, 8]);
    @endphp
    <div class="col-md-4">
        <label for="{{ $doc_field }}" class="form-label fw-bold">{{ $labels[$i] }}</label>
        @if($employee->{$doc_field})
            <a href="{{ asset('storage/'.$employee->{$doc_field}) }}" target="_blank" class="small d-block mb-1 text-success"><i class="bi bi-file-earmark-text"></i> ดูไฟล์ปัจจุบัน</a>
        @endif
        <input type="file" class="form-control form-control-sm" id="{{ $doc_field }}" name="{{ $doc_field }}">

        @if($has_description_field)
            {{-- This is a dummy input for description, data will not be saved --}}
            <input type="text" class="form-control form-control-sm mt-2" name="dummy_desc_{{$i}}" placeholder="ระบุประเภทเอกสาร...">
        @endif

         @if($employee->{$doc_field})
            <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" name="remove_{{ $doc_field }}" id="remove_{{ $doc_field }}">
                <label class="form-check-label small text-danger" for="remove_{{ $doc_field }}">ลบไฟล์นี้</label>
            </div>
        @endif
    </div>
@endfor
    </div>

        <div class="mt-5 text-end">
            <button type="submit" class="btn btn-primary px-4">บันทึกข้อมูล</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('workPermitMOUGroup').addEventListener('change', function() {
        document.getElementById('workPermitMOUGroupOther').classList.toggle('d-none', this.value !== 'อื่นๆ');
    });

    // --- Conditional Passport Type Logic ---
    const nationalitySelect = document.getElementById('employeeNationality');
    const passportTypeContainer = document.getElementById('passportTypeContainer');

    function togglePassportTypeVisibility() {
        if (nationalitySelect.value === 'เมียนมา') {
            passportTypeContainer.classList.remove('d-none');
        } else {
            passportTypeContainer.classList.add('d-none');
        }
    }

    // Add event listener for changes
    nationalitySelect.addEventListener('change', togglePassportTypeVisibility);

    // Run on page load to set initial state
    document.addEventListener('DOMContentLoaded', function() {
        togglePassportTypeVisibility();
    });
</script>
@endpush

@endsection
