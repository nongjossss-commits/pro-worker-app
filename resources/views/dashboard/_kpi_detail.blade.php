{{-- Hover detail of a dashboard KPI card (dashboard.blade.php → Bootstrap popover). --}}
<div class="ed-detail-title">{{ $title }}</div>
@foreach($rows as [$label, $value])
    <div class="ed-detail-row"><span>{{ $label }}</span><strong>{{ !empty($money) ? '฿' . number_format($value, 2) : number_format($value) }}</strong></div>
@endforeach
<div class="ed-detail-note">{{ $note }}</div>
