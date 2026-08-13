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
             data-employee-name-th="{{ strtolower($item->name ?? '') }}"
             data-employer-name="{{ strtolower($item->company ?? '') }}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge {{ $badgeClass }} mb-1">{{ $item->source_label }}</span>
                    <div class="fw-bold">{{ $item->name }}</div>
                    <div class="small text-muted">{{ $item->company }}</div>
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
    @endforeach
</div>
@endif
