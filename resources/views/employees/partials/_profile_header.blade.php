{{--
    Employee profile header above the record tabs (edit form + preview).
    Expiry badges: red = expired, yellow = expires within 30 days (90-day
    report: 15 days), green = valid, grey = no date recorded.

    @include('employees.partials._profile_header', ['employee' => $employee, 'mirrorPhotoId' => 'edit_employeePhotoPreview'])
    'photoLink' => true makes the photo open full size (preview).
    'editUrl' => edit page link → shows an Edit button (previews).
    'details' => true (previews): the personal details (employer, names, date of
      birth, age, gender, nationality, and height, weight, parents when
      recorded) are shown here with a copy button each, so the
      preview doesn't repeat them below.
--}}
@php
    $today = now()->startOfDay();
    $expiryBadges = collect([
        ['label' => __('Passport'), 'date' => $employee->passportExpiryDate, 'soon' => 30, 'icon' => 'bi-passport'],
        ['label' => __('Visa'), 'date' => $employee->visaExpiryDate, 'soon' => 30, 'icon' => 'bi-airplane'],
        ['label' => 'Work Permit', 'date' => $employee->workPermitExpiryDate, 'soon' => 30, 'icon' => 'bi-briefcase'],
        ['label' => __('90-Day Report'), 'date' => $employee->ninetyDayReportDate, 'soon' => 15, 'icon' => 'bi-calendar-check'],
    ])->map(function ($b) use ($today) {
        if (!$b['date']) {
            return $b + ['state' => 'is-none', 'note' => null];
        }
        $days = (int) $today->diffInDays($b['date']->copy()->startOfDay(), false);
        if ($days < 0) {
            return $b + ['state' => 'is-expired', 'note' => __('Expired')];
        }
        if ($days <= $b['soon']) {
            return $b + ['state' => 'is-soon', 'note' => __(':days days left', ['days' => $days])];
        }
        return $b + ['state' => 'is-ok', 'note' => null];
    });

    $nameEn = trim(($employee->employeeTitleEn ?? '') . ' ' . ($employee->getRawOriginal('employeeNameEn') ?? ''));
    $nameTh = trim(($employee->employeeTitleTh ?? '') . ' ' . ($employee->employeeNameTh ?? ''));
    $employerName = $employee->employer->employerNameTh ?? ($employee->employer->employerNameEn ?? null);

    if (!empty($details)) {
        // Copy button after a value (handler: layouts/_app_scripts, .pv-copy).
        $copyLabel = e(__('Copy'));
        $copyBtn = fn ($v) => '<button type="button" class="pv-copy" data-copy="' . e($v) . '" title="' . $copyLabel . '" aria-label="' . $copyLabel . '"><i class="bi bi-copy"></i></button>';
        $employerTh = $employee->employer->employerNameTh ?? null;
        $employerEn = $employee->employer->employerNameEn ?? null;
        $employerFull = ($employerTh && $employerEn && trim($employerEn) !== '') ? $employerTh . ' (' . $employerEn . ')' : ($employerTh ?: $employerEn);
        // [icon, label, value, always shown?]
        $facts = [
            ['bi-building', __('Employer (Affiliation)'), $employerFull, true],
            ['bi-cake2', __('Date of Birth (Full)'), $employee->employeeDob?->format('d/m/Y'), true],
            [null, __('Age'), $employee->age, true],
            ['bi-gender-ambiguous', __('Gender'), $employee->gender, true],
            ['bi-flag', __('Nationality'), $employee->employeeNationality ? \App\Helpers\NationalityList::label($employee->employeeNationality) : null, true],
            [null, __('Name Suffix (EN)'), $employee->name_suffix, false],
            ['bi-rulers', __('Height'), $employee->height ? $employee->height . ' cm' : null, false],
            [null, __('Weight'), $employee->weight ? $employee->weight . ' kg' : null, false],
            ['bi-person', __('Father Name'), $employee->father_name, false],
            ['bi-person', __('Mother Name'), $employee->mother_name, false],
        ];
    }
