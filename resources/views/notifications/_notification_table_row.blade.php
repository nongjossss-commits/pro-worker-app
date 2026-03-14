@php
    $employee = $notification->employee;
    $employer = $employee->employer ?? $notification->employer;
    $days_remaining = $notification->days_remaining;
    $is_overdue = $days_remaining < 0;
    $text_class = 'text-dark';
    if ($is_overdue) {
        $text_class = 'text-danger fw-bold';
    } elseif ($days_remaining <= 7) {
        $text_class = 'text-danger';
    } elseif ($days_remaining <= 30) {
        $text_class = 'text-warning';
    }

    // NEW PAYLOAD STRUCTURE - Mirrored from _notification_item
    $payload = [
        'id' => $notification->id,
        'type' => 'notification',
        'render_as' => $employee ? 'employee_card' : 'simple_text',
        'title' => $notification->type,
        'employee_name' => $employee ? ($employee->employeeNameTh ?? $employee->employeeNameEn) : null,
        'employee_id' => $employee ? $employee->id : null,
        'url' => route('notifications.view-employee', $notification->id),
    ];
@endphp

<tr id="notification-row-{{ $notification->id }}" data-drag-payload="{{ json_encode($payload) }}">
    <td>
        {{-- Conditionally show checkbox. Disabled for employer notifications. --}}
        @if($employee)
            <input class="form-check-input bulk-action-checkbox employee-checkbox" type="checkbox" value="{{ $employee->id }}" id="notification_table_checkbox_{{ $notification->id }}"
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
            <input class="form-check-input" type="checkbox" value="" disabled>
        @endif
    </td>
    <td>{{ $itemNumber }}</td>
    <td>
        <i class="bi bi-grid-3x2-gap-fill text-muted cursor-grab"
           draggable="true"
           ondragstart="window.startDragGlobal(event, 'notification', JSON.parse(document.getElementById('notification-row-{{ $notification->id }}').dataset.dragPayload))"
           title="Drag"></i>
    </td>
    <td>
        @if($employee)
            <div class="d-flex align-items-center">
                <img src="{{ $employee->photo_url }}" alt="Photo" class="employee-photo-thumb" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-right: 1rem;">
                <div>
                    <div>
                        <a href="{{ route('notifications.view-employee', $notification->id) }}" class="text-decoration-none text-dark fw-bold">
                            {{ $employee->employeeTitleEn ?? '' }} {{ $employee->employeeNameEn ?? 'N/A' }}
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employee" data-model-id="{{ $employee->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>

                        @if(isset($employee->active_workflows) && $employee->active_workflows->isNotEmpty())
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
                                   class="badge {{ $badgeClass }} text-decoration-none ms-1 border border-dark shadow-sm"
                                   style="{{ $style }}"
                                   title="{{ $wf->status_label }}: {{ $wf->name }}">
                                   <i class="bi {{ $icon }} me-1"></i>{{ $wf->status_label }}
                                </a>
                            @endforeach
                        @endif
                    </div>
                    <div class="small text-muted">{{ $employee->employeeTitleTh ?? '' }} {{ $employee->employeeNameTh ?? 'N/A' }}</div>
                </div>
            </div>
        @else
            <div class="text-muted">{{ __('เอกสารนายจ้าง') }}</div>
        @endif
    </td>
    <td>
        @if($employee && $employee->employeeNationality)
            @php
                $countryCode = \App\Helpers\CountryHelper::getCountryCode($employee->employeeNationality);
            @endphp
            @if($countryCode)
                <span class="d-inline-flex align-items-center">
                    <img src="{{ asset('images/flags/' . strtolower($countryCode) . '.png') }}" alt="{{ $countryCode }}" style="width: 20px; height: 15px; margin-right: 8px;">
                    <span>{{ $employee->employeeNationality }}</span>
                </span>
            @endif
        @else
            <span class="text-muted">{{ __('N/A') }}</span>
        @endif
    </td>
    <td>
        {{ $employer?->employerNameTh ?? 'N/A' }}
        @if(request('addrProvince') && $employer)
            @foreach($employer->getMatchedAddressLabels(request('addrProvince'), request('addrDistrict'), request('addrSubDistrict')) as $label)
                <div class="text-primary small fw-bold">{{ $label }}</div>
            @endforeach
        @endif
        @if($employer)
            <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="employer" data-model-id="{{ $employer->id }}" title="พรีวิวข้อมูล"> <i class="bi bi-search"></i> </button>
        @endif
    </td>
    <td class="text-nowrap">{{ \Carbon\Carbon::parse($notification->due_date)->translatedFormat('d M Y') }}</td>
    <td class="{{ $text_class }} text-nowrap">
        @if($is_overdue)
            หมดอายุ {{ abs($days_remaining) }} วัน
        @else
            เหลือ {{ $days_remaining }} วัน
        @endif
    </td>
    <td class="text-center">
        <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
            @if($notification->status === 'cancelled')
                <form action="{{ route('notifications.restore', $notification->id) }}" method="POST" class="d-grid d-md-inline">
                    @csrf
                    <button type="submit" class="btn btn-info btn-sm" title="{{ __('กู้คืน') }}"><i class="bi bi-arrow-counterclockwise"></i>{{ __('กู้คืน') }}</button>
                </form>
                 <form action="{{ route('notifications.forceDelete', $notification->id) }}" method="POST" class="d-grid d-md-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้อย่างถาวร?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" title="ลบถาวร"><i class="bi bi-trash3-fill"></i></button>
                </form>
            @else
                <a href="#" class="btn btn-info" title="{{ __('สร้างงาน') }}"><i class="bi bi-rocket-takeoff-fill"></i></a>
                <a href="#" class="btn btn-success" title="ต่ออายุ" data-bs-toggle="modal" data-bs-target="#renewNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-calendar-check"></i></a>
                @if($employee)
                    <a href="{{ route('notifications.view-employee', $notification->id) }}" class="btn btn-primary" title="ค้นหาตำแหน่ง"><i class="bi bi-geo-alt-fill"></i></a>
                @endif
                <a href="#" class="btn btn-warning" title="{{ __('ยกเลิก') }}" data-bs-toggle="modal" data-bs-target="#cancelNotificationModal" data-notification-id="{{ $notification->id }}"><i class="bi bi-x-circle"></i></a>
            @endif
        </div>
    </td>
</tr>
