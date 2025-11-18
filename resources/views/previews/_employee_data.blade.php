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
                    <label class="form-label fw-bold">อายุ</label>
                    <p class="form-control-plaintext">{{ $employee->employeeAge ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">เพศ</label>
                    <p class="form-control-plaintext">{{ $employee->employeeGender ?? 'N/A' }}</p>
                </div>
                 <div class="col-md-6">
                    <label class="form-label fw-bold">สัญชาติ</label>
                    <p class="form-control-plaintext">{{ $employee->employeeNationality ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">ชื่อบิดา</label>
                    <p class="form-control-plaintext">{{ $employee->father_name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">ชื่อมารดา</label>
                    <p class="form-control-plaintext">{{ $employee->mother_name ?? 'N/A' }}</p>
                </div>
            </div>
             <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">เลขหนังสือเดินทาง</label>
                    <p class="form-control-plaintext">{{ $employee->employeePassport ?? 'N/A' }}</p>
                </div>
                 <div class="col-md-4">
                    <label class="form-label fw-bold">ประเภทหนังสือเดินทาง</label>
                     <p class="form-control-plaintext">
                        @if($employee->employeeNationality === 'กัมพูชา')
                            {{ $employee->passport_type_cambodia ?? 'N/A' }}
                        @else
                            {{ $employee->passportType ?? 'N/A' }}
                        @endif
                    </p>
                </div>
                 <div class="col-md-4">
                    <label class="form-label fw-bold">วันหมดอายุหนังสือเดินทาง</label>
                    <p class="form-control-plaintext">{{ $employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : 'N/A' }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 d-flex flex-column align-items-center justify-content-center">
            <img src="{{ $employee->photo_url }}" class="img-thumbnail mb-2" style="width: 120px; height: 120px; object-fit: cover;">
        </div>
    </div>

    <hr class="my-4">

    {{-- Work Permit & Visa --}}
    <h5>ข้อมูลใบอนุญาตทำงานและวีซ่า</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">ใบอนุญาตทำงาน (Work Permit No.)</label><p class="form-control-plaintext">{{ $employee->employeeWorkPermit ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">วันหมดอายุใบอนุญาตทำงาน</label><p class="form-control-plaintext">{{ $employee->workPermitExpiryDate ? $employee->workPermitExpiryDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4">
            <label class="form-label fw-bold">ประเภทใบอนุญาตทำงาน (กลุ่ม มติ.)</label>
            <p class="form-control-plaintext">
                {{ $employee->workPermitMOUGroup ?? 'N/A' }}
                @if($employee->workPermitMOUGroup === 'อื่นๆ')
                    ({{ $employee->workPermitMOUGroupOther ?? 'ไม่ระบุ' }})
                @endif
            </p>
        </div>
        <div class="col-md-4"><label class="form-label fw-bold">ประเภทวีซ่า</label><p class="form-control-plaintext">{{ $employee->visaType ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">วันหมดอายุวีซ่า</label><p class="form-control-plaintext">{{ $employee->visaExpiryDate ? $employee->visaExpiryDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">วันหมดอายุรายงานตัว 90 วัน</label><p class="form-control-plaintext">{{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : 'N/A' }}</p></div>
    </div>

    <hr class="my-4">

    {{-- Job & Contact Info --}}
    <h5>ข้อมูลการจ้างงานและข้อมูลติดต่อ</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">ตำแหน่ง</label><p class="form-control-plaintext">{{ $employee->job_title ?? 'N/A' }}</p></div>
        <div class="col-md-8"><label class="form-label fw-bold">ลักษณะงาน (Nature of Work)</label><p class="form-control-plaintext">{{ $employee->job_description ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">วันเริ่มงาน</label><p class="form-control-plaintext">{{ $employee->startDate ? $employee->startDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เบอร์โทรศัพท์</label><p class="form-control-plaintext">{{ $employee->employeePhone ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">อีเมล</label><p class="form-control-plaintext">{{ $employee->email ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">รหัสผ่าน / หมายเหตุ</label><p class="form-control-plaintext">{{ $employee->password ?? 'N/A' }}</p></div>
    </div>

    <hr class="my-4">

    {{-- Official Document Numbers --}}
    <h5>ข้อมูลเอกสารราชการ</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">เลขที่ Namelist</label><p class="form-control-plaintext">{{ $employee->name_list_number ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขที่คำขอ</label><p class="form-control-plaintext">{{ $employee->request_number ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขอ้างอิงคนงาน</label><p class="form-control-plaintext">{{ $employee->employee_reference_id ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขประจำตัว</label><p class="form-control-plaintext">{{ $employee->employee_id_number ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">รหัสคนงาน (บริษัท)</label><p class="form-control-plaintext">{{ $employee->employer_employee_id ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขบัตรชมพู</label><p class="form-control-plaintext">{{ $employee->pinkCardNo ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">เลขประจำตัวผู้เสียภาษี</label><p class="form-control-plaintext">{{ $employee->tax_id_number ?? 'N/A' }}</p></div>
    </div>

    <hr class="my-4">

    {{-- Insurance Information --}}
    <h5>ข้อมูลประกัน</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">ประเภทประกัน</label><p class="form-control-plaintext">{{ $employee->insurance_type ?? 'N/A' }}</p></div>
        @if($employee->insurance_type === 'ประกันสังคม')
            <div class="col-md-4"><label class="form-label fw-bold">เลขประกันสังคม</label><p class="form-control-plaintext">{{ $employee->social_security_number ?? 'N/A' }}</p></div>
            <div class="col-md-4"><label class="form-label fw-bold">โรงพยาบาลตามสิทธิ</label><p class="form-control-plaintext">{{ $employee->insurance_detail_hospital ?? 'N/A' }}</p></div>
            <div class="col-md-4"><label class="form-label fw-bold">วันหมดอายุ</label><p class="form-control-plaintext">{{ $employee->insurance_expiry_date_hospital ? $employee->insurance_expiry_date_hospital->format('d/m/Y') : 'N/A' }}</p></div>
        @elseif($employee->insurance_type === 'ประกันสุขภาพเอกชน')
            <div class="col-md-4"><label class="form-label fw-bold">บริษัทประกัน</label><p class="form-control-plaintext">{{ $employee->insurance_detail_private ?? 'N/A' }}</p></div>
            <div class="col-md-4"><label class="form-label fw-bold">วันหมดอายุ</label><p class="form-control-plaintext">{{ $employee->insurance_expiry_date_private ? $employee->insurance_expiry_date_private->format('d/m/Y') : 'N/A' }}</p></div>
        @else
             <div class="col-md-8"><label class="form-label fw-bold">รายละเอียด</label><p class="form-control-plaintext">{{ $employee->insurance_detail ?? 'N/A' }}</p></div>
        @endif
    </div>

<hr class="my-4">

{{-- V6: Standard Attachments --}}
<h5>ไฟล์แนบมาตรฐาน</h5>
<div class="row g-3">
    @php
        $fileFields = [
            'passport_file_path' => 'ไฟล์หนังสือเดินทาง',
            'visa_file_path' => 'ไฟล์วีซ่า',
            'work_permit_file_path' => 'ไฟล์ใบอนุญาตทำงาน',
            'pink_card_file_path' => 'ไฟล์บัตรชมพู',
            'insurance_attachment_path' => 'ไฟล์แนบประกัน',
        ];
        // Conditionally add insurance documents based on type
        if ($employee->insurance_type === 'ประกันสังคม') {
            $fileFields['insurance_document_path'] = 'ไฟล์ประกันสังคม';
        } elseif ($employee->insurance_type === 'ประกันสุขภาพเอกชน') {
            $fileFields['insurance_document_path_private'] = 'ไฟล์ประกันสุขภาพเอกชน';
        }
    @endphp

    @foreach($fileFields as $field => $label)
    <div class="col-md-4">
        <label class="form-label fw-bold">{{ $label }}</label>
        @if($employee->{$field})
            <p class="form-control-plaintext">
                <a href="{{ Storage::disk('private')->url($employee->{$field}) }}" target="_blank">
                    <i class="bi bi-file-earmark-text"></i> ดูเอกสาร
                </a>
            </p>
        @else
            <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
        @endif
    </div>
    @endforeach
</div>

<hr class="my-4">

{{-- V6: Other Attachments --}}
<h5>ไฟล์แนบอื่นๆ</h5>
<div class="row g-3">
    @for ($i = 1; $i <= 12; $i++)
        @php
            $doc_field = 'employee_doc_' . $i;
            $desc_field = 'other_doc_' . $i . '_desc'; // Assuming description fields follow this pattern
        @endphp
        <div class="col-md-4">
            <label class="form-label fw-bold">เอกสารแนบ {{ $i }}</label>
            @if($employee->{$doc_field})
                <p class="form-control-plaintext">
                    <a href="{{ Storage::disk('public')->url($employee->{$doc_field}) }}" target="_blank">
                        <i class="bi bi-file-earmark-text"></i> ดูเอกสาร
                    </a>
                    @if($employee->{$desc_field})
                        <span class="text-muted d-block" style="font-size: 0.875em;">({{ $employee->{$desc_field} }})</span>
                    @endif
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
    @endfor
</div>
</div>