@endphp
<div class="rec-profile">
    @if(!empty($photoLink))
        <a href="{{ $employee->photo_url }}" target="_blank" rel="noopener noreferrer">
            <img src="{{ $employee->photo_url }}" class="rec-profile-photo" alt="{{ __('Employee Photo') }}">
        </a>
    @else
        <img src="{{ $employee->photo_url }}" class="rec-profile-photo" alt="{{ __('Employee Photo') }}"
             @if(!empty($mirrorPhotoId)) data-rec-mirror="{{ $mirrorPhotoId }}" @endif>
    @endif
    @if(!empty($details))
    <div class="rec-profile-main">
        <h5 class="rec-profile-name">{{ $nameEn ?: ($nameTh ?: '-') }}@if($nameEn ?: $nameTh){!! $copyBtn($nameEn ?: $nameTh) !!}@endif</h5>
        @if($nameTh && $nameEn)
            <div class="rec-profile-sub">{{ $nameTh }}{!! $copyBtn($nameTh) !!}</div>
        @endif
        <div class="rec-profile-facts">
            @foreach($facts as [$icon, $label, $value, $always])
                @continue(!$always && ($value === null || $value === ''))
                <span class="rec-fact">
                    @if($icon)<i class="bi {{ $icon }}"></i>@endif
                    <span class="rec-fact-label">{{ $label }}</span>
                    @if($value === null || $value === '')
                        <span class="rec-fact-value text-muted-empty">-</span>
                    @else
                        <span class="rec-fact-value">{{ $value }}</span>{!! $copyBtn($value) !!}
                    @endif
                </span>
            @endforeach
        </div>
    </div>
    @else
    <div class="rec-profile-main">
        <h5 class="rec-profile-name">{{ $nameEn ?: ($nameTh ?: '-') }}</h5>
        @if($nameTh && $nameEn)
            <div class="rec-profile-sub">{{ $nameTh }}</div>
        @endif
        <div class="rec-profile-meta">
            @if($employerName)
                <span><i class="bi bi-building me-1"></i>{{ $employerName }}</span>
            @endif
            @if($employee->employeeDob)
                {{-- Date of birth + age right under the name (edit form + preview). --}}
                <span title="{{ __('Date of Birth') }}"><i class="bi bi-cake2 me-1"></i>{{ $employee->employeeDob->format('d/m/Y') }}@if($employee->age !== null) ({{ __('Age') }} {{ $employee->age }})@endif</span>
            @endif
            @if($employee->employeeNationality)
                <span><i class="bi bi-flag me-1"></i>{{ \App\Helpers\NationalityList::label($employee->employeeNationality) }}</span>
            @endif
            @if($employee->employeePassport)
                <span><i class="bi bi-passport me-1"></i>{{ $employee->employeePassport }}</span>
            @endif
        </div>
    </div>
    @endif
    <div class="rec-profile-badges">
        @if(!empty($editUrl))
            {{-- Preview → edit → back to this preview, refreshed (partials/_record_tabs: data-preview-edit). --}}
            <div class="rec-profile-actions">
                <a href="{{ $editUrl }}" class="btn btn-sm btn-primary" data-preview-edit data-type="employee" data-id="{{ $employee->id }}">
                    <i class="bi bi-pencil-square me-1"></i>{{ __('Edit') }}
                </a>
            </div>
        @endif
        @foreach($expiryBadges as $b)
            <span class="rec-expiry {{ $b['state'] }}" title="{{ $b['label'] }}{{ $b['note'] ? ' — ' . $b['note'] : '' }}">
                <i class="bi {{ $b['icon'] }}"></i>{{ $b['label'] }}
                <span>{{ $b['date'] ? $b['date']->format('d/m/Y') : '-' }}</span>
                @if($b['note'])<small>({{ $b['note'] }})</small>@endif
            </span>
        @endforeach
    </div>
</div>
