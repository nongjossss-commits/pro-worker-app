{{--
    Employer preview — loaded via AJAX into the universal preview modal.

    Same compact layout as the employee preview (previews/_preview_compact):
    all data on one tab with flowing fields, key numbers highlighted, a copy
    button after every filled value, documents as chips on their own tab.
--}}
@include('previews._preview_compact')
<style>
    .empr-preview .empr-address-card {
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        padding: 8px 10px;
        background: var(--bs-tertiary-bg);
        font-size: 0.88rem;
    }
    .empr-preview .empr-address-card .preview-value { display: inline; font-size: inherit; }
    /* registered + workplace addresses side by side on wide windows */
    .empr-preview .empr-addresses { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 24rem), 1fr)); gap: .55rem 1.1rem; }
    .empr-preview .empr-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(9rem, 1fr)); gap: .5rem; }
    .empr-preview .empr-stat-card {
        border-radius: 10px;
        padding: 8px 12px;
        background: var(--bs-tertiary-bg);
    }
    .empr-preview .empr-stat-card h6 {
        font-size: 0.78rem;
        color: var(--bs-secondary-color);
        margin-bottom: 2px;
    }
    .empr-preview .empr-stat-card p {
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0;
    }
</style>

@php
    // Shared "N/A → dash" renderer — same idea as the employee preview so
    // truly-missing fields look softer than filled ones.
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
    $empDocCount = collect(['employer_doc_company', 'employer_doc_lease', 'employer_doc_construction', 'employer_doc_other_1', 'employer_doc_other_2', 'employer_doc_other_3'])
        ->filter(fn ($f) => !empty($employer->{$f}))->count();
@endphp

