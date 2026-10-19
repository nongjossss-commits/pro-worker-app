{{--
    This view is loaded via AJAX into the universal preview modal.

    All data on one tab (personal, contact, passport, visa, work permit,
    employment, insurance), attached files on their own tab. Shared look
    (flowing fields, highlighted key numbers, copy buttons, file chips,
    bigger window) lives in previews/_preview_compact.
--}}
@include('previews._preview_compact')

@php
    // Helper: render a "N/A" fallback with softer styling so real data
    // pops visually against unfilled fields. Callers use it via short
    // inline @if or the small closure below.
    // Filled values get a copy button that copies exactly what is shown
    // (one click instead of selecting the text — see previews/_preview_compact).
    $copyLabel = e(__('Copy'));
    $val = function ($v) use ($copyLabel) {
        if ($v === null || $v === '' || $v === 'N/A') {
            return '<span class="preview-value text-muted-empty">-</span>';
        }
        $text = e($v);
        return '<span class="preview-value">' . $text
            . '<button type="button" class="pv-copy" data-copy="' . $text . '" title="' . $copyLabel . '" aria-label="' . $copyLabel . '"><i class="bi bi-copy"></i></button></span>';
    };

    // Passport type resolves per-nationality
    $passportTypeVal = $employee->employeeNationality === 'กัมพูชา'
        ? ($employee->passport_type_cambodia ?? null)
        : ($employee->passportType ?? null);

    $mouGroupVal = $employee->workPermitMOUGroup;
    if ($mouGroupVal === 'อื่นๆ' && $employee->workPermitMOUGroupOther) {
        $mouGroupVal .= ' (' . $employee->workPermitMOUGroupOther . ')';
    }

    $attachedCount = collect(range(1, 18))->filter(fn ($i) => !empty($employee->{'employee_doc_' . $i}))->count();
@endphp

