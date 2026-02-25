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
        'ninety_day_report' => 'ครบกำหนดรายงานตัว 90 วัน',
        'work_permit_expiry' => 'ใบอนุญาตทำงานหมดอายุ',
        'visa_expiry' => 'วีซ่าหมดอายุ',
        'passport_expiry' => 'หนังสือเดินทางหมดอายุ',
        'pink_card_missing' => 'ไม่มีข้อมูลบัตรชมพู',
        'residence_permit_missing' => 'ไม่มีข้อมูลใบอนุญาตพำนัก',
        'work_permit_mou' => 'Work Permit (MOU)',
        'employer_document_expiry' => 'เอกสารนายจ้างหมดอายุ',
        'insurance_expiry' => 'ประกันหมดอายุ'
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
        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center rounded"
             style="background-color: rgba(255, 223, 0, 0.15); border: 2px solid #ffc107; z-index: 10; pointer-events: none;">
             <div class="d-flex flex-column gap-2" style="pointer-events: auto;">
                @foreach($employee->active_workflows as $wf)
                <a href="{{ $wf->url }}"
                       class="badge bg-warning text-dark text-decoration-none shadow-sm border border-dark fs-6 text-truncate"
                       style="max-width: 90%;">
                       <i class="bi bi-gear-fill me-1"></i> {{ $wf->status_label }}: {{ $wf->name }}
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
                        <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employee" data-model-id="{{ $employee->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
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
                    <strong>นายจ้าง:</strong> {{ $employer?->employerNameTh ?? 'N/A' }}
                    @if(request('addrProvince') && $employer)
                        @foreach($employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                            <span class="text-primary small fw-bold ms-1">{{ $label }}</span>
                        @endforeach
                    @endif
                    @if($employer)
                    <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $employer->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
                    @endif
                </p>
                <p class="mb-0 small">
                    @if($isMissingDataType)
                        <strong>สถานะ:</strong> <span class="text-danger">ข้อมูลยังไม่ครบถ้วน</span>
                    @else
                        <strong>วันครบกำหนด:</strong> {{ \Carbon\Carbon::parse($notification->due_date)->translatedFormat('d F Y') }}
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
                            <button type="submit" form="restore-form-{{ $notification->id }}" class="btn btn-sm btn-outline-info" title="กู้คืน">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>

                            <form id="force-delete-form-{{ $notification->id }}" action="{{ route('notifications.forceDelete', $notification->id) }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้อย่างถาวร?');" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                            <button type="submit" form="force-delete-form-{{ $notification->id }}" class="btn btn-sm btn-outline-danger" title="ลบถาวร">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        @else
                            <a href="#" class="btn btn-sm btn-outline-info" title="สร้างงาน"><i class="bi bi-rocket-takeoff-fill"></i></a>

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
                                <a href="{{ route('notifications.view-employee', $notification->id) }}" class="btn btn-sm btn-outline-primary" title="ค้นหาตำแหน่ง"><i class="bi bi-geo-alt-fill"></i></a>
                            @endif
                            <a href="#" class="btn btn-sm btn-outline-warning" title="ยกเลิก" data-bs-toggle="modal" data-bs-target="#cancelNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-x-circle"></i></a>

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