<div class="empr-preview rec-fixed">

    @include('employers.partials._profile_header', ['employer' => $employer, 'stats' => $stats ?? null, 'editUrl' => auth()->user()?->can('edit-employers') ? route('employers.edit', $employer->id) : null])

    {{-- Tabs in a fixed frame: header + tab bar stay put, only the content scrolls (see partials/_record_tabs).
         All data on one tab; documents keep their own tab. --}}
    <div class="rec-tabs" data-rec-tabs>
    <div class="rec-tabs-nav" role="tablist">
        <button type="button" class="rec-tab active" data-rec-tab="info" role="tab"><i class="bi bi-building"></i><span>{{ __('Employer Data') }}</span></button>
        <button type="button" class="rec-tab" data-rec-tab="docs" role="tab"><i class="bi bi-paperclip"></i><span>{{ __('Employer Attached Documents') }}</span>@if($empDocCount)<span class="rec-tab-count">{{ $empDocCount }}</span>@endif</button>
    </div>

    <div class="rec-tab-pane active" data-rec-pane="info">
    {{-- ================== SECTION 1: Identity ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Employer Information') }}</h6>
    </div>
    <div class="pv-grid">
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Employer Name (Thai)') }}</span>
            {!! $val($employer->employerNameTh) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Employer Name (English)') }}</span>
            {!! $val($employer->employerNameEn) !!}
        </div>
        @if($employer->name_suffix)
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Name Suffix') }}</span>
            {!! $val($employer->name_suffix) !!}
        </div>
        @endif
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('Employer ID') }}</span>
            {!! $val($employer->employerId) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('Employer Tax ID') }}</span>
            {!! $val($employer->employerTaxId) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">{{ __('Expiry Date (Certificate)') }}</span>
            {!! $val($employer->employer_doc_company_expiry ? \Carbon\Carbon::parse($employer->employer_doc_company_expiry)->format('d/m/Y') : null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Job Owner') }}</span>
            {!! $val($employer->jobOwner?->name) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Caretaker (Admin)') }}</span>
            {!! $val($employer->assignedStaff?->name) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Business Type (Thai)') }}</span>
            {!! $val($employer->businessType) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Business Type (English)') }}</span>
            {!! $val($employer->businessTypeEn) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Registered Capital') }}</span>
            {!! $val($employer->regCapital ? number_format($employer->regCapital, 2) : null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Registration Date') }}</span>
            {!! $val($employer->regDate ? $employer->regDate->format('d/m/Y') : null) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Minimum Wage') }}</span>
            {!! $val($employer->minimum_wage ? number_format($employer->minimum_wage, 2) : null) !!}
        </div>
    </div>

    <hr class="preview-hr">
    {{-- ================== SECTION 2: Contact + Credentials ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Contact & External Systems') }}</h6>
    </div>
    <div class="pv-grid">
        <div class="preview-field">
            <span class="preview-label">{{ __('Phone (Short)') }}</span>
            {!! $val($employer->employerPhone) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Email') }}</span>
            {!! $val($employer->employerEmail) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Email Code') }}</span>
            {!! $val($employer->employerPassword) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('SSO Hospital') }}</span>
            {!! $val($employer->socialSecurityHospital) !!}
        </div>
        <div class="preview-field pv-key">
            <span class="preview-label">RE Code</span>
            {!! $val($employer->outsource_re_code) !!}
        </div>
        <div class="preview-field">
            <span class="preview-label">{{ __('Outsource Pass') }}</span>
            {!! $val($employer->outsource_password) !!}
        </div>
    </div>

    <hr class="preview-hr">
    {{-- ================== SECTION 3: Signers ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Authorized Signatories') }}</h6>
    </div>
    <div class="pv-grid">
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Signer 1 (Thai)') }}</span>
            {!! $val($employer->signerNameTh) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Signer 1 (English)') }}</span>
            {!! $val($employer->signerNameEn) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Signer 2 (Thai)') }}</span>
            {!! $val($employer->signerNameTh2) !!}
        </div>
        <div class="preview-field pv-l">
            <span class="preview-label">{{ __('Signer 2 (English)') }}</span>
            {!! $val($employer->signerNameEn2) !!}
        </div>
    </div>

    <hr class="preview-hr">
    {{-- ================== SECTION 4: Addresses ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Addresses') }}</h6>
    </div>
    <div class="empr-addresses">
        @foreach (['registered' => __('By Registration'), 'workplace' => __('Workplace')] as $addrType => $addrLabel)
        <div class="preview-field">
            <span class="preview-label">{{ $addrLabel }}</span>
            <div class="vstack gap-2 mt-1">
                @forelse($employer->addresses->where('type', $addrType) as $address)
                    <div class="empr-address-card">
                        <div><strong>TH:</strong> {!! $val($address->full_address_th) !!}</div>
                        <div class="text-muted"><strong>EN:</strong> {!! $val($address->full_address_en) !!}</div>
                    </div>
                @empty
                    <span class="preview-value text-muted-empty">{{ __('No Address Yet') }}</span>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

    <hr class="preview-hr">
    {{-- ================== SECTION 5: Employee Stats ================== --}}
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Employee Summary') }}</h6>
    </div>
    <div class="empr-stats">
        <div class="empr-stat-card">
            <h6>{{ __('Total Workers') }}</h6>
            <p>{{ $stats->total ?? 0 }}</p>
        </div>
        <div class="empr-stat-card">
            <h6>{{ __('Male (Gender Stat)') }}</h6>
            <p>{{ $stats->male ?? 0 }}</p>
        </div>
        <div class="empr-stat-card">
            <h6>{{ __('Female (Gender Stat)') }}</h6>
            <p>{{ $stats->female ?? 0 }}</p>
        </div>
        <div class="empr-stat-card">
            <h6>{{ __('Terminated (Notice Filed)') }}</h6>
            <p>{{ $stats->terminated_total ?? 0 }}</p>
        </div>
    </div>
    <div class="preview-field mt-2">
        <span class="preview-label">{{ __('Summary by Nationality (Active Employees Only)') }}</span>
        @if(isset($stats->breakdown) && count($stats->breakdown))
            <ul class="list-unstyled mb-0 mt-1 d-flex flex-wrap column-gap-4 row-gap-1" style="font-size: 0.9rem;">
                @foreach($stats->breakdown as $nationality => $data)
                    <li>
                        <strong>{{ $nationality ?: __('Nationality Not Specified') }}:</strong>
                        {{ $data->total_count }} {{ __('people') }}
                        <span class="text-muted">({{ __('Male (Short)') }}: {{ $data->male_count }} / {{ __('Female (Short)') }}: {{ $data->female_count }})</span>
                    </li>
                @endforeach
            </ul>
        @else
            <span class="preview-value text-muted-empty">{{ __('No Nationality Breakdown Data') }}</span>
        @endif
    </div>
    </div>{{-- /pane info --}}

    {{-- ================== TAB: Documents ================== --}}
    <div class="rec-tab-pane" data-rec-pane="docs">
    <div class="preview-section-header">
        <h6 class="preview-section-title">{{ __('Employer Attached Documents') }}</h6>
    </div>
    @php
        $otherDocLabels = [1 => __('Other Document 1'), 2 => __('Other Document 2'), 3 => __('Other Document 3')];
        $companyExpiry = $employer->employer_doc_company_expiry ? \Carbon\Carbon::parse($employer->employer_doc_company_expiry)->format('d/m/Y') : null;
        $employerDocs = [
            ['field' => 'employer_doc_company', 'label' => '1. ' . __('Company Cert. / ID Card'), 'view' => __('View Company Certificate'),
             'meta' => $companyExpiry ? __('Expiry Date (Certificate)') . ': ' . $companyExpiry : null],
            ['field' => 'employer_doc_lease', 'label' => '2. ' . __('Lease / House Registration'), 'view' => __('View Lease')],
            ['field' => 'employer_doc_construction', 'label' => '3. ' . __('Construction Contract / Map'), 'view' => __('View Construction Permit')],
        ];
        foreach ([1, 2, 3] as $i) {
            $desc = $employer->{"employer_doc_other_{$i}_desc"};
            $employerDocs[] = ['field' => "employer_doc_other_{$i}", 'label' => (3 + $i) . '. ' . $otherDocLabels[$i] . ($desc ? ' - ' . $desc : ''), 'view' => $desc ?? __('View Document')];
        }
    @endphp
    {{-- One compact chip per slot: attached ones are solid with View / PDF, empty ones are faded. --}}
    <div class="pv-files">
        @foreach ($employerDocs as $doc)
            @php $has = !empty($employer->{$doc['field']}); @endphp
            <div class="pv-file {{ $has ? 'has-file' : 'is-empty' }}">
                <i class="bi {{ $has ? 'bi-file-earmark-check-fill' : 'bi-file-earmark' }} pv-file-icon"></i>
                <span class="pv-file-body">
                    <span class="pv-file-name" title="{{ $doc['label'] }}">{{ $doc['label'] }}</span>
                    @if(!empty($doc['meta']))<span class="pv-file-meta d-block">{{ $doc['meta'] }}</span>@endif
                </span>
                @if($has)
                    <span class="pv-file-actions">
                        <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $employer->{$doc['field']}) }}', '{{ $doc['view'] }}')" class="btn btn-success preview-attach-btn text-white" title="{{ __('View') }}"><i class="bi bi-eye-fill"></i> {{ __('View') }}</a>
                        <a href="{{ route('employers.documents.pdf', ['employer' => $employer->id, 'field' => $doc['field']]) }}" download class="btn btn-danger preview-attach-btn text-white" title="PDF"><i class="bi bi-file-earmark-pdf-fill"></i> PDF</a>
                    </span>
                @else
                    <span class="pv-file-empty">{{ __('No Document') }}</span>
                @endif
            </div>
        @endforeach
    </div>
    </div>{{-- /pane docs --}}
    </div>{{-- /rec-tabs --}}
</div>
