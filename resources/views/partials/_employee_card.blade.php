@php
    $employerName = $employee->employer->employerNameTh ?? 'N/A';
    // Fix for duplicate IDs in list views
    $cardId = 'employee-card-' . ($idPrefix ?? '') . $employee->id;
    // Determine the source menu name if passed, otherwise try to infer or default
    $sourceMenu = $source_menu ?? __('Employee List');
    // Generate the drag source URL. Use the 'dragUrl' prop if provided (for custom anchors like in Groups),
    // otherwise fallback to a standard hash link on the current page.
    $dragSourceUrl = $dragUrl ?? (request()->fullUrlWithQuery(['highlight_employee' => $employee->id]) . '#' . $cardId);

    $dragPayload = [
        'id' => $employee->id,
        'title' => $employee->employeeNameEn ?? $employee->employeeNameTh,
        'title_th' => $employee->employeeNameTh,
        'title_en' => $employee->employeeNameEn,
        'subtitle' => $employee->job_title ?? 'N/A',
        'photo_url' => $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : asset('images/default-profile.png'),
        'url' => $dragSourceUrl,
        'source_menu' => $sourceMenu,
        'employer_name' => $employerName,
        'nationality' => $employee->employeeNationality
    ];
@endphp

@php
    $cardBorderStyle = '';
    if (isset($employee->active_workflows) && $employee->active_workflows->isNotEmpty()) {
        $isRegistration = $employee->active_workflows->contains('is_registration', true);
        $isRenewal = $employee->active_workflows->contains('is_renewal', true);
        $hasStandardWorkflow = $employee->active_workflows->contains(function ($value, $key) {
            return ($value->is_pre_production === false) && (!isset($value->is_registration) || !$value->is_registration) && (!isset($value->is_renewal) || !$value->is_renewal);
        });

        if ($isRegistration) {
            $cardBorderStyle = 'border-left: 4px solid #8B5CF6;';
            $overlayBorder = 'border: 2px solid #8B5CF6;';
            $overlayBg = 'background-color: rgba(139, 92, 246, 0.08);';
        } elseif ($isRenewal) {
            $cardBorderStyle = 'border-left: 4px solid #EC4899;';
            $overlayBorder = 'border: 2px solid #EC4899;';
            $overlayBg = 'background-color: rgba(236, 72, 153, 0.08);';
        } elseif ($hasStandardWorkflow) {
            $cardBorderStyle = 'border-left: 4px solid #ffc107;';
            $overlayBorder = 'border: 2px solid #ffc107;';
            $overlayBg = 'background-color: rgba(255, 223, 0, 0.08);';
        } else {
            $cardBorderStyle = 'border-left: 4px solid #0dcaf0;';
            $overlayBorder = 'border: 2px solid #0dcaf0;';
            $overlayBg = 'background-color: rgba(13, 202, 240, 0.08);';
        }
    }
