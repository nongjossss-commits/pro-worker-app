{{--
    This view is intentionally simplified to display data.
    It does not include @extends, @section, or any form elements.
    It is loaded via AJAX into a modal.
--}}
<div class="content-section">
    {{-- Personal Information --}}
    <h5>ข้อมูลส่วนตัว</h5>
    <hr>
    <div class="row">
        <div class="col-md-8">
            {{-- Employer Name (as per blueprint) --}}
            <div class="mb-3">
                <label class="form-label fw-bold">สังกัดนายจ้าง:</label>
                <p class="form-control-plaintext">{{ $employee->employer->employerNameTh ?? 'N/A' }}</p>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">ชื่อพนักงาน (ไทย)</label>
                    <p class="form-control-plaintext">{{ trim(($employee->employeeTitleTh ?? '') . ' ' . ($employee->employeeNameTh ?? '')) ?: 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">ชื่อพนักงาน (อังกฤษ)</label>
                    <p class="form-control-plaintext">{{ trim(($employee->employeeTitleEn ?? '') . ' ' . ($employee->employeeNameEn ?? '')) ?: 'N/A' }}</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">วันเดือนปีเกิด</label>
                    <p class="form-control-plaintext">{{ $employee->employeeDob ? $employee->employeeDob->format('d/m/Y') : 'N/A' }}</p>
                </div>
                 <div class="col-md-6">
                    <label class="form-label fw-bold">สัญชาติ</label>
                    <p class="form-control-plaintext">{{ $employee->employeeNationality ?? 'N/A' }}</p>
                </div>
            </div>
             <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">เลขหนังสือเดินทาง (Passport No.)</label>
                    <p class="form-control-plaintext">{{ $employee->employeePassport ?? 'N/A' }}</p>
                </div>
                 <div class="col-md-6">
                    <label class="form-label fw-bold">ประเภทหนังสือเดินทาง</label>
                    <p class="form-control-plaintext">{{ $employee->passportType ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-center">
            <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/120x120/f8fafc/6c757d?text=Photo' }}" class="img-thumbnail mb-2" style="width: 120px; height: 120px; object-fit: cover;">
        </div>
    </div>

    <hr class="my-4">
    <h5>ข้อมูลการจ้างงานและเอกสารราชการ</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">เลขที่ Namelist</label><p class="form-control-plaintext">{{ $employee->namelistNo ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขที่คำขอ</label><p class="form-control-plaintext">{{ $employee->requestNo ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขอ้างอิงคนงาน</label><p class="form-control-plaintext">{{ $employee->workerRefNo ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขประจำตัว</label><p class="form-control-plaintext">{{ $employee->personalId ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">รหัสคนงาน (บริษัท)</label><p class="form-control-plaintext">{{ $employee->companyWorkerId ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขบัตรชมพู</label><p class="form-control-plaintext">{{ $employee->pinkCardNo ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขประกันสังคม</label><p class="form-control-plaintext">{{ $employee->socialSecurityNo ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขประจำตัวผู้เสียภาษี</label><p class="form-control-plaintext">{{ $employee->taxIdNo ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">โรงพยาบาลตามสิทธิ</label><p class="form-control-plaintext">{{ $employee->designatedHospital ?? 'N/A' }}</p></div>

        <div class="col-md-4"><label class="form-label fw-bold">วันหมดอายุหนังสือเดินทาง</label><p class="form-control-plaintext">{{ $employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">วันหมดอายุใบอนุญาตทำงาน</label><p class="form-control-plaintext">{{ $employee->workPermitExpiryDate ? $employee->workPermitExpiryDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4">
            <label class="form-label fw-bold">ประเภทใบอนุญาตทำงาน(กลุ่ม มติ.)</label>
            <p class="form-control-plaintext">
                {{ $employee->workPermitMOUGroup ?? 'N/A' }}
                @if($employee->workPermitMOUGroup === 'อื่นๆ')
                    ({{ $employee->workPermitMOUGroupOther ?? 'ไม่ระบุ' }})
                @endif
            </p>
        </div>
        <div class="col-md-4"><label class="form-label fw-bold">วันหมดอายุวีซ่า</label><p class="form-control-plaintext">{{ $employee->visaExpiryDate ? $employee->visaExpiryDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">ใบอนุญาตทำงาน (Work Permit No.)</label><p class="form-control-plaintext">{{ $employee->employeeWorkPermit ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">วันหมดอายุรายงานตัว 90 วัน</label><p class="form-control-plaintext">{{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">วันเริ่มงาน</label><p class="form-control-plaintext">{{ $employee->startDate ? $employee->startDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เบอร์โทรศัพท์</label><p class="form-control-plaintext">{{ $employee->employeePhone ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">อีเมล</label><p class="form-control-plaintext">{{ $employee->email ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">รหัสผ่าน / หมายเหตุ</label><p class="form-control-plaintext">{{ $employee->password ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">ตำแหน่ง</label><p class="form-control-plaintext">{{ $employee->employeePosition ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">ลักษณะงาน (Nature of Work)</label><p class="form-control-plaintext">{{ $employee->nature_of_work ?? 'N/A' }}</p></div>
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
            $has_description_field = in_array($i, [3, 4, 5, 6, 7, 8]);
        @endphp
        <div class="col-md-4">
            <label class="form-label fw-bold">{{ $labels[$i] }}</label>
            @if($employee->{$doc_field})
                {{-- Blueprint: Render as a link --}}
                <p class="form-control-plaintext">
                    <a href="{{ Storage::url($employee->{$doc_field}) }}" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> ดูเอกสาร
                    </a>
                    @if($has_description_field && $employee->{$desc_field})
                        <span class="text-muted"> ({{ $employee->{$desc_field} }})</span>
                    @endif
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
    @endfor
</div>
</div>
