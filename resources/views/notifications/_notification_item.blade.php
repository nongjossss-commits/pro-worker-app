@php
    $employee = $notification->employee;
    // Correctly determine the employer, whether the notification is linked to an employee or directly to an employer.
    $employer = $employee->employer ?? $notification->employer;
    $days_remaining = $notification->days_remaining;
    $is_overdue = $days_remaining < 0;

    // For missing data types, days_remaining is 0/irrelevant, so handle badge
    $isMissingDataType = in_array($notification->type, ['pink_card_missing', 'residence_permit_missing']);

    $card_class = '';
    $badge_class = 'bg-info text-dark';

    if ($isMissingDataType) {
        $card_class = 'alert-danger'; // Always red for missing data
        $badge_class = 'bg-danger';
    } elseif ($is_overdue) {
        $card_class = 'alert-dark';
        $badge_class = 'bg-dark';
    } elseif ($days_remaining <= 30) {
        $card_class = 'alert-danger';
        $badge_class = 'bg-danger';
    } elseif ($days_remaining <= 60) {
        $card_class = 'alert-warning';
        $badge_class = 'bg-warning text-dark';
    }

    // V2.5-S20: Enhanced payload for rich chat messages
    $notificationTypeLabels = [
        'ninety_day_report' => __('ครบกำหนดรายงานตัว 90 วัน'),
        'work_permit_expiry' => __('ใบอนุญาตทำงานหมดอายุ'),
        'visa_expiry' => __('วีซ่าหมดอายุ'),
        'passport_expiry' => __('หนังสือเดินทางหมดอายุ'),
        'pink_card_missing' => __('ไม่มีข้อมูลบัตรชมพู'),
        'residence_permit_missing' => __('ไม่มีข้อมูลใบอนุญาตพำนัก'),
        'work_permit_mou' => 'Work Permit (MOU)',
        'employer_document_expiry' => __('เอกสารนายจ้างหมดอายุ'),
        'insurance_expiry' => __('ประกันหมดอายุ')
    ];
    $notification_title_th = $notificationTypeLabels[$notification->type] ?? ucfirst(str_replace('_', ' ', $notification->type));

    $payload = [
        'id' => $notification->id,
        'type' => 'notification',
        'render_as' => $employee ? 'employee_card' : 'simple_text',
        'title' => $notification->type, // raw type
        'notification_title_th' => $notification_title_th,
        'employee_id' => optional($employee)->id,
        'employee_name_th' => optional($employee)->employeeNameTh,
        'employee_name_en' => optional($employee)->employeeNameEn,
        'employee_photo_url' => optional($employee)->photo_url,
        'employee_nationality' => optional($employee)->employeeNationality,
        'employer_name_th' => optional($employer)->employerNameTh,
        'url' => request()->fullUrl() . '#notification-item-' . $notification->id,
    ];
@endphp

