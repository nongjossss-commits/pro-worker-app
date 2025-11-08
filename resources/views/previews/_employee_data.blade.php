{{-- resources/views/previews/_employee_data.blade.php --}}

<div class="container-fluid">
    {{-- Personal Information --}}
    <h5>ข้อมูลส่วนตัว</h5>
    <hr>
    <div class="row">
        <div class="col-md-8">
            <div class="row mb-2">
                <div class="col-md-6">
                    <strong class="form-label">ชื่อพนักงาน (ไทย):</strong>
                    <p class="form-control-plaintext">{{ $employee->employeeTitleTh }} {{ $employee->employeeNameTh }}</p>
                </div>
                <div class="col-md-6">
                    <strong class="form-label">ชื่อพนักงาน (อังกฤษ):</strong>
                    <p class="form-control-plaintext">{{ $employee->employeeTitleEn }} {{ $employee->employeeNameEn }}</p>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">
                    <strong class="form-label">สังกัดนายจ้าง:</strong>
                    <p class="form-control-plaintext">{{ $employee->employer->employer_name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <strong class="form-label">วันเดือนปีเกิด:</strong>
                    <p class="form-control-plaintext">{{ formatDate($employee->employeeDob) }}</p>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-6">
                    <strong class="form-label">สัญชาติ:</strong>
                    <p class="form-control-plaintext">{{ displayValue($employee->employeeNationality) }}</p>
                </div>
                <div class="col-md-6">
                    <strong class="form-label">เลขหนังสือเดินทาง (Passport No.):</strong>
                    <p class="form-control-plaintext">{{ displayValue($employee->employeePassport) }}
                        @if($employee->passportType)
                            <span class="badge bg-secondary">{{ $employee->passportType }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-center">
            <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/120x120/f8fafc/6c757d?text=Photo' }}"
                 class="img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;">
        </div>
    </div>

    <hr class="my-4">
    <h5>ข้อมูลการจ้างงานและเอกสารราชการ</h5>
    <div class="row g-3">
        <div class="col-md-4"><strong>เลขที่ Namelist:</strong><p class="form-control-plaintext">{{ displayValue($employee->namelistNo) }}</p></div>
        <div class="col-md-4"><strong>เลขที่คำขอ:</strong><p class="form-control-plaintext">{{ displayValue($employee->requestNo) }}</p></div>
        <div class="col-md-4"><strong>เลขอ้างอิงคนงาน:</strong><p class="form-control-plaintext">{{ displayValue($employee->workerRefNo) }}</p></div>
        <div class="col-md-4"><strong>เลขประจำตัว:</strong><p class="form-control-plaintext">{{ displayValue($employee->personalId) }}</p></div>
        <div class="col-md-4"><strong>รหัสคนงาน (บริษัท):</strong><p class="form-control-plaintext">{{ displayValue($employee->companyWorkerId) }}</p></div>
        <div class="col-md-4"><strong>เลขบัตรชมพู:</strong><p class="form-control-plaintext">{{ displayValue($employee->pinkCardNo) }}</p></div>
        <div class="col-md-4"><strong>เลขประกันสังคม:</strong><p class="form-control-plaintext">{{ displayValue($employee->socialSecurityNo) }}</p></div>
        <div class="col-md-4"><strong>เลขประจำตัวผู้เสียภาษี:</strong><p class="form-control-plaintext">{{ displayValue($employee->taxIdNo) }}</p></div>
        <div class="col-md-4"><strong>โรงพยาบาลตามสิทธิ:</strong><p class="form-control-plaintext">{{ displayValue($employee->designatedHospital) }}</p></div>
        <div class="col-md-4"><strong>วันหมดอายุหนังสือเดินทาง:</strong><p class="form-control-plaintext">{{ formatDate($employee->passportExpiryDate) }}</p></div>
        <div class="col-md-4"><strong>วันหมดอายุใบอนุญาตทำงาน:</strong><p class="form-control-plaintext">{{ formatDate($employee->workPermitExpiryDate) }}</p></div>
        <div class="col-md-4"><strong>ประเภทใบอนุญาตทำงาน:</strong><p class="form-control-plaintext">{{ $employee->workPermitMOUGroup === 'อื่นๆ' ? displayValue($employee->workPermitMOUGroupOther) : displayValue($employee->workPermitMOUGroup) }}</p></div>
        <div class="col-md-4"><strong>วันหมดอายุวีซ่า:</strong><p class="form-control-plaintext">{{ formatDate($employee->visaExpiryDate) }}</p></div>
        <div class="col-md-4"><strong>ใบอนุญาตทำงาน (Work Permit No.):</strong><p class="form-control-plaintext">{{ displayValue($employee->employeeWorkPermit) }}</p></div>
        <div class="col-md-4"><strong>วันหมดอายุรายงานตัว 90 วัน:</strong><p class="form-control-plaintext">{{ formatDate($employee->ninetyDayReportDate) }}</p></div>
        <div class="col-md-4"><strong>วันเริ่มงาน:</strong><p class="form-control-plaintext">{{ formatDate($employee->startDate) }}</p></div>
        <div class="col-md-4"><strong>เบอร์โทรศัพท์:</strong><p class="form-control-plaintext">{{ displayValue($employee->employeePhone) }}</p></div>
        <div class="col-md-4"><strong>อีเมล:</strong><p class="form-control-plaintext">{{ displayValue($employee->email) }}</p></div>
        <div class="col-md-4"><strong>ตำแหน่ง:</strong><p class="form-control-plaintext">{{ displayValue($employee->employeePosition) }}</p></div>
        <div class="col-md-4"><strong>ลักษณะงาน:</strong><p class="form-control-plaintext">{{ displayValue($employee->nature_of_work) }}</p></div>
    </div>

    <hr class="my-4">
    <h5>เอกสารแนบ</h5>
    <div class="row g-3">
        @php
        $labels = [
            1 => '1. passport/visa/workpermit', 2 => '2. บัตรชมพู', 3 => '3. สัญญาจ้างงาน',
            4 => '4. บัตรประชาชนเมียนมา/ทะเบียนบ้าน', 5 => 'เอกสารอื่นๆ 1', 6 => 'เอกสารอื่นๆ 2',
            7 => 'เอกสารอื่นๆ 3', 8 => 'เอกสารอื่นๆ 4',
        ];
        @endphp
        @foreach ($labels as $i => $label)
            @php
                $doc_field = 'document_' . $i;
                $document_path = $employee->{$doc_field};
            @endphp
            <div class="col-md-4">
                <strong>{{ $label }}:</strong>
                @if($document_path)
                    <p class="form-control-plaintext">
                        <a href="{{ asset('storage/' . $document_path) }}" target="_blank">
                            <i class="bi bi-file-earmark-text"></i> ดูเอกสาร
                        </a>
                    </p>
                @else
                    <p class="form-control-plaintext text-muted">N/A</p>
                @endif
            </div>
        @endforeach
    </div>
</div>
