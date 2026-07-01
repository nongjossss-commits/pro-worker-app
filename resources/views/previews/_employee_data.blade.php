{{--
    This view is loaded via AJAX into the universal preview modal.

    Layout goals (redesign):
      * Portrait phone: 2-column grid of compact fields, no wasted vertical space
      * Landscape phone: 3-column grid, no label overlap
      * Tablet / desktop: 4-column grid
      * Wide desktop / foldable open: 6-column grid + widened modal so
        content isn't trapped in a narrow center strip.

    All CSS is scoped under .emp-preview so it doesn't leak to other modals.
--}}
<style>
    /* --- Scoped preview styles --- */
    .emp-preview .preview-section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #f97316;
        border-left: 4px solid #f97316;
        padding-left: 8px;
        margin: 0 0 10px;
        letter-spacing: 0.2px;
    }
    .emp-preview .preview-hr {
        border-top: 1px dashed #e5e7eb;
        margin: 12px 0 14px;
    }
    .emp-preview .preview-field {
        margin-bottom: 8px;
        min-width: 0; /* let long values wrap instead of blowing the column */
    }
    .emp-preview .preview-label {
        display: block;
        font-size: 0.7rem;
        color: #6b7280;
        line-height: 1.15;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .emp-preview .preview-value {
        font-size: 0.88rem;
        font-weight: 500;
        line-height: 1.25;
        color: #111827;
        word-break: break-word;
        min-height: 1.25rem;
    }
    .emp-preview .preview-section-title { font-size: 0.95rem; }
    .emp-preview .preview-value.text-muted-empty {
        color: #9ca3af;
        font-weight: 400;
    }
    .emp-preview .preview-photo-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
    }
    .emp-preview .preview-photo {
        width: 110px;
        height: 110px;
        object-fit: cover;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    @media (min-width: 576px) {
        .emp-preview .preview-photo { width: 140px; height: 140px; }
    }
    /* Tablet: modest bump so labels are still comfortable when four columns
       share a modal-xl row (~250px each). */
    @media (min-width: 768px) {
        .emp-preview .preview-photo { width: 170px; height: 170px; }
        .emp-preview .preview-label { font-size: 0.8rem; }
        .emp-preview .preview-value { font-size: 1rem; }
        .emp-preview .preview-section-title { font-size: 1.05rem; }
        .emp-preview .preview-field { margin-bottom: 10px; }
        .emp-preview .preview-attach-btn { padding: 4px 10px; font-size: 0.85rem; }
    }
    /* PC / desktop: bigger and darker labels.
       Feedback: on wide monitors fields have plenty of horizontal space, so
       we don't need to save space — bump sizes and use a darker gray so
       labels are easy to read against white. Mobile & tablet unchanged. */
    @media (min-width: 992px) {
        .emp-preview .preview-label {
            font-size: 0.95rem;
            color: #374151;   /* gray-700 — clear separation from #111827 value */
            font-weight: 500;
            line-height: 1.3;
            margin-bottom: 4px;
        }
        .emp-preview .preview-value {
            font-size: 1.15rem;
            font-weight: 600;
            line-height: 1.4;
        }
        .emp-preview .preview-section-title { font-size: 1.2rem; }
        .emp-preview .preview-field { margin-bottom: 16px; }
        .emp-preview .preview-attach-btn { padding: 5px 12px; font-size: 0.95rem; }
    }
    /* Wide desktop / foldable open: slightly larger photo + extra breathing
       room in the value line-height. */
    @media (min-width: 1200px) {
        .emp-preview .preview-photo { width: 200px; height: 200px; }
        .emp-preview .preview-label { font-size: 1rem; }
        .emp-preview .preview-value { font-size: 1.2rem; line-height: 1.45; }
        .emp-preview .preview-section-title { font-size: 1.25rem; }
        .emp-preview .preview-field { margin-bottom: 18px; }
    }
    /* Sticky section headers so the user knows where they are while scrolling */
    .emp-preview .preview-section-header {
        position: sticky;
        top: 0;
        background: #ffffff;
        padding: 6px 0 4px;
        z-index: 2;
    }
    /* Compact attachment buttons on mobile */
    .emp-preview .preview-attach-btn {
        padding: 2px 8px;
        font-size: 0.75rem;
    }
    /* Foldable / wide-screen: let the modal breathe.
       This targets the universal preview modal so it only applies when
       this partial is shown. modal-xl caps at 1140px which wastes space
       on ~1800px+ portrait displays. */
    @media (min-width: 1400px) {
        #universalPreviewModal .modal-dialog { max-width: 1360px; }
    }
    @media (min-width: 1700px) {
        #universalPreviewModal .modal-dialog { max-width: 92vw; }
    }
</style>

@php
    // Helper: render a "N/A" fallback with softer styling so real data
    // pops visually against unfilled fields. Callers use it via short
    // inline @if or the small closure below.
    $val = function ($v) {
        if ($v === null || $v === '' || $v === 'N/A') {
            return '<span class="preview-value text-muted-empty">-</span>';
        }
        return '<span class="preview-value">' . e($v) . '</span>';
    };

    // Passport type resolves per-nationality
    $passportTypeVal = $employee->employeeNationality === 'กัมพูชา'
        ? ($employee->passport_type_cambodia ?? null)
        : ($employee->passportType ?? null);

    $mouGroupVal = $employee->workPermitMOUGroup;
    if ($mouGroupVal === 'อื่นๆ' && $employee->workPermitMOUGroupOther) {
        $mouGroupVal .= ' (' . $employee->workPermitMOUGroupOther . ')';
    }
@endphp

<div class="emp-preview content-section">

    {{-- ================== SECTION 1: Personal + Photo ================== --}}
    <div class="row g-2 mb-1">
        {{-- Photo column: shrinks on mobile, grows on desktop --}}
        <div class="col-4 col-sm-3 col-md-3 col-lg-2 preview-photo-wrap">
            <a href="{{ $employee->photo_url }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $employee->photo_url }}" class="preview-photo" alt="Employee Photo">
            </a>
        </div>

        {{-- Header info: sits inline with the photo on all screens --}}
        <div class="col-8 col-sm-9 col-md-9 col-lg-10">
            <div class="preview-section-header">
                <h6 class="preview-section-title mb-2">ข้อมูลส่วนตัว</h6>
            </div>
            @php
                // Employer name shows Thai + English when both are recorded so
                // an English-locale user can still identify the employer even
                // though the surrounding labels aren't translated.
                $employerTh = $employee->employer->employerNameTh ?? null;
                $employerEn = $employee->employer->employerNameEn ?? null;
                if ($employerTh && $employerEn && trim($employerEn) !== '') {
                    $employerDisplay = $employerTh . ' (' . $employerEn . ')';
                } else {
                    $employerDisplay = $employerTh ?: $employerEn;
                }
            @endphp
            <div class="row g-2">
                <div class="col-12 preview-field">
                    <span class="preview-label">สังกัดนายจ้าง</span>
                    {!! $val($employerDisplay) !!}
                </div>
                {{-- English name first (left) so language-independent readers
                     can spot the employee identity without needing to parse Thai.
                     Thai name follows on the right. --}}
                <div class="col-6 preview-field">
                    <span class="preview-label">ชื่อ (อังกฤษ)</span>
                    {!! $val(trim(($employee->employeeTitleEn ?? '') . ' ' . ($employee->employeeNameEn ?? '')) ?: null) !!}
                </div>
                <div class="col-6 preview-field">
                    <span class="preview-label">ชื่อ (ไทย)</span>
                    {!! $val(trim(($employee->employeeTitleTh ?? '') . ' ' . ($employee->employeeNameTh ?? '')) ?: null) !!}
                </div>
                @if($employee->name_suffix)
                <div class="col-12 preview-field">
                    <span class="preview-label">ต่อท้ายชื่อ (EN)</span>
                    {!! $val($employee->name_suffix) !!}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Compact numeric strip: DOB, Age, Gender, Nationality, Height, Weight
         2 cols portrait / 3 cols landscape / 6 cols tablet+ so all fit one row --}}
    <div class="row g-2 mb-2">
        <div class="col-6 col-sm-4 col-md-2 preview-field">
            <span class="preview-label">วันเดือนปีเกิด</span>
            {!! $val($employee->employeeDob ? $employee->employeeDob->format('d/m/Y') : null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-2 preview-field">
            <span class="preview-label">อายุ</span>
            {!! $val($employee->age ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-2 preview-field">
            <span class="preview-label">เพศ</span>
            {!! $val($employee->gender ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-2 preview-field">
            <span class="preview-label">สัญชาติ</span>
            {!! $val($employee->employeeNationality ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-2 preview-field">
            <span class="preview-label">ส่วนสูง</span>
            {!! $val($employee->height ? $employee->height . ' cm' : null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-2 preview-field">
            <span class="preview-label">น้ำหนัก</span>
            {!! $val($employee->weight ? $employee->weight . ' kg' : null) !!}
        </div>
    </div>

    {{-- Parents --}}
    <div class="row g-2 mb-2">
        <div class="col-6 col-md-3 preview-field">
            <span class="preview-label">ชื่อบิดา</span>
            {!! $val($employee->father_name ?? null) !!}
        </div>
        <div class="col-6 col-md-3 preview-field">
            <span class="preview-label">ชื่อมารดา</span>
            {!! $val($employee->mother_name ?? null) !!}
        </div>
    </div>

    {{-- Passport row --}}
    <div class="row g-2 mb-1">
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เลขหนังสือเดินทาง</span>
            {!! $val($employee->employeePassport ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">{{ __('Passport Issue Place') }}</span>
            {!! $val($employee->passport_issue_place ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ประเภทหนังสือเดินทาง</span>
            {!! $val($passportTypeVal) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">วันหมดอายุ Passport</span>
            {!! $val($employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : null) !!}
        </div>
    </div>

    <hr class="preview-hr">

    {{-- ================== SECTION 2: Work Permit & Visa ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">ใบอนุญาตทำงานและวีซ่า</h6>
    </div>
    <div class="row g-2 mb-1">
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">Work Permit No.</span>
            {!! $val($employee->employeeWorkPermit ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">วันหมดอายุ Work Permit</span>
            {!! $val($employee->workPermitExpiryDate ? $employee->workPermitExpiryDate->format('d/m/Y') : null) !!}
        </div>
        <div class="col-12 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ประเภทใบอนุญาต (กลุ่มมติ)</span>
            {!! $val($mouGroupVal) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ประเภทวีซ่า</span>
            {!! $val($employee->visaType ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">{{ __('Visa Issue Place') }}</span>
            {!! $val($employee->visa_issue_place ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">วันที่ตรวจลงตราวีซ่า</span>
            {!! $val($employee->visaEndorsementDate ? $employee->visaEndorsementDate->format('d/m/Y') : null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เลขที่ตรวจลงตราวีซ่า</span>
            {!! $val($employee->visaEndorsementNo ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">วันหมดอายุวีซ่า</span>
            {!! $val($employee->visaExpiryDate ? $employee->visaExpiryDate->format('d/m/Y') : null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">รายงานตัว 90 วัน</span>
            {!! $val($employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : null) !!}
        </div>
    </div>

    <hr class="preview-hr">

    {{-- ================== SECTION 3: Job & Contact ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">การจ้างงานและติดต่อ</h6>
    </div>
    <div class="row g-2 mb-1">
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ตำแหน่ง</span>
            {!! $val($employee->job_title ?? null) !!}
        </div>
        <div class="col-12 col-sm-8 col-md-6 preview-field">
            <span class="preview-label">ลักษณะงาน (Nature of Work)</span>
            {!! $val($employee->job_description ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">วันเริ่มงาน</span>
            {!! $val($employee->startDate ? $employee->startDate->format('d/m/Y') : null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">{{ __('Work Age') }}</span>
            {!! $val($employee->work_age) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เบอร์โทร</span>
            {!! $val($employee->employeePhone ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">อีเมล</span>
            {!! $val($employee->email ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">รหัสอีเมล</span>
            {!! $val($employee->password ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">รหัส outsource</span>
            {!! $val($employee->outsource_code ?? null) !!}
        </div>
    </div>

    <hr class="preview-hr">

    {{-- ================== SECTION 4: Official Docs ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">เอกสารราชการ</h6>
    </div>
    <div class="row g-2 mb-1">
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เลข RA (outsource)</span>
            {!! $val($employee->name_list_number ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เลขที่คำขอ</span>
            {!! $val($employee->request_number ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เลขอ้างอิงคนงาน</span>
            {!! $val($employee->employee_reference_id ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เลขประจำตัว</span>
            {!! $val($employee->employee_id_number ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">รหัสคนงาน (บริษัท)</span>
            {!! $val($employee->employer_employee_id ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">{{ __('Department') }}</span>
            {!! $val($employee->department ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เลขบัตรชมพู</span>
            {!! $val($employee->pinkCardNo ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เลขผู้เสียภาษี</span>
            {!! $val($employee->tax_id_number ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ชื่อธนาคาร</span>
            {!! $val($employee->bank_name ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">เลขบัญชี</span>
            {!! $val($employee->bank_account_number ?? null) !!}
        </div>
    </div>

    <hr class="preview-hr">

    {{-- ================== SECTION 5: Insurance ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">ประกัน</h6>
    </div>
    <div class="row g-2 mb-1">
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ประเภทประกัน</span>
            {!! $val($employee->insurance_type ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">โรงพยาบาลตรวจโรค</span>
            {!! $val($employee->medical_hospital_name ?? null) !!}
        </div>
        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ใบรับรองแพทย์</span>
            @if($employee->medical_certificate_path)
                <div>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ Storage::disk('public')->url($employee->medical_certificate_path) }}', 'ดูใบรับรองแพทย์')" class="btn btn-success preview-attach-btn text-white">
                        <i class="bi bi-eye-fill"></i> ดู
                    </a>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'medical_certificate_path']) }}', 'ใบรับรองแพทย์')" class="btn btn-danger preview-attach-btn text-white ms-1">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </a>
                </div>
            @else
                <span class="preview-value text-muted-empty">-</span>
            @endif
        </div>

        @if($employee->insurance_type === 'ประกันสังคม')
            <div class="col-6 col-sm-4 col-md-3 preview-field">
                <span class="preview-label">เลขประกันสังคม</span>
                {!! $val($employee->social_security_number ?? null) !!}
            </div>
            <div class="col-6 col-sm-4 col-md-3 preview-field">
                <span class="preview-label">วันที่ออกบัตร</span>
                {!! $val($employee->sso_issue_date ? $employee->sso_issue_date->format('d/m/Y') : null) !!}
            </div>
            <div class="col-6 col-sm-4 col-md-3 preview-field">
                <span class="preview-label">วันหมดอายุ</span>
                {!! $val($employee->sso_expiry_date ? $employee->sso_expiry_date->format('d/m/Y') : null) !!}
            </div>
            <div class="col-6 col-sm-4 col-md-3 preview-field">
                <span class="preview-label">โรงพยาบาลตามสิทธิ</span>
                {!! $val($employee->insurance_detail ?? null) !!}
            </div>
        @elseif($employee->insurance_type === 'ประกันโรงพยาบาล')
            <div class="col-6 col-sm-4 col-md-3 preview-field">
                <span class="preview-label">โรงพยาบาล</span>
                {!! $val($employee->insurance_detail_hospital ?? null) !!}
            </div>
            <div class="col-6 col-sm-4 col-md-3 preview-field">
                <span class="preview-label">วันหมดอายุ</span>
                {!! $val($employee->insurance_expiry_date_hospital ? $employee->insurance_expiry_date_hospital->format('d/m/Y') : null) !!}
            </div>
        @elseif($employee->insurance_type === 'ประกันเอกชน')
            <div class="col-6 col-sm-4 col-md-3 preview-field">
                <span class="preview-label">บริษัทประกัน</span>
                {!! $val($employee->insurance_detail_private ?? null) !!}
            </div>
            <div class="col-6 col-sm-4 col-md-3 preview-field">
                <span class="preview-label">วันหมดอายุ</span>
                {!! $val($employee->insurance_expiry_date_private ? $employee->insurance_expiry_date_private->format('d/m/Y') : null) !!}
            </div>
        @endif

        <div class="col-6 col-sm-4 col-md-3 preview-field">
            <span class="preview-label">ไฟล์แนบประกัน</span>
            @if($employee->insurance_document_path_private)
                <div>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ Storage::disk('public')->url($employee->insurance_document_path_private) }}', 'ดูประกัน')" class="btn btn-success preview-attach-btn text-white">
                        <i class="bi bi-eye-fill"></i> ดู
                    </a>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'insurance_document_path_private']) }}', 'ประกัน')" class="btn btn-danger preview-attach-btn text-white ms-1">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </a>
                </div>
            @else
                <span class="preview-value text-muted-empty">-</span>
            @endif
        </div>
    </div>

    <hr class="preview-hr">

    {{-- ================== SECTION 6: Attachments ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">ไฟล์แนบ</h6>
    </div>
    <div class="row g-2 mb-1">
        @php
            $attachmentFields = [
                '1'  => ['field' => 'employee_doc_1',  'label' => '1. พาสปอร์ต'],
                '2'  => ['field' => 'employee_doc_2',  'label' => '2. วีซ่า'],
                '3'  => ['field' => 'employee_doc_3',  'label' => '3. Work Permit'],
                '4'  => ['field' => 'employee_doc_4',  'label' => '4. บัตรชมพู'],
                '5'  => ['field' => 'employee_doc_5',  'label' => '5. ทร. 38'],
                '6'  => ['field' => 'employee_doc_6',  'label' => '6. รายงานตัว 90 วัน'],
                '7'  => ['field' => 'employee_doc_7',  'label' => '7. ใบแจ้งที่พักอาศัย'],
                '8'  => ['field' => 'employee_doc_8',  'label' => '8. เอกสารบ้านเกิด'],
                '9'  => ['field' => 'employee_doc_9',  'label' => '9. เอกสารอื่นๆ 1',  'desc_field' => 'other_doc_1_desc'],
                '10' => ['field' => 'employee_doc_10', 'label' => '10. เอกสารอื่นๆ 2', 'desc_field' => 'other_doc_2_desc'],
                '11' => ['field' => 'employee_doc_11', 'label' => '11. เอกสารอื่นๆ 3', 'desc_field' => 'other_doc_3_desc'],
                '12' => ['field' => 'employee_doc_12', 'label' => '12. เอกสารอื่นๆ 4', 'desc_field' => 'other_doc_4_desc'],
                '13' => ['field' => 'employee_doc_13', 'label' => '13. เอกสารอื่นๆ 5', 'desc_field' => 'other_doc_5_desc'],
                '14' => ['field' => 'employee_doc_14', 'label' => '14. เอกสารอื่นๆ 6', 'desc_field' => 'other_doc_6_desc'],
                '15' => ['field' => 'employee_doc_15', 'label' => '15. เอกสารอื่นๆ 7', 'desc_field' => 'other_doc_7_desc'],
                '16' => ['field' => 'employee_doc_16', 'label' => '16. เอกสารอื่นๆ 8', 'desc_field' => 'other_doc_8_desc'],
                '17' => ['field' => 'employee_doc_17', 'label' => '17. เอกสารอื่นๆ 9', 'desc_field' => 'other_doc_9_desc'],
                '18' => ['field' => 'employee_doc_18', 'label' => '18. เอกสารอื่นๆ 10', 'desc_field' => 'other_doc_10_desc'],
            ];
        @endphp

        @foreach($attachmentFields as $key => $file)
            @php
                $field = $file['field'];
                $desc_field = $file['desc_field'] ?? null;
                $description = ($desc_field && $employee->{$desc_field}) ? $employee->{$desc_field} : null;
                $label = $description ? $file['label'] . ' - ' . $description : $file['label'];
                $url = $employee->{$field} ? Storage::disk('public')->url($employee->{$field}) : '#';
                $pdfUrl = $employee->{$field} ? route('employees.documents.pdf', ['employee' => $employee->id, 'field' => $field]) : '#';
            @endphp
            <div class="col-6 col-sm-4 col-md-3 preview-field">
                <span class="preview-label" title="{{ $label }}">{{ $label }}</span>
                @if($employee->{$field})
                    <div>
                        <a href="#" onclick="event.preventDefault(); viewPDF('{{ $url }}', 'ดูเอกสาร')" class="btn btn-success preview-attach-btn text-white">
                            <i class="bi bi-eye-fill"></i> ดู
                        </a>
                        <a href="{{ $pdfUrl }}" download class="btn btn-danger preview-attach-btn text-white ms-1">
                            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                        </a>
                    </div>
                @else
                    <span class="preview-value text-muted-empty">ไม่มีเอกสาร</span>
                @endif
            </div>
        @endforeach
    </div>
</div>
