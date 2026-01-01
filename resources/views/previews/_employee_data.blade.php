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
                    <p class="form-control-plaintext">{{ $employee->age ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">เพศ</label>
                    <p class="form-control-plaintext">{{ $employee->gender ?? 'N/A' }}</p>
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
            <a href="{{ $employee->photo_url }}" target="_blank" rel="noopener noreferrer">
                <img src="{{ $employee->photo_url }}" class="img-thumbnail mb-2" style="width: 250px; height: 250px; object-fit: contain;" alt="Employee Photo">
            </a>
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
        <div class="col-md-4"><label class="form-label fw-bold">เลข RA จากระบบ outsource</label><p class="form-control-plaintext">{{ $employee->name_list_number ?? 'N/A' }}</p></div>
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
        <div class="col-md-4">
            <label class="form-label fw-bold">ประเภทประกัน</label>
            <p class="form-control-plaintext">{{ $employee->insurance_type ?? 'N/A' }}</p>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold">ใบรับรองแพทย์</label>
            @if($employee->medical_certificate_path)
                <p class="form-control-plaintext">
                    <a href="{{ Storage::disk('public')->url($employee->medical_certificate_path) }}" target="_blank" class="btn btn-success btn-sm text-white">
                        <i class="bi bi-eye-fill"></i> ดูเอกสาร
                    </a>
                    <a href="{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'medical_certificate_path']) }}" target="_blank" class="btn btn-danger btn-sm text-white ms-1">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">โรงพยาบาลที่ตรวจโรค</label>
            <p class="form-control-plaintext">{{ $employee->medical_hospital_name ?? '-' }}</p>
        </div>

        @if($employee->insurance_type === 'ประกันสังคม')
            <div class="col-md-4">
                <label class="form-label fw-bold">เลขประกันสังคม</label>
                <p class="form-control-plaintext">{{ $employee->social_security_number ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">โรงพยาบาลตามสิทธิ</label>
                <p class="form-control-plaintext">{{ $employee->insurance_detail ?? 'N/A' }}</p>
            </div>
        @elseif($employee->insurance_type === 'ประกันโรงพยาบาล')
            <div class="col-md-4">
                <label class="form-label fw-bold">โรงพยาบาล</label>
                <p class="form-control-plaintext">{{ $employee->insurance_detail_hospital ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">วันหมดอายุ</label>
                <p class="form-control-plaintext">{{ $employee->insurance_expiry_date_hospital ? $employee->insurance_expiry_date_hospital->format('d/m/Y') : 'N/A' }}</p>
            </div>
        @elseif($employee->insurance_type === 'ประกันเอกชน')
            <div class="col-md-4">
                <label class="form-label fw-bold">บริษัทประกัน</label>
                <p class="form-control-plaintext">{{ $employee->insurance_detail_private ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">วันหมดอายุ</label>
                <p class="form-control-plaintext">{{ $employee->insurance_expiry_date_private ? $employee->insurance_expiry_date_private->format('d/m/Y') : 'N/A' }}</p>
            </div>
        @else
            {{-- Fallback for cases with no clear type or data --}}
            <div class="col-md-8">
                <label class="form-label fw-bold">รายละเอียด</label>
                <p class="form-control-plaintext">ไม่มีข้อมูล</p>
            </div>
        @endif

        {{-- Insurance Attachment --}}
        <div class="col-md-4">
            <label class="form-label fw-bold">ไฟล์แนบประกัน</label>
            @if($employee->insurance_document_path_private)
                <p class="form-control-plaintext">
                    <a href="{{ Storage::disk('public')->url($employee->insurance_document_path_private) }}" target="_blank" class="btn btn-success btn-sm text-white">
                        <i class="bi bi-eye-fill"></i> ดูเอกสาร
                    </a>
                    <a href="{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'insurance_document_path_private']) }}" target="_blank" class="btn btn-danger btn-sm text-white ms-1">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
            @endif
        </div>
    </div>

    <hr class="my-4">

    {{-- Other Attachments --}}
    <h5>ไฟล์แนบ</h5>
    <div class="row g-3">
        @php
            // Remap keys to match the form labels from the screenshot
            $attachmentFields = [
                '1' => ['field' => 'employee_doc_1', 'label' => '1. พาสปอร์ต'],
                '2' => ['field' => 'employee_doc_2', 'label' => '2. วีซ่า'],
                '3' => ['field' => 'employee_doc_3', 'label' => '3. ใบอนุญาต Work Permit'],
                '4' => ['field' => 'employee_doc_4', 'label' => '4. บัตรชมพู'],
                '5' => ['field' => 'employee_doc_5', 'label' => '5. ทร. 38'],
                '6' => ['field' => 'employee_doc_6', 'label' => '6. รายงานตัว 90 วัน'],
                '7' => ['field' => 'employee_doc_7', 'label' => '7. ใบแจ้งที่พักอาศัย'],
                '8' => ['field' => 'employee_doc_8', 'label' => '8. เอกสารบ้านเกิด'],
                '9' => ['field' => 'employee_doc_9', 'label' => '9. เอกสารอื่นๆ 1', 'desc_field' => 'other_doc_1_desc'],
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
                $label = $file['label'];
                $desc_field = $file['desc_field'] ?? null;
                $url = $employee->{$field} ? Storage::disk('public')->url($employee->{$field}) : '#';
                $pdfUrl = $employee->{$field} ? route('employees.documents.pdf', ['employee' => $employee->id, 'field' => $field]) : '#';
            @endphp
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ $label }}</label>
                @if($employee->{$field})
                    <p class="form-control-plaintext">
                        <a href="{{ $url }}" target="_blank" class="btn btn-success btn-sm text-white">
                            <i class="bi bi-eye-fill"></i> ดูเอกสาร
                        </a>
                        <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-danger btn-sm text-white ms-1">
                            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                        </a>
                         @if($desc_field && $employee->{$desc_field})
                            <span class="text-muted d-block" style="font-size: 0.875em;">({{ $employee->{$desc_field} }})</span>
                        @endif
                    </p>
                @elseif($desc_field && $employee->{$desc_field})
                    <p class="form-control-plaintext text-muted" style="color: #6c757d !important;">{{ $employee->{$desc_field} }}</p>
                @else
                    <p class="form-control-plaintext text-muted">ไม่มีเอกสาร</p>
                @endif
            </div>
        @endforeach
    </div>
</div>