<div class="emp-preview content-section rec-fixed">

    @include('employees.partials._profile_header', ['employee' => $employee, 'photoLink' => true, 'details' => true, 'editUrl' => auth()->user()?->can('edit-employees') ? route('employees.edit', $employee->id) : null])

    {{-- Tabs (see partials/_record_tabs): all data on one tab, files on the other. --}}
    <div class="rec-tabs" data-rec-tabs>
    <div class="rec-tabs-nav" role="tablist">
        <button type="button" class="rec-tab active" data-rec-tab="personal" role="tab"><i class="bi bi-person-badge"></i><span>{{ __('Employee Data') }}</span></button>
        <button type="button" class="rec-tab" data-rec-tab="files" role="tab"><i class="bi bi-paperclip"></i><span>{{ __('Attached Files') }}</span>@if($attachedCount)<span class="rec-tab-count">{{ $attachedCount }}</span>@endif</button>
    </div>

        {{-- ================== TAB: all data on one page (files have their own tab) ================== --}}
    <div class="rec-tab-pane active" data-rec-pane="personal">
    {{-- Personal details (names, employer, date of birth, age, gender, nationality…) are in the profile header above, each with a copy button. --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Contact & Login') }}</h6>
    </div>
    <div class="pv-grid">
        <div class="preview-field">
            <span class="preview-label">{{ __('Phone (Short)') }}</span>
            {!! $val($employee->employeePhone ?? null) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Email') }}</span>
            {!! $val($employee->email ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Email Code') }}</span>
            {!! $val($employee->password ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Outsource Code (Preview)') }}</span>
            {!! $val($employee->outsource_code ?? null) !!}
        </div>
    </div>

    <hr class="preview-hr">
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Passport') }}</h6>
    </div>
    <div class="pv-grid">
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('Passport No. (Preview)') }}</span>
            {!! $val($employee->employeePassport ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Passport Issue Place') }}</span>
            {!! $val($employee->passport_issue_place ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Passport Issued On') }}</span>
            {!! $val($employee->passport_issue_date ? $employee->passport_issue_date->format('d/m/Y') : null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Passport Type (Preview)') }}</span>
            {!! $val($passportTypeVal) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('Passport Expiry (Preview)') }}</span>
            {!! $val($employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : null) !!}
        </div>
    </div>
    <hr class="preview-hr">
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Visa') }}</h6>
    </div>
    <div class="pv-grid">
        <div class="preview-field">
            <span class="preview-label">{{ __('Visa Type') }}</span>
            {!! $val($employee->visaType ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Visa Issue Place') }}</span>
            {!! $val($employee->visa_issue_place ?? null) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('Visa Endorsement Date') }}</span>
            {!! $val($employee->visaEndorsementDate ? $employee->visaEndorsementDate->format('d/m/Y') : null) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('Visa Endorsement Number') }}</span>
            {!! $val($employee->visaEndorsementNo ?? null) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('Visa Expiry Date') }}</span>
            {!! $val($employee->visaExpiryDate ? $employee->visaExpiryDate->format('d/m/Y') : null) !!}
        </div>
    </div>
    
    <hr class="preview-hr">

    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Work Permit') }}</h6>
    </div>
    <div class="pv-grid">
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Work Permit Type (Resolution Group)') }}</span>
            {!! $val($mouGroupVal) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">Work Permit No.</span>
            {!! $val($employee->employeeWorkPermit ?? null) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('ID Number') }}</span>
            {!! $val($employee->employee_id_number ?? null) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('Pink Card ID') }}</span>
            {!! $val($employee->pinkCardNo ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Work Permit Issue Date') }}</span>
            {!! $val($employee->workPermitIssueDate ? $employee->workPermitIssueDate->format('d/m/Y') : null) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('Work Permit Expiry Date') }}</span>
            {!! $val($employee->workPermitExpiryDate ? $employee->workPermitExpiryDate->format('d/m/Y') : null) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('90-Day Report') }}</span>
            {!! $val($employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('RA Number (Outsource)') }}</span>
            {!! $val($employee->name_list_number ?? null) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Position') }}</span>
            {!! $val($employee->job_title ?? null) !!}
        </div>
        <div class="preview-field pv-full">
            <span class="preview-label">{{ __('Job Description (Nature of Work)') }}</span>
            {!! $val($employee->job_description ?? null) !!}
        </div>
    </div>

    <hr class="preview-hr">
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Employment') }}</h6>
    </div>
    <div class="pv-grid">
        <div class="preview-field">
            <span class="preview-label">{{ __('Work Start Date') }}</span>
            {!! $val($employee->startDate ? $employee->startDate->format('d/m/Y') : null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Work Age') }}</span>
            {!! $val($employee->work_age) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Request Number') }}</span>
            {!! $val($employee->request_number ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Worker Reference Number') }}</span>
            {!! $val($employee->employee_reference_id ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Worker Code (Company)') }}</span>
            {!! $val($employee->employer_employee_id ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Department') }}</span>
            {!! $val($employee->department ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Tax ID (Short)') }}</span>
            {!! $val($employee->tax_id_number ?? null) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Bank Name') }}</span>
            {!! $val($employee->bank_name ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Bank Account No. (Preview)') }}</span>
            {!! $val($employee->bank_account_number ?? null) !!}
        </div>
    </div>
    
    <hr class="preview-hr">

    {{-- ================== SECTION 5: Insurance ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Insurance') }}</h6>
    </div>
    <div class="pv-grid">
        <div class="preview-field">
            <span class="preview-label">{{ __('Insurance Type') }}</span>
            {!! $val($employee->insurance_type ?? null) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Exam Hospital') }}</span>
            {!! $val($employee->medical_hospital_name ?? null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __("Doctor's Certificate") }}</span>
            @if($employee->medical_certificate_path)
                <div>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ Storage::disk('public')->url($employee->medical_certificate_path) }}', '{{ __('View Medical Certificate') }}')" class="btn btn-success preview-attach-btn text-white">
                        <i class="bi bi-eye-fill"></i> {{ __('View') }}
                    </a>
                    <a href="{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'medical_certificate_path']) }}" download class="btn btn-danger preview-attach-btn text-white ms-1">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </a>
                </div>
            @else
                <span class="preview-value text-muted-empty">-</span>
            @endif
        </div>

        @if($employee->insurance_type === 'ประกันสังคม')
            <div class="preview-field">
                <span class="preview-label">{{ __('Social Security Number') }}</span>
                {!! $val($employee->social_security_number ?? null) !!}
            </div>
            <div class="preview-field">
                <span class="preview-label">{{ __('Card Issued On') }}</span>
                {!! $val($employee->sso_issue_date ? $employee->sso_issue_date->format('d/m/Y') : null) !!}
            </div>
            <div class="preview-field">
                <span class="preview-label">{{ __('Expiry') }}</span>
                {!! $val($employee->sso_expiry_date ? $employee->sso_expiry_date->format('d/m/Y') : null) !!}
            </div>
            <div class="preview-field pv-l">
                <span class="preview-label">{{ __('Entitled Hospital') }}</span>
                {!! $val($employee->insurance_detail ?? null) !!}
            </div>
        @elseif($employee->insurance_type === 'ประกันโรงพยาบาล')
            <div class="preview-field pv-l">
                <span class="preview-label">{{ __('Insurance Hospital Name') }}</span>
                {!! $val($employee->insurance_detail_hospital ?? null) !!}
            </div>
            <div class="preview-field">
                <span class="preview-label">{{ __('Expiry') }}</span>
                {!! $val($employee->insurance_expiry_date_hospital ? $employee->insurance_expiry_date_hospital->format('d/m/Y') : null) !!}
            </div>
        @elseif($employee->insurance_type === 'ประกันเอกชน')
            <div class="preview-field pv-l">
                <span class="preview-label">{{ __('Insurance Company') }}</span>
                {!! $val($employee->insurance_detail_private ?? null) !!}
            </div>
            <div class="preview-field">
                <span class="preview-label">{{ __('Expiry') }}</span>
                {!! $val($employee->insurance_expiry_date_private ? $employee->insurance_expiry_date_private->format('d/m/Y') : null) !!}
            </div>
        @endif

        <div class="preview-field">
            <span class="preview-label">{{ __('Insurance Attachment') }}</span>
            @if($employee->insurance_document_path_private)
                <div>
                    <a href="#" onclick="event.preventDefault(); viewPDF('{{ Storage::disk('public')->url($employee->insurance_document_path_private) }}', '{{ __('View Insurance') }}')" class="btn btn-success preview-attach-btn text-white">
                        <i class="bi bi-eye-fill"></i> {{ __('View') }}
                    </a>
                    <a href="{{ route('employees.documents.pdf', ['employee' => $employee->id, 'field' => 'insurance_document_path_private']) }}" download class="btn btn-danger preview-attach-btn text-white ms-1">
                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                    </a>
                </div>
            @else
                <span class="preview-value text-muted-empty">-</span>
            @endif
        </div>
    </div>

    </div>{{-- /pane data --}}

    {{-- ================== TAB: Attachments ================== --}}
    <div class="rec-tab-pane" data-rec-pane="files">
    {{-- ================== SECTION 6: Attachments ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Attached Files') }}</h6>
    </div>
    <div class="pv-files">
        @php
            $attachmentFields = [
                '1'  => ['field' => 'employee_doc_1',  'label' => '1. ' . __('Passport (Doc)')],
                '2'  => ['field' => 'employee_doc_2',  'label' => '2. ' . __('Visa')],
                '3'  => ['field' => 'employee_doc_3',  'label' => '3. Work Permit'],
                '4'  => ['field' => 'employee_doc_4',  'label' => '4. ' . __('Pink Card')],
                '5'  => ['field' => 'employee_doc_5',  'label' => '5. ' . __('House Registration (Tor.Ror.38)')],
                '6'  => ['field' => 'employee_doc_6',  'label' => '6. ' . __('90-Day Report')],
                '7'  => ['field' => 'employee_doc_7',  'label' => '7. ' . __('Accommodation Notification')],
                '8'  => ['field' => 'employee_doc_8',  'label' => '8. ' . __('Home Country Documents')],
                '9'  => ['field' => 'employee_doc_9',  'label' => '9. ' . \App\Support\OtherDocumentNames::employee(1),  'desc_field' => 'other_doc_1_desc'],
                '10' => ['field' => 'employee_doc_10', 'label' => '10. ' . \App\Support\OtherDocumentNames::employee(2), 'desc_field' => 'other_doc_2_desc'],
                '11' => ['field' => 'employee_doc_11', 'label' => '11. ' . \App\Support\OtherDocumentNames::employee(3), 'desc_field' => 'other_doc_3_desc'],
                '12' => ['field' => 'employee_doc_12', 'label' => '12. ' . \App\Support\OtherDocumentNames::employee(4), 'desc_field' => 'other_doc_4_desc'],
                '13' => ['field' => 'employee_doc_13', 'label' => '13. ' . \App\Support\OtherDocumentNames::employee(5), 'desc_field' => 'other_doc_5_desc'],
                '14' => ['field' => 'employee_doc_14', 'label' => '14. ' . \App\Support\OtherDocumentNames::employee(6), 'desc_field' => 'other_doc_6_desc'],
                '15' => ['field' => 'employee_doc_15', 'label' => '15. ' . \App\Support\OtherDocumentNames::employee(7), 'desc_field' => 'other_doc_7_desc'],
                '16' => ['field' => 'employee_doc_16', 'label' => '16. ' . \App\Support\OtherDocumentNames::employee(8), 'desc_field' => 'other_doc_8_desc'],
                '17' => ['field' => 'employee_doc_17', 'label' => '17. ' . \App\Support\OtherDocumentNames::employee(9), 'desc_field' => 'other_doc_9_desc'],
                '18' => ['field' => 'employee_doc_18', 'label' => '18. ' . \App\Support\OtherDocumentNames::employee(10), 'desc_field' => 'other_doc_10_desc'],
            ];
        @endphp

        @foreach($attachmentFields as $key => $file)
            @php
                $field = $file['field'];
                $desc_field = $file['desc_field'] ?? null;
                $description = ($desc_field && $employee->{$desc_field}) ? $employee->{$desc_field} : null;
                // A renamed "Other Document" slot shows its own name instead of the default one.
                $label = $description ? $key . '. ' . $description : $file['label'];
                $url = $employee->{$field} ? Storage::disk('public')->url($employee->{$field}) : '#';
                $pdfUrl = $employee->{$field} ? route('employees.documents.pdf', ['employee' => $employee->id, 'field' => $field]) : '#';
            @endphp
            {{-- One compact chip per slot: attached ones are solid with View / PDF,
                 empty ones are faded so the attached files stand out. --}}
            <div class="pv-file {{ $employee->{$field} ? 'has-file' : 'is-empty' }}">
                <i class="bi {{ $employee->{$field} ? 'bi-file-earmark-check-fill' : 'bi-file-earmark' }} pv-file-icon"></i>
                <span class="pv-file-name" title="{{ $label }}">{{ $label }}</span>
                @if($employee->{$field})
                    <span class="pv-file-actions">
                        <a href="#" onclick="event.preventDefault(); viewPDF('{{ $url }}', '{{ __('View Document') }}')" class="btn btn-success preview-attach-btn text-white" title="{{ __('View') }}">
                            <i class="bi bi-eye-fill"></i> {{ __('View') }}
                        </a>
                        <a href="{{ $pdfUrl }}" download class="btn btn-danger preview-attach-btn text-white" title="PDF">
                            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                        </a>
                    </span>
                @else
                    <span class="pv-file-empty">{{ __('No Document') }}</span>
                @endif
            </div>
        @endforeach
    </div>
    </div>{{-- /pane files --}}
    </div>{{-- /rec-tabs --}}
</div>
