@php
    // Helper to prevent errors if employer relationship is not loaded
    $employerName = $employee->employer->employerNameTh ?? 'N/A';

    // V2.5: Allow overriding the DOM ID and the Drag URL from parent views
    // This is crucial for views like Groups/Teams where the same employee appears multiple times
    // or where we need query parameters (active_tab) in the URL.
    $elementId = $elementId ?? 'employee-card-' . $employee->id;
    $dragUrl = $dragUrl ?? (request()->fullUrl() . '#' . $elementId);
@endphp
@php
    // V2.5: Check completeness for badge display (Only in Incomplete View)
    $missingCount = 0;
    if (isset($is_incomplete_view) && $is_incomplete_view) {
        $missingCount = count(\App\Helpers\CompletenessHelper::getMissingFields($employee, $mandatoryFields ?? null));
    }
@endphp

<div id="{{ $elementId }}"
     class="list-group-item list-group-item-action position-relative"
     draggable="true"
     data-drag-payload="{{ json_encode([
        'id' => $employee->id,
        'title' => $employee->employeeNameTh,
        'subtitle' => $employee->employeeNameEn,
        'photo_url' => $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC',
        'url' => $dragUrl,
        'employer_name' => $employerName,
        'nationality' => $employee->employeeNationality
     ]) }}"
     ondragstart="window.startDragGlobal(event, 'employee', JSON.parse(this.dataset.dragPayload))">

    @if(isset($employee->active_workflows) && $employee->active_workflows->isNotEmpty())
        @php
            $isRegistration = $employee->active_workflows->contains('is_registration', true);
            $isRenewal = $employee->active_workflows->contains('is_renewal', true);
            $hasStandardWorkflow = $employee->active_workflows->contains(function ($value, $key) {
                return ($value->is_pre_production === false) && (!isset($value->is_registration) || !$value->is_registration) && (!isset($value->is_renewal) || !$value->is_renewal);
            });

            if ($isRegistration) {
                $overlayStyle = 'background-color: rgba(139, 92, 246, 0.15); border: 2px solid #8B5CF6;';
            } elseif ($isRenewal) {
                $overlayStyle = 'background-color: rgba(236, 72, 153, 0.15); border: 2px solid #EC4899;';
            } elseif ($hasStandardWorkflow) {
                $overlayStyle = 'background-color: rgba(255, 223, 0, 0.15); border: 2px solid #ffc107;';
            } else {
                $overlayStyle = 'background-color: rgba(13, 202, 240, 0.15); border: 2px solid #0dcaf0;';
            }
        @endphp
        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center rounded"
             style="{{ $overlayStyle }} z-index: 10; pointer-events: none;">
             <div class="d-flex flex-column gap-2" style="pointer-events: auto;">
                @foreach($employee->active_workflows as $wf)
                    @php
                        if (isset($wf->is_registration) && $wf->is_registration) {
                            $style = 'background-color: #8B5CF6; color: white;';
                            $icon = 'bi-person-badge';
                            $badgeClass = '';
                        } elseif (isset($wf->is_renewal) && $wf->is_renewal) {
                            $style = 'background-color: #EC4899; color: white;';
                            $icon = 'bi-arrow-repeat';
                            $badgeClass = '';
                        } elseif (isset($wf->is_pre_production) && $wf->is_pre_production) {
                            $style = '';
                            $icon = 'bi-hourglass-split';
                            $badgeClass = 'bg-info text-dark';
                        } else {
                            $style = '';
                            $icon = 'bi-gear-fill';
                            $badgeClass = 'bg-warning text-dark';
                        }
                    @endphp
                    <a href="{{ $wf->url }}"
                       class="badge {{ $badgeClass }} text-decoration-none shadow-sm border border-dark fs-6 text-truncate"
                       style="max-width: 90%; {{ $style }}">
                       <i class="bi {{ $icon }} me-1"></i> {{ $wf->status_label }}: {{ $wf->name }}
                    </a>
                @endforeach
             </div>
        </div>
    @endif

    @if($missingCount > 0)
        <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-warning text-dark border border-light shadow-sm" style="z-index: 10; margin-left: 15px; margin-top: 15px; font-size: 0.8rem;">
            {{ $missingCount }}
            <span class="visually-hidden">missing fields</span>
        </span>
    @endif
    <div class="d-flex align-items-center">
        {{-- Checkbox for Bulk Actions --}}
        <div class="me-3">
            <input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}" id="employee_checkbox_{{ $employee->id }}" data-employee-id="{{ $employee->id }}">
        </div>

        {{-- Employee Photo --}}
        <div class="position-relative" style="margin-right: 1rem;">
            <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
                 alt="Photo" class="employee-photo-thumb" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%;">
            @if(isset($employee->financialStatus))
                @if($employee->financialStatus === 'paid')
                    <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-success border border-white" title="{{ __('Fully Paid') }}" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                        <i class="bi bi-currency-dollar"></i>
                    </span>
                @elseif($employee->financialStatus === 'partial')
                    <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-primary border border-white" title="{{ __('Partial/Pending Payment') }}" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                        <i class="bi bi-currency-dollar"></i>
                    </span>
                @elseif($employee->financialStatus === 'installment_created')
                    <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-warning text-dark border border-white" title="{{ __('Installment Created') }}" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                        <i class="bi bi-currency-dollar"></i>
                    </span>
                @elseif($employee->financialStatus === 'priced')
                    <span class="position-absolute bottom-0 start-100 translate-middle badge rounded-pill bg-secondary border border-white" title="{{ __('Priced') }}" style="font-size: 0.6rem; padding: 0.25em 0.4em;">
                        <i class="bi bi-currency-dollar"></i>
                    </span>
                @endif
            @endif
        </div>

        {{-- Employee Details --}}
        <div class="flex-grow-1">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">
                    {{ trim(($employee->employeeTitleEn ?? '') . ' ' . ($employee->employeeNameEn ?? '')) ?: 'N/A' }}
                    <button class="btn btn-sm btn-link p-0 ms-1 btn-preview"
                            data-model-type="employee"
                            data-model-id="{{ $employee->id }}"
                            @click.stop
                            title="{{ __('Preview Employee') }}">
                        <i class="bi bi-search"></i>
                    </button>

                    @if($employee->employeeNationality)
                        @php $flagCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality); @endphp
                        @if($flagCode)
                            <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" alt="{{ $employee->employeeNationality }}" title="{{ $employee->employeeNationality }}">
                        @endif
                    @endif
                </h5>
                <small class="text-muted" title="{{ __('นายจ้าง') }}">
                    {{ $employerName }}
                    @if(request('addrProvince') && $employee->employer)
                        @foreach($employee->employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                            <div class="text-primary small fw-bold">{{ $label }}</div>
                        @endforeach
                    @endif
                    @if($employee->employer)
                        <button class="btn btn-sm btn-link p-0 ms-1 btn-preview"
                                data-model-type="employer"
                                data-model-id="{{ $employee->employer->id }}"
                                @click.stop
                                title="{{ __('Preview Employer') }}">
                            <i class="bi bi-search"></i>
                        </button>
                    @endif
                </small>
            </div>
            <p class="mb-1">
                {{ trim(($employee->employeeTitleTh ?? '') . ' ' . ($employee->employeeNameTh ?? '')) ?: 'N/A' }} ({{ $employee->job_title ?? 'N/A' }})
            </p>
            <small class="text-muted d-block">Passport: {{ $employee->employeePassport ?? '-' }} (หมดอายุ: {{ optional($employee->passportExpiryDate)->format('d/m/Y') ?? '-' }})</small>
            <small class="text-muted d-block">Work Permit: <strong>{{ $employee->employeeWorkPermit ?? '-' }}</strong> (หมดอายุ: {{ optional($employee->workPermitExpiryDate)->format('d/m/Y') ?? '-' }})</small>
            <small class="text-muted d-block">Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) | 90-Day: {{ optional($employee->ninetyDayReportDate)->format('d/m/Y') ?? '-' }}</small>
        </div>

        {{-- Action Buttons --}}
        <div class="ms-auto ps-3">
             <div class="btn-group-vertical btn-group-sm">
                 <a href="{{ route('employees.edit', ['employer' => $employee->employer_id, 'employee' => $employee->id]) }}" class="btn btn-outline-primary" title="{{ __('แก้ไข') }}"><i class="bi bi-pencil-fill"></i></a>
                 <a href="{{ route('employees.locate', $employee) }}" class="btn btn-outline-info" title="{{ __('ไปที่ข้อมูลนายจ้าง') }}"><i class="bi bi-geo-alt-fill"></i></a>
                 <button type="button" class="btn btn-outline-danger" title="{{ __('ลบ') }}"><i class="bi bi-trash-fill"></i></button>
             </div>
        </div>
    </div>
</div>
