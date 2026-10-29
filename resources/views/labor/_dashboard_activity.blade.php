{{--
    Recent activity as a timeline (labor/dashboard) — same rows as before:
    date, team (overview only), type, description, amount.
    @include('labor._dashboard_activity', ['items' => $recentActivity, 'showTeam' => true])
--}}
@php
    $activityLook = [
        'bill' => ['icon' => 'bi-receipt', 'tone' => 'var(--bs-primary)', 'badge' => 'bg-primary'],
        'payment' => ['icon' => 'bi-cash-coin', 'tone' => '#10b981', 'badge' => 'bg-success'],
        'charge' => ['icon' => 'bi-plus-circle', 'tone' => '#f59e0b', 'badge' => 'bg-warning text-dark'],
    ];
@endphp
<div class="ld-timeline">
    @forelse($items as $item)
        @php $look = $activityLook[$item['type']] ?? $activityLook['charge']; @endphp
        <div class="ld-tl-item">
            <span class="ld-tl-icon" style="--tone: {{ $look['tone'] }};"><i class="bi {{ $look['icon'] }}"></i></span>
            <div class="flex-grow-1 ld-min-w-0">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge {{ $look['badge'] }}">{{ $item['label'] }}</span>
                    @if(($showTeam ?? false) && isset($item['team']))
                        <a href="{{ $item['link'] }}" class="fw-semibold text-decoration-none">{{ $item['team'] }}</a>
                    @endif
                    <span class="ld-tl-date"><i class="bi bi-calendar3 me-1"></i>{{ optional($item['date'])->format('d/m/Y') }}</span>
                </div>
                <div class="ld-tl-desc text-truncate" title="{{ $item['description'] }}">{{ $item['description'] ?: '-' }}</div>
            </div>
            <div class="ld-tl-amount {{ $item['type'] === 'payment' ? 'text-success' : '' }}">{{ number_format(abs($item['amount']), 2) }}</div>
        </div>
    @empty
        <div class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>{{ __('No activity yet.') }}</div>
    @endforelse
</div>