<div id="notification-item-{{ $notification->id }}" class="alert {{ $card_class }} notification-item position-relative" data-drag-payload="{{ json_encode($payload) }}">
    @if($employee && isset($employee->active_workflows) && $employee->active_workflows->isNotEmpty())
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
    <div class="d-flex align-items-start gap-3">
        {{-- Conditionally show checkbox. Disabled for employer notifications. --}}
        @if($employee)
            <input class="form-check-input bulk-action-checkbox employee-checkbox mt-1" type="checkbox" value="{{ $employee->id }}" id="notification_checkbox_{{ $notification->id }}"
                   data-employee-id="{{ $employee->id }}"
                   data-employer-id="{{ $employee->employer_id }}"
                   data-name-th="{{ $employee->employeeNameTh }}"
                   data-name-en="{{ $employee->employeeNameEn }}"
                   data-photo="{{ $employee->photo_url }}"
                   data-employer-name="{{ $employer?->employerNameTh ?? 'N/A' }}"
                   data-title-th="{{ $employee->employeeTitleTh }}"
                   data-title-en="{{ $employee->employeeTitleEn }}"
                   data-nationality="{{ $employee->employeeNationality }}"
                   data-gender="{{ $employee->gender }}"
                   data-country-code="{{ \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality) }}"
            >
        @else
            <input class="form-check-input mt-1" type="checkbox" disabled>
        @endif

        {{-- Use employee photo or a placeholder --}}
        <img src="{{ $employee->photo_url ?? 'https://placehold.co/48x48/e2e8f0/6c757d?text=PIC' }}"
             alt="Photo"
             class="w-12 h-12 object-cover rounded-full bg-light flex-shrink-0"
             style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start w-100">
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">
                    {{ $itemNumber }}.
                    {{-- Display Employee Name or Employer Document Type --}}
                    @if($employee)
                        <a href="{{ route('notifications.view-employee', $notification->id) }}" class="text-decoration-none text-dark fw-bold">
                            {{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'N/A' }}
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employee" data-model-id="{{ $employee->id }}" title="{{ __('พรีวิวข้อมูล') }}"> <i class="bi bi-search"></i> </button>
                        @if(optional($employee)->employeeNationality)
                            @php
                                $flagCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
                            @endphp
                            @if($flagCode)
                            <span class="badge bg-light text-dark ms-2 d-inline-flex align-items-center">
                                <img src="{{ asset('images/flags/' . strtolower($flagCode) . '.png') }}" alt="{{ $employee->employeeNationality }}" title="{{ $employee->employeeNationality }}" style="width: 16px; height: 12px; margin-right: 5px;">
                                <span>{{ $employee->employeeNationality }}</span>
                            </span>
                            @endif
                        @endif
                    @else
                        เอกสารนายจ้าง
                    @endif
                </h5>
                @if($employee)
                    <p class="mb-1 small">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'N/A' }}</p>
                @endif
                <p class="mb-1 small">
                    <strong>{{ __('นายจ้าง:') }}</strong> {{ $employer?->employerNameTh ?? 'N/A' }}
                    @if(request('addrProvince') && $employer)
                        @foreach($employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                            <span class="text-primary small fw-bold ms-1">{{ $label }}</span>
                        @endforeach
                    @endif
                    @if($employer)
                    <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $employer->id }}" title="{{ __('พรีวิวข้อมูล') }}"> <i class="bi bi-search"></i> </button>
                    @endif
                </p>
                <p class="mb-0 small">
                    @if($isMissingDataType)
                        <strong>{{ __('สถานะ:') }}</strong> <span class="text-danger">{{ __('ข้อมูลยังไม่ครบถ้วน') }}</span>
                    @else
                        <strong>{{ __('วันครบกำหนด:') }}</strong> {{ \Carbon\Carbon::parse($notification->due_date)->translatedFormat('d F Y') }}
                    @endif
                </p>
            </div>

            <div class="text-end flex-shrink-0 mt-2 mt-sm-0 align-self-end align-self-sm-auto ms-sm-2">
                <div class="d-flex flex-column align-items-end">
                    <span class="badge {{ $badge_class }} mb-2 d-block text-nowrap fs-6">
                        @if($isMissingDataType)
                            กรุณาอัพเดต
                        @elseif($is_overdue)
                            หมดอายุ {{ abs($days_remaining) }} วัน
                        @else
                            เหลือ {{ $days_remaining }} วัน
                        @endif
                    </span>
                    <div class="d-flex flex-row flex-sm-column gap-1 justify-content-end" role="group">
                        @if($notification->status === 'cancelled')
                            <form id="restore-form-{{ $notification->id }}" action="{{ route('notifications.restore', $notification->id) }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                            <button type="submit" form="restore-form-{{ $notification->id }}" class="btn btn-sm btn-outline-info" title="{{ __('กู้คืน') }}">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>

                            <form id="force-delete-form-{{ $notification->id }}" action="{{ route('notifications.forceDelete', $notification->id) }}" method="POST" onsubmit="return confirm('{{ __('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้อย่างถาวร?') }}'));" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                            <button type="submit" form="force-delete-form-{{ $notification->id }}" class="btn btn-sm btn-outline-danger" title="{{ __('ลบถาวร') }}">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        @else
                            <a href="#" class="btn btn-sm btn-outline-info" title="{{ __('สร้างงาน') }}"><i class="bi bi-rocket-takeoff-fill"></i></a>

                            {{-- Update/Renew Button --}}
                            <a href="#" class="btn btn-sm btn-outline-success"
                               title="{{ $isMissingDataType ? 'อัพเดตข้อมูล' : 'ต่ออายุ' }}"
                               data-bs-toggle="modal"
                               data-bs-target="#renewNotificationModal"
                               data-notification-id="{{ $notification->id }}"
                               data-notification-type="{{ $notification->type }}">
                               <i class="bi {{ $isMissingDataType ? 'bi-pencil-square' : 'bi-calendar-check' }}"></i>
                            </a>

                            {{-- Only show the 'Locate' button if there is an employee --}}
                            @if($employee)
                                <a href="{{ route('notifications.view-employee', $notification->id) }}" class="btn btn-sm btn-outline-primary" title="{{ __('ค้นหาตำแหน่ง') }}"><i class="bi bi-geo-alt-fill"></i></a>
                            @endif
                            <a href="#" class="btn btn-sm btn-outline-warning" title="{{ __('ยกเลิก') }}" data-bs-toggle="modal" data-bs-target="#cancelNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-x-circle"></i></a>

                            {{-- Drag Handle --}}
                            <a href="#" class="btn btn-sm btn-light border cursor-grab"
                               draggable="true"
                               ondragstart="window.startDragGlobal(event, 'notification', JSON.parse(document.getElementById('notification-item-{{ $notification->id }}').dataset.dragPayload))"
                               title="Drag">
                                <i class="bi bi-grid-3x2-gap-fill text-muted"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
