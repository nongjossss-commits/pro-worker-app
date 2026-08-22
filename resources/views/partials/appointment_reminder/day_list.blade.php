@if($items->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="bi bi-calendar-x fs-1 opacity-25"></i>
    <p class="mt-2">{{ __('No appointments on this date.') }}</p>
</div>
@else
<div class="list-group list-group-flush">
    @foreach($items as $item)
        @php
            $badgeClass = match($item->source) {
                'registration' => 'bg-primary',
                'renewal' => 'bg-warning text-dark',
                default => 'bg-info text-dark',
            };
        @endphp
        <div class="list-group-item appointment-card"
             data-employee-name-th="{{ strtolower($item->name_th ?? '') }}"
             data-employer-name="{{ strtolower($item->company ?? '') }}">
            <div class="d-flex align-items-start gap-2">
                @can('edit-employees')
                @if($item->employee_id)
                <div class="pt-1 flex-shrink-0">
                    <input class="form-check-input employee-checkbox"
                           type="checkbox"
                           style="width:18px; height:18px; cursor:pointer;"
                           value="{{ $item->employee_id }}"
                           data-employee-id="{{ $item->employee_id }}"
                           data-employer-id="{{ $item->employer_id }}"
                           data-name-th="{{ $item->name_th }}"
                           data-name-en="{{ $item->name_en }}"
                           data-title-th="{{ $item->title_th }}"
                           data-title-en="{{ $item->title_en }}"
                           data-photo="{{ $item->photo_url }}"
                           data-employer-name="{{ $item->company }}"
                           data-passport="{{ $item->passport }}">
                </div>
                @endif
                @endcan

                <div class="flex-shrink-0">
                    <img src="{{ $item->photo_url }}" class="rounded-circle shadow-sm" style="width:44px; height:44px; object-fit:cover;">
                </div>

                <div class="flex-grow-1" style="min-width:0;">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                        <div>
                            <span class="badge {{ $badgeClass }} mb-1">{{ $item->source_label }}</span>
                            <div class="fw-bold d-flex align-items-center gap-1">
                                <span>{{ $item->title_en }} {{ $item->name_en ?: '-' }}</span>
                                @if($item->employee_id)
                                <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted btn-preview"
                                        data-model-type="employee" data-model-id="{{ $item->employee_id }}"
                                        title="{{ __('Preview Employee') }}">
                                    <i class="bi bi-search" style="font-size:0.8rem;"></i>
                                </button>
                                @endif
                            </div>
                            <div class="text-muted small">{{ $item->title_th }} {{ $item->name_th ?: '-' }}</div>
                            <div class="small text-muted d-flex align-items-center gap-1">
                                <i class="bi bi-building"></i> {{ $item->company }}
                                @if($item->employer_id)
                                <button type="button" class="btn btn-sm btn-link p-0 border-0 text-muted btn-preview"
                                        data-model-type="employer" data-model-id="{{ $item->employer_id }}"
                                        title="{{ __('Preview Employer') }}">
                                    <i class="bi bi-search" style="font-size:0.78rem;"></i>
                                </button>
                                @endif
                            </div>
                            @if($item->appointment_location)
                                <div class="small text-muted"><i class="bi bi-geo-alt"></i> {{ $item->appointment_location }}</div>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="small text-muted mb-1">{{ optional($item->appointment_date)->format('H:i') }}</div>
                            <a href="{{ $item->link }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                {{ __('Go to record') }} <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif
