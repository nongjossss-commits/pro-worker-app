{{--
    This view is intentionally simplified to display data.
    It does not include @extends, @section, or any form elements.
    It is loaded via AJAX into a modal.
--}}
<div class="content-section">
    {{-- Personal Information --}}
    <h5>{{ __('ข้อมูลส่วนตัว') }}</h5>
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
                    <label class="form-label fw-bold">{{ __('ชื่อพนักงาน (ไทย)') }}</label>
                    <p class="form-control-plaintext">{{ trim(($employee->employeeTitleTh ?? '') . ' ' . ($employee->employeeNameTh ?? '')) ?: 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('ชื่อพนักงาน (อังกฤษ)') }}</label>
                    <p class="form-control-plaintext">{{ trim(($employee->employeeTitleEn ?? '') . ' ' . ($employee->employeeNameEn ?? '')) ?: 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">{{ __('Height') }}</label>
                    <p class="form-control-plaintext">{{ $employee->height ? $employee->height . ' cm' : 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">{{ __('Weight') }}</label>
                    <p class="form-control-plaintext">{{ $employee->weight ? $employee->weight . ' kg' : 'N/A' }}</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('วันเดือนปีเกิด') }}</label>
                    <p class="form-control-plaintext">{{ $employee->employeeDob ? $employee->employeeDob->format('d/m/Y') : 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('อายุ') }}</label>
                    <p class="form-control-plaintext">{{ $employee->age ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('เพศ') }}</label>
                    <p class="form-control-plaintext">{{ $employee->gender ?? 'N/A' }}</p>
                </div>
                 <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('สัญชาติ') }}</label>
                    <p class="form-control-plaintext">{{ $employee->employeeNationality ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('ชื่อบิดา') }}</label>
                    <p class="form-control-plaintext">{{ $employee->father_name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">{{ __('ชื่อมารดา') }}</label>
                    <p class="form-control-plaintext">{{ $employee->mother_name ?? 'N/A' }}</p>
                </div>
            </div>
             <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">{{ __('เลขหนังสือเดินทาง') }}</label>
                    <p class="form-control-plaintext">{{ $employee->employeePassport ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">{{ __('Passport Issue Place') }}</label>
                    <p class="form-control-plaintext">{{ $employee->passport_issue_place ?? 'N/A' }}</p>
                </div>
                 <div class="col-md-3">
                    <label class="form-label fw-bold">{{ __('ประเภทหนังสือเดินทาง') }}</label>
                     <p class="form-control-plaintext">
                        @if($employee->employeeNationality === 'กัมพูชา')
                            {{ $employee->passport_type_cambodia ?? 'N/A' }}
                        @else
                            {{ $employee->passportType ?? 'N/A' }}
                        @endif
                    </p>
                </div>
                 <div class="col-md-3">
                    <label class="form-label fw-bold">{{ __('วันหมดอายุหนังสือเดินทาง') }}</label>
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
    <h5>{{ __('ข้อมูลใบอนุญาตทำงานและวีซ่า') }}</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('ใบอนุญาตทำงาน (Work Permit No.)') }}</label><p class="form-control-plaintext">{{ $employee->employeeWorkPermit ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('วันหมดอายุใบอนุญาตทำงาน') }}</label><p class="form-control-plaintext">{{ $employee->workPermitExpiryDate ? $employee->workPermitExpiryDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4">
            <label class="form-label fw-bold">{{ __('ประเภทใบอนุญาตทำงาน (กลุ่ม มติ.)') }}</label>
            <p class="form-control-plaintext">
                {{ $employee->workPermitMOUGroup ?? 'N/A' }}
                @if($employee->workPermitMOUGroup === 'อื่นๆ')
                    ({{ $employee->workPermitMOUGroupOther ?? 'ไม่ระบุ' }})
                @endif
            </p>
        </div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('ประเภทวีซ่า') }}</label><p class="form-control-plaintext">{{ $employee->visaType ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('Visa Issue Place') }}</label><p class="form-control-plaintext">{{ $employee->visa_issue_place ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('วันหมดอายุวีซ่า') }}</label><p class="form-control-plaintext">{{ $employee->visaExpiryDate ? $employee->visaExpiryDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('วันหมดอายุรายงานตัว 90 วัน') }}</label><p class="form-control-plaintext">{{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : 'N/A' }}</p></div>
    </div>

    <hr class="my-4">

    {{-- Job & Contact Info --}}
    <h5>{{ __('ข้อมูลการจ้างงานและข้อมูลติดต่อ') }}</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('ตำแหน่ง') }}</label><p class="form-control-plaintext">{{ $employee->job_title ?? 'N/A' }}</p></div>
        <div class="col-md-8"><label class="form-label fw-bold">{{ __('ลักษณะงาน (Nature of Work)') }}</label><p class="form-control-plaintext">{{ $employee->job_description ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('วันเริ่มงาน') }}</label><p class="form-control-plaintext">{{ $employee->startDate ? $employee->startDate->format('d/m/Y') : 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('Work Age') }}</label><p class="form-control-plaintext">{{ $employee->work_age }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('เบอร์โทรศัพท์') }}</label><p class="form-control-plaintext">{{ $employee->employeePhone ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('อีเมล') }}</label><p class="form-control-plaintext">{{ $employee->email ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('รหัสผ่านสำหรับอีเมล') }}</label><p class="form-control-plaintext">{{ $employee->password ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('รหัสผ่าน ระบบ outsource') }}</label><p class="form-control-plaintext">{{ $employee->outsource_code ?? 'N/A' }}</p></div>
    </div>

    <hr class="my-4">

    {{-- Official Document Numbers --}}
    <h5>{{ __('ข้อมูลเอกสารราชการ') }}</h5>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('เลข RA จากระบบ outsource') }}</label><p class="form-control-plaintext">{{ $employee->name_list_number ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('เลขที่คำขอ') }}</label><p class="form-control-plaintext">{{ $employee->request_number ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('เลขอ้างอิงคนงาน') }}</label><p class="form-control-plaintext">{{ $employee->employee_reference_id ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('เลขประจำตัว') }}</label><p class="form-control-plaintext">{{ $employee->employee_id_number ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('รหัสคนงาน (บริษัท)') }}</label><p class="form-control-plaintext">{{ $employee->employer_employee_id ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('Department') }}</label><p class="form-control-plaintext">{{ $employee->department ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('เลขบัตรชมพู') }}</label><p class="form-control-plaintext">{{ $employee->pinkCardNo ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('เลขประจำตัวผู้เสียภาษี') }}</label><p class="form-control-plaintext">{{ $employee->tax_id_number ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('ชื่อธนาคาร') }}</label><p class="form-control-plaintext">{{ $employee->bank_name ?? 'N/A' }}</p></div>
        <div class="col-md-4"><label class="form-label fw-bold">{{ __('เลขบัญชีธนาคาร') }}</label><p class="form-control-plaintext">{{ $employee->bank_account_number ?? 'N/A' }}</p></div>
    </div>

    <hr class="my-4">

    {{-- Insurance Information --}}
    <h5>{{ __('ข้อมูลประกัน') }}</h5>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-bold">{{ __('ประเภทประกัน') }}</label>
            <p class="form-control-plaintext">{{ $employee->insurance_type ?? 'N/A' }}</p>
        </div>

        <div class="col-md-4">
            <label class="form-label fw-bold">{{ __('ใบรับรองแพทย์') }}</label>
            @if($employee->medical_certificate_path)
                <p class="form-control-plaintext">
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ Storage::disk('public')->url($employee->medical_certificate_path) }}', 'ดูใบรับรองแพทย์')" class="btn btn-success btn-sm text-white">
                        <i class="bi bi-eye-fill"></i>{{ __('ดูเอกสาร') }}</a>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'medical_certificate_path']) }}', 'ใบรับรองแพทย์')" class="btn btn-danger btn-sm text-white ms-1">
                        <i class="bi bi-file-earmark-pdf-fill"></i>{{ __('PDF') }}</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">{{ __('ไม่มีเอกสาร') }}</p>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">{{ __('โรงพยาบาลที่ตรวจโรค') }}</label>
            <p class="form-control-plaintext">{{ $employee->medical_hospital_name ?? '-' }}</p>
        </div>

        @if($employee->insurance_type === 'ประกันสังคม')
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('เลขประกันสังคม') }}</label>
                <p class="form-control-plaintext">{{ $employee->social_security_number ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('วันที่ออกบัตร') }}</label>
                <p class="form-control-plaintext">{{ $employee->sso_issue_date ? $employee->sso_issue_date->format('d/m/Y') : 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('วันหมดอายุ') }}</label>
                <p class="form-control-plaintext">{{ $employee->sso_expiry_date ? $employee->sso_expiry_date->format('d/m/Y') : 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('โรงพยาบาลตามสิทธิ') }}</label>
                <p class="form-control-plaintext">{{ $employee->insurance_detail ?? 'N/A' }}</p>
            </div>
        @elseif($employee->insurance_type === 'ประกันโรงพยาบาล')
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('โรงพยาบาล') }}</label>
                <p class="form-control-plaintext">{{ $employee->insurance_detail_hospital ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('วันหมดอายุ') }}</label>
                <p class="form-control-plaintext">{{ $employee->insurance_expiry_date_hospital ? $employee->insurance_expiry_date_hospital->format('d/m/Y') : 'N/A' }}</p>
            </div>
        @elseif($employee->insurance_type === 'ประกันเอกชน')
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('บริษัทประกัน') }}</label>
                <p class="form-control-plaintext">{{ $employee->insurance_detail_private ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ __('วันหมดอายุ') }}</label>
                <p class="form-control-plaintext">{{ $employee->insurance_expiry_date_private ? $employee->insurance_expiry_date_private->format('d/m/Y') : 'N/A' }}</p>
            </div>
        @else
            {{-- Fallback for cases with no clear type or data --}}
            <div class="col-md-8">
                <label class="form-label fw-bold">{{ __('รายละเอียด') }}</label>
                <p class="form-control-plaintext">{{ __('ไม่มีข้อมูล') }}</p>
            </div>
        @endif

        {{-- Insurance Attachment --}}
        <div class="col-md-4">
            <label class="form-label fw-bold">{{ __('ไฟล์แนบประกัน') }}</label>
            @if($employee->insurance_document_path_private)
                <p class="form-control-plaintext">
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ Storage::disk('public')->url($employee->insurance_document_path_private) }}', 'ดูประกัน')" class="btn btn-success btn-sm text-white">
                        <i class="bi bi-eye-fill"></i>{{ __('ดูเอกสาร') }}</a>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'insurance_document_path_private']) }}', 'ประกัน')" class="btn btn-danger btn-sm text-white ms-1">
                        <i class="bi bi-file-earmark-pdf-fill"></i>{{ __('PDF') }}</a>
                </p>
            @else
                <p class="form-control-plaintext text-muted">{{ __('ไม่มีเอกสาร') }}</p>
            @endif
        </div>
    </div>

    <hr class="my-4">

    {{-- Other Attachments --}}
    <h5>{{ __('ไฟล์แนบ') }}</h5>
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
                $desc_field = $file['desc_field'] ?? null;
                $description = ($desc_field && $employee->{$desc_field}) ? $employee->{$desc_field} : null;
                $label = $description ? $file['label'] . ' - ' . $description : $file['label'];

                $url = $employee->{$field} ? Storage::disk('public')->url($employee->{$field}) : '#';
                $pdfUrl = $employee->{$field} ? route('employees.documents.pdf', ['employee' => $employee->id, 'field' => $field]) : '#';
            @endphp
            <div class="col-md-4">
                <label class="form-label fw-bold">{{ $label }}</label>
                @if($employee->{$field})
                    <p class="form-control-plaintext">
                        <a href="#" onclick="event.preventDefault(); viewPDF('{{ $url }}', 'ดูเอกสาร')" class="btn btn-success btn-sm text-white">
                            <i class="bi bi-eye-fill"></i>{{ __('ดูเอกสาร') }}</a>
                        <a href="{{ $pdfUrl }}" download class="btn btn-danger btn-sm text-white ms-1">
                            <i class="bi bi-file-earmark-pdf-fill"></i>{{ __('PDF') }}</a>
                    </p>
                @else
                    <p class="form-control-plaintext text-muted">{{ __('ไม่มีเอกสาร') }}</p>
                @endif
            </div>
        @endforeach
    </div>
</div>
