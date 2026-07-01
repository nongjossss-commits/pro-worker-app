@php
    $snap = \App\Services\ContractStatusService::snapshot();
    $mode = $snap['mode'];

    $show = in_array($mode, [
        \App\Services\ContractStatusService::MODE_READ_ONLY,
        \App\Services\ContractStatusService::MODE_GRACE,
    ], true) || \App\Services\ContractStatusService::isNearExpiry();

    // Only Super Admin sees the banner — customer-facing accounts (admin, staff,
    // caretaker, employer) should never see contract/license state exposed on
    // screen. The read-only enforcement itself still applies silently to those
    // roles via EnforceContractStatus middleware.
    if (!$show || !auth()->check() || !auth()->user()->hasRole('super-admin')) return;

    // Style per mode
    $styles = match ($mode) {
        \App\Services\ContractStatusService::MODE_READ_ONLY => [
            'class' => 'bg-danger text-white',
            'icon' => 'bi-lock-fill',
            'label' => 'READ-ONLY MODE — ระบบดูอย่างเดียว',
            'message' => 'สัญญาการใช้งานสิ้นสุดแล้ว โปรดติดต่อผู้ให้บริการเพื่อต่ออายุ',
        ],
        \App\Services\ContractStatusService::MODE_GRACE => [
            'class' => 'bg-warning text-dark',
            'icon' => 'bi-clock-history',
            'label' => 'ระบบเปิดใช้ชั่วคราว',
            'message' => 'ใช้งานได้ถึง ' . \Carbon\Carbon::parse($snap['grace_end'])->format('d/m/Y')
                . ' (คงเหลือ ' . $snap['days_remaining'] . ' วัน)',
        ],
        default => [
            'class' => 'bg-info text-dark',
            'icon' => 'bi-exclamation-triangle-fill',
            'label' => 'สัญญาใกล้หมดอายุ',
            'message' => 'สัญญาจะหมดในอีก ' . $snap['days_remaining'] . ' วัน (' .
                \Carbon\Carbon::parse($snap['effective_end'])->format('d/m/Y') . ') โปรดต่ออายุก่อนกำหนด',
        ],
    };
@endphp

<div class="contract-status-banner {{ $styles['class'] }} py-2 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2"
     style="border-bottom: 2px solid rgba(0,0,0,0.1); font-size: 0.9rem;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi {{ $styles['icon'] }}" style="font-size: 1.15rem;"></i>
        <strong>{{ $styles['label'] }}</strong>
        <span class="ms-1">— {{ $styles['message'] }}</span>
    </div>
    @hasrole('super-admin')
        <a href="{{ route('super-admin.settings.index', ['tab' => 'program-pricelist']) }}#sub-contract-status"
           class="btn btn-sm btn-dark">
            <i class="bi bi-gear-fill me-1"></i> จัดการสัญญา
        </a>
    @endhasrole
</div>
