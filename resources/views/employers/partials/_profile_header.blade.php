{{--
    Employer profile header above the record tabs (preview). Same look as
    employees/partials/_profile_header; the company-certificate expiry badge
    uses the same colours (red = expired, yellow = within 30 days, green =
    valid, grey = no date).

    @include('employers.partials._profile_header', ['employer' => $employer, 'stats' => $stats ?? null])
--}}
@php
    $certExpiry = $employer->employer_doc_company_expiry ? \Carbon\Carbon::parse($employer->employer_doc_company_expiry)->startOfDay() : null;
    if (!$certExpiry) {
        $certState = 'is-none'; $certNote = null;
    } else {
        $days = (int) now()->startOfDay()->diffInDays($certExpiry, false);
        [$certState, $certNote] = match (true) {
            $days < 0 => ['is-expired', __('Expired')],
            $days <= 30 => ['is-soon', __(':days days left', ['days' => $days])],
            default => ['is-ok', null],
        };
    }
    $nameTh = trim((string) $employer->employerNameTh);
    $nameEn = trim((string) $employer->employerNameEn);
@endphp
<div class="rec-profile">
    <div class="rec-profile-photo rec-profile-avatar"><i class="bi bi-building"></i></div>
    <div class="rec-profile-main">
        <h5 class="rec-profile-name">{{ $nameTh ?: ($nameEn ?: '-') }}</h5>
        @if($nameTh && $nameEn)
            <div class="rec-profile-sub">{{ $nameEn }}</div>
        @endif
        <div class="rec-profile-meta">
            @if($employer->employerId)
                <span><i class="bi bi-hash me-1"></i>{{ $employer->employerId }}</span>
            @endif
            @if($employer->employerTaxId)
                <span><i class="bi bi-upc me-1"></i>{{ $employer->employerTaxId }}</span>
            @endif
            @if($employer->employerPhone)
                <span><i class="bi bi-telephone me-1"></i>{{ $employer->employerPhone }}</span>
            @endif
            @if($employer->assignedStaff?->name)
                <span><i class="bi bi-person-badge me-1"></i>{{ $employer->assignedStaff->name }}</span>
            @endif
        </div>
    </div>
    <div class="rec-profile-badges">
        @if($stats)
            <span class="rec-expiry is-none"><i class="bi bi-people"></i>{{ __('Total Workers') }} <span>{{ $stats->total ?? 0 }}</span></span>
        @endif
        <span class="rec-expiry {{ $certState }}" title="{{ __('Expiry Date (Certificate)') }}{{ $certNote ? ' — ' . $certNote : '' }}">
            <i class="bi bi-file-earmark-text"></i>{{ __('Expiry Date (Certificate)') }}
            <span>{{ $certExpiry ? $certExpiry->format('d/m/Y') : '-' }}</span>
            @if($certNote)<small>({{ $certNote }})</small>@endif
        </span>
    </div>
</div>
