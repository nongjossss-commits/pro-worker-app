{{-- Single appointment card for cross-date search results — same visual
     style as day_appointments_list.blade.php's card, but standalone (no
     fixed-id toolbar/script) since this card repeats once per matching
     date group and fixed ids would collide if duplicated. Read-only plus
     the two safe actions (Update, Mark Complete) which only call global
     onclick functions, not id-bound listeners. --}}
@php
    $employee  = $item->employee;
    $order     = $item->order;
    $employer  = $order->employer ?? null;
    $workType  = $order->workType ?? null;

    $titleEn = $employee->employeeTitleEn ?? '';
    $nameEn  = $employee->employeeNameEn  ?? 'N/A';
    $titleTh = $employee->employeeTitleTh ?? '';
    $nameTh  = $employee->employeeNameTh  ?? '';

    $passport    = $employee->employeePassport    ?? '-';
    $nationality = $employee->employeeNationality ?? '';
    $countryCode = $nationality ? \App\Helpers\CountryHelper::getCountryCode($nationality) : null;

    $photoUrl = ($employee && $employee->employeePhoto)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($employee->employeePhoto)
        : null;
    $initials = mb_substr(($nameTh ?: $nameEn) ?: 'E', 0, 1);

    $isAppCompleted = $item->appointment_completed_at ? true : false;
    $appDate        = $item->appointment_date;
    $appDisplay     = '-';
    if ($appDate) {
        $appDisplay = ($appDate->format('H:i:s') === '00:00:00')
            ? $appDate->format('d/m/Y')
            : $appDate->format('d/m/Y H:i');
    }
    $appDateValue = $appDate ? $appDate->format('Y-m-d H:i') : '';
    $appLocation  = $item->appointment_location ?? '';
@endphp
<div class="col-12 mb-3">
    <div class="card border-0 shadow-sm overflow-hidden position-relative">
        <div class="position-absolute top-0 start-0 h-100"
             style="width:5px; background-color:{{ $isAppCompleted ? '#10B981' : '#F59E0B' }};"></div>
        <div class="card-body py-3 ps-4 pe-3">
            <div class="d-flex align-items-start gap-3">
                <div class="flex-shrink-0 position-relative" style="width:52px; height:52px;">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" loading="lazy" class="rounded-circle shadow-sm"
                             style="width:52px; height:52px; object-fit:cover;"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="rounded-circle bg-secondary text-white align-items-center justify-content-center shadow-sm fw-bold"
                             style="display:none; width:52px; height:52px; font-size:1.3rem; position:absolute; top:0; left:0;">
                            {{ $initials }}
                        </div>
                    @else
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm fw-bold"
                             style="width:52px; height:52px; font-size:1.3rem;">
                            {{ $initials }}
                        </div>
                    @endif
                </div>

                <div class="flex-grow-1" style="min-width:0;">
                    <div class="fw-bold text-dark lh-sm d-flex align-items-center gap-1 flex-wrap">
                        <span>{{ $titleEn }} {{ $nameEn }}</span>
                        @if($item->employee_id)
                        <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted btn-preview"
                                data-model-type="employee" data-model-id="{{ $item->employee_id }}"
                                title="{{ __('Preview Employee') }}">
                            <i class="bi bi-search" style="font-size:0.8rem;"></i>
                        </button>
                        @endif
                    </div>
                    <div class="text-muted small">{{ $titleTh }} {{ $nameTh }}</div>

                    <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:0.78rem; color:#6c757d;">
                        @if($passport && $passport !== '-')
                        <span><i class="bi bi-passport text-primary me-1"></i>{{ $passport }}</span>
                        @endif
                        @if($nationality)
                        <span class="d-inline-flex align-items-center gap-1">
                            @if($countryCode)
                                <img src="{{ asset('images/flags/'.strtolower($countryCode).'.png') }}" loading="lazy"
                                     style="width:15px; height:11px;" onerror="this.style.display='none'">
                            @endif
                            {{ $nationality }}
                        </span>
                        @endif
                        @if($workType)
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size:0.72rem;">
                            <i class="bi bi-kanban me-1"></i>{{ $workType->name }}
                        </span>
                        @endif
                    </div>

                    @if($employer)
                    <div class="mt-1 d-flex align-items-center gap-1 flex-wrap" style="font-size:0.82rem;">
                        <i class="bi bi-building text-secondary me-1"></i>
                        <span class="fw-semibold">{{ $employer->employerNameTh ?? '-' }}</span>
                        @if($employer->employerNameEn)
                            <span class="text-muted">({{ $employer->employerNameEn }})</span>
                        @endif
                        <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted btn-preview"
                                data-model-type="employer" data-model-id="{{ $employer->id }}"
                                title="{{ __('Preview Employer') }}">
                            <i class="bi bi-search" style="font-size:0.78rem;"></i>
                        </button>
                    </div>
                    @endif

                    <div class="mt-1" style="font-size:0.78rem;">
                        <i class="bi bi-clock me-1 {{ $isAppCompleted ? 'text-success' : 'text-warning' }}"></i>
                        <span class="fw-bold {{ $isAppCompleted ? 'text-success' : 'text-warning' }}">{{ $appDisplay }}</span>
                        @if($appLocation)
                            <i class="bi bi-geo-alt-fill text-info ms-2 me-1"></i>
                            <span class="text-info">{{ $appLocation }}</span>
                        @endif
                        @if($isAppCompleted)
                            <span class="badge bg-success ms-1">{{ __('Done') }}</span>
                        @endif
                    </div>
                </div>

                <div class="flex-shrink-0 d-flex flex-column gap-2 align-items-end">
                    <a href="{{ route('workflow.index', ['tab' => $workType->slug ?? '']) }}#item-card-{{ $item->id }}"
                       class="btn btn-sm btn-outline-secondary" target="_blank" title="{{ __('Open in Workflow Board') }}">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    @can('edit-employees')
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm fw-bold {{ $isAppCompleted ? 'btn-success' : 'btn-warning text-dark' }}"
                                onclick="wfUpdateAppointment({{ $item->id }}, '{{ addslashes($appDateValue) }}', '{{ addslashes($appLocation) }}', this)"
                                title="{{ __('Update Appointment') }}">
                            @if($isAppCompleted)
                                <i class="bi bi-check-circle-fill me-1"></i>{{ __('Completed') }}
                            @else
                                <i class="bi bi-clock-fill me-1"></i>{{ __('Update') }}
                            @endif
                        </button>
                        @if(!$isAppCompleted)
                        <button class="btn btn-sm btn-success fw-bold"
                                onclick="wfMarkAppointmentComplete({{ $item->id }}, this)" title="{{ __('Mark as Completed') }}">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        @endif
                    </div>
                    @endcan
                    @if($item->employee_id)
                        <x-share-handle type="employee" :id="$item->employee_id" :name="optional($item->employee)->employeeNameEn" :context="'item:' . $item->id" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
