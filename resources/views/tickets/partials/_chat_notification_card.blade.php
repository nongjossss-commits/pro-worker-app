{{-- resources/views/tickets/partials/_chat_notification_card.blade.php --}}
{{-- This partial is used to render the special notification card in the chat history. --}}
{{-- It receives the parsed JSON data as a $notification variable (stdClass object). --}}

@php
    // Get the country code for the flag image
    $flagCode = App\Helpers\CountryHelper::getCountryCode($notification->employee_nationality);
@endphp

<div class="p-3 my-2 rounded border border-info" style="background-color: #f0f9ff;">
    <h6 class="text-info fw-bold mb-2">
        <i class="bi bi-bell-fill me-2"></i> การแจ้งเตือน: {{ $notification->notification_title_th ?? 'N/A' }}
    </h6>
    <div class="d-flex align-items-center gap-3">
        <div class="flex-shrink-0">
            <img src="{{ $notification->employee_photo_url ?? 'https://placehold.co/64x64/e2e8f0/6c757d?text=PIC' }}"
                 alt="Photo"
                 class="rounded-circle"
                 style="width: 64px; height: 64px; object-fit: cover;">
        </div>
        <div class="flex-grow-1">
            <div class="fw-bold">
                {{-- The URL in the notification payload is the highlight link --}}
                <a href="{{ $notification->url ?? '#' }}" class="text-dark text-decoration-none">
                    <i class="bi bi-person"></i> {{ $notification->employee_name_th ?? 'N/A' }}
                </a>
                @if($notification->employee_name_en)
                    <span class="text-muted">({{ $notification->employee_name_en }})</span>
                @endif
            </div>

            <div class="small text-muted mb-2">
                <i class="bi bi-building"></i> {{ $notification->employer_name_th ?? 'N/A' }}
                {{-- Preview Employer --}}
                @if(!empty($notification->employer_id)) <!-- Assuming employer_id is available in payload or context -->
                    <button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-2"
                            data-model-type="employer"
                            data-model-id="{{ $notification->employer_id }}">
                        <i class="bi bi-search"></i>
                    </button>
                @endif
                @if($flagCode)
                    <span class="badge bg-light text-dark ms-2 d-inline-flex align-items-center">
                        <img src="{{ asset('images/flags/' . strtolower($flagCode) . '.png') }}"
                             alt="{{ $notification->employee_nationality }}"
                             title="{{ $notification->employee_nationality }}"
                             style="width: 16px; height: 12px; margin-right: 5px;">
                        <span>{{ $notification->employee_nationality }}</span>
                    </span>
                @endif
            </div>

            <div class="d-flex gap-2">
                {{-- Preview button --}}
                @if($notification->employee_id)
                    <button type="button" class="btn btn-sm btn-outline-info btn-preview"
                            data-model-type="employee"
                            data-model-id="{{ $notification->employee_id }}">
                        <i class="bi bi-search"></i> พรีวิว
                    </button>
                @endif

                {{-- Link to highlight notification --}}
                <a href="{{ $notification->url ?? '#' }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right"></i> ไปยังการแจ้งเตือน
                </a>
            </div>
        </div>
    </div>
</div>