@endphp
<div id="{{ $cardId }}" class="employee-card card mb-3 position-relative" style="{{ $cardBorderStyle }}">
    <x-last-edited-badge :model="$employee" />
    @if(isset($employee->active_workflows) && $employee->active_workflows->isNotEmpty())
        {{-- Desktop/Tablet: overlay centered on card (≥576px) --}}
        <div class="d-none d-sm-flex position-absolute top-0 start-0 w-100 h-100 justify-content-center align-items-center rounded"
             style="{{ $overlayBorder }} {{ $overlayBg }} z-index: 10; pointer-events: none;">
             <div class="d-flex flex-column gap-2" style="pointer-events: auto; touch-action: auto;">
                @foreach($employee->active_workflows as $wf)
                    @php
                        $url = $wf->url;
                        if (isset($wf->is_registration) && $wf->is_registration) {
                            $style = 'background-color: #8B5CF6; color: white;';
                            $icon = 'bi-person-badge';
                            $tooltip = __('Go to Registration Resolution');
                            $badgeClass = '';
                        } elseif (isset($wf->is_renewal) && $wf->is_renewal) {
                            $style = 'background-color: #EC4899; color: white;';
                            $icon = 'bi-arrow-repeat';
                            $tooltip = __('Go to Renewal Resolution');
                            $badgeClass = '';
                        } elseif (isset($wf->is_pre_production) && $wf->is_pre_production) {
                            $style = '';
                            $icon = 'bi-hourglass-split';
                            $tooltip = __('Go to Pre-Production');
                            $badgeClass = 'bg-info';
                        } else {
                            $style = '';
                            $icon = 'bi-gear-fill';
                            $tooltip = __('Go to Workflow');
                            $badgeClass = 'bg-warning';
                        }
                    @endphp
                    <a href="{{ $url }}"
                       class="badge {{ $badgeClass }} text-dark text-decoration-none shadow-sm border border-dark fs-6 text-truncate"
                       title="{{ $tooltip }}"
                       style="max-width: 90%; {{ $style }}">
                       <i class="bi {{ $icon }} me-1"></i> {{ $wf->status_label }}: {{ $wf->name }}
                    </a>
                @endforeach
             </div>
        </div>
    @endif
    <div class="card-body d-flex flex-wrap align-items-center" style="row-gap: 0.5rem;">
        <div class="me-3 flex-shrink-0">
            <input class="form-check-input employee-checkbox" type="checkbox" value="{{ $employee->id }}" data-employee-id="{{ $employee->id }}" data-employer-id="{{ $employee->employer_id }}" data-name-th="{{ $employee->employeeNameTh }}" data-name-en="{{ $employee->employeeNameEn }}" data-photo="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : asset('images/default-profile.png') }}" data-employer-name="{{ $employee->employer->employerNameTh ?? 'N/A' }}">
        </div>

        <div class="position-relative flex-shrink-0" style="margin-right: 1rem;">
            <img src="{{ $employee->employeePhoto ? asset('storage/' . $employee->employeePhoto) : asset('images/default-profile.png') }}"
                loading="lazy"
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

        <div class="employee-info flex-grow-1 position-relative">
            {{-- Group & Team Tags --}}
            @if($employee->teams && $employee->teams->count() > 0 && !($hideTeamTags ?? false))
                <div class="position-absolute top-0 end-0 mt-0 me-2 d-flex flex-wrap justify-content-end gap-1" style="max-width: 250px;">
                    @foreach($employee->teams as $team)
                        @if($team->group)
                        <a href="{{ route('groups.locate_member', ['group' => $team->group->id, 'employee' => $employee->id]) }}"
                           class="badge text-decoration-none"
                           style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: normal; font-size: 0.75rem;"
                           title="{{ __('Group') }}: {{ $team->group->name }} | {{ __('Team') }}: {{ $team->name }}"
                           onclick="event.preventDefault(); Swal.fire({
                               title: '{{ __('Go to group') }}',
                               text: '{{ __('Do you want to go to group') }}: {{ $team->group->name }}?',
                               icon: 'question',
                               showCancelButton: true,
                               confirmButtonText: '{{ __('Go') }}',
                               cancelButtonText: '{{ __('Cancel') }}'
                           }).then((result) => { if(result.isConfirmed) window.location.href = this.href; })">
                            <i class="bi bi-tag-fill me-1"></i>{{ $team->group->name }}
                        </a>
                        @endif
                    @endforeach
                </div>
            @endif

            <span class="employee-name-en">
                @if(isset($pagination) && $pagination instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ ($pagination->currentPage() - 1) * $pagination->perPage() + $loop->iteration }}.
                @else
                    {{ $loop->iteration }}.
                @endif
                {{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? __('No English Name') }}
            </span>

            <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2" data-model-type="employee" data-model-id="{{ $employee->id }}" title="{{ __('Preview Data') }}">
                <i class="bi bi-search"></i>
            </button>

            @if($employee->employeeNationality)
                @php
                    $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                @endphp
                @if($countryCode)
                    <span class="badge bg-light text-dark ms-2 d-inline-flex align-items-center">
                        <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" style="width: 16px; height: 12px; margin-right: 5px;">
                        <span>{{ $employee->employeeNationality }}</span>
                    </span>
                @endif
            @endif

            <span class="employee-name-th d-block">
                {{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'N/A' }} ({{ $employee->job_title ?? 'N/A' }})
            </span>

            <span class="employer-name d-block text-muted">
                {{ __('Employer:') }} {{ $employerName }}
                @if(request('addrProvince') && $employee->employer)
                    @foreach($employee->employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                        <span class="text-primary small fw-bold">{{ $label }}</span>
                    @endforeach
                @endif
                @if($employee->employer)
                <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2" data-model-type="employer" data-model-id="{{ $employee->employer->id }}" title="{{ __('Preview Data') }}">
                    <i class="bi bi-search"></i>
                </button>
                @endif
            </span>

            <div class="document-details small mt-2">
                Passport: {{ $employee->employeePassport ?? '-' }} ({{ __('Expires:') }} {{ $employee->passportExpiryDate ? $employee->passportExpiryDate->format('d/m/Y') : '-' }})
                <br>
                Visa ({{ $employee->workPermitMOUGroup ?? '-' }}) | 90-Day: {{ $employee->ninetyDayReportDate ? $employee->ninetyDayReportDate->format('d/m/Y') : '-' }}
            </div>
        </div>

        {{-- Single unified actions section: right on ≥576px, below on <576px --}}
        <div class="employee-actions-unified">
            {{-- Mobile-only status badges (below 576px) --}}
            @if(isset($employee->active_workflows) && $employee->active_workflows->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mb-2 d-sm-none">
                    @foreach($employee->active_workflows as $wf)
                        @php
                            $url = $wf->url;
                            if (isset($wf->is_registration) && $wf->is_registration) {
                                $mStyle = 'background-color: #8B5CF6; color: white;';
                                $mIcon = 'bi-person-badge';
                            } elseif (isset($wf->is_renewal) && $wf->is_renewal) {
                                $mStyle = 'background-color: #EC4899; color: white;';
                                $mIcon = 'bi-arrow-repeat';
                            } elseif (isset($wf->is_pre_production) && $wf->is_pre_production) {
                                $mStyle = 'background-color: #0dcaf0; color: white;';
                                $mIcon = 'bi-hourglass-split';
                            } else {
                                $mStyle = 'background-color: #ffc107; color: #212529;';
                                $mIcon = 'bi-gear-fill';
                            }
                        @endphp
                        <a href="{{ $url }}"
                           class="badge text-decoration-none shadow-sm"
                           style="{{ $mStyle }} font-size: 0.8rem; padding: 0.4em 0.7em; touch-action: auto;">
                           <i class="bi {{ $mIcon }} me-1"></i> {{ $wf->status_label }}: {{ Str::limit($wf->name, 20) }}
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="d-flex flex-wrap align-items-center gap-2">
                @if(isset($isTrashView) && $isTrashView)
                    @include('admin.trash._action_buttons', ['modelName' => 'employees', 'item' => $employee])
                @else
                    <x-employee-action-buttons :employee="$employee" :show-locate-button="($showLocateButton ?? false)" />
                    @if(isset($currentTeamId))
                        <form action="{{ route('groups.teams.members.remove', ['team' => $currentTeamId, 'employee' => $employee->id]) }}" method="POST" class="d-inline delete-member-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Remove from Team') }}">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                    @endif
                @endif
                <span class="btn btn-sm btn-light border cursor-grab"
                      draggable="true"
                      data-drag-payload="{{ json_encode($dragPayload) }}"
                      ondragstart="window.startDragGlobal(event, 'employee', JSON.parse(this.dataset.dragPayload))"
                     title="{{ __('Drag') }}">
                    <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
                </span>
            </div>
        </div>
    </div>
</div>
