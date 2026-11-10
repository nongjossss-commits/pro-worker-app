@extends('labor.layout')

@section('title', __('Contract History'))

@section('content')
@include('labor.contracts._pwc_styles')
<div class="container-fluid pwc">
    <div class="pwc-hero">
        <div class="pwc-hero-main">
            <div class="pwc-hero-icon"><i class="bi bi-journal-text"></i></div>
            <div>
                <h4 class="pwc-hero-title">{{ __('Contract History') }}</h4>
                @if(app()->getLocale() !== 'th')<p class="pwc-hero-sub">ประวัติการเบิกสัญญา</p>@endif
            </div>
        </div>
        <div class="pwc-hero-actions">
            <a href="{{ route('labor.contracts.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Issue Contract') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Total/complete/incomplete counts — strictly bounded to this
         viewer's own access tier (own issuances only / whole own team /
         every team broken out) — see
         LaborContractController::buildCompletionSummary(). --}}
    @php
        // All-teams view: each card toggles that team in the filter (several
        // can be on at once; click again = off). The grid keeps at most
        // $maxRows rows — more teams make the cards narrower (down to a
        // minimum width, then it wraps) and the text inside scales with the
        // card width (container query units) and wraps instead of overflowing.
        $teamFilter = $summary['scope'] === 'all_teams';
        $selectedTeamIds = $selectedTeamIds ?? [];
        $maxRows = 2;
        $summaryCols = $teamFilter ? max(4, (int) ceil(count($summary['rows']) / $maxRows)) : max(1, min(3, count($summary['rows'])));
        $keepQuery = request()->except(['team_id', 'team_ids', 'page']);
        $teamToggleUrl = function ($id) use ($selectedTeamIds, $keepQuery) {
            $ids = in_array($id, $selectedTeamIds, true)
                ? array_values(array_diff($selectedTeamIds, [$id]))
                : array_merge($selectedTeamIds, [$id]);
            return route('labor.contracts.index', $keepQuery + ($ids ? ['team_ids' => $ids] : []));
        };
    @endphp
    @push('styles')
    <style>
        .ct-summary {
            display: grid; gap: .5rem; margin-bottom: .5rem;
            grid-template-columns: repeat(auto-fill, minmax(max(8.5rem, calc((100% - (var(--ct-cols) - 1) * .5rem) / var(--ct-cols))), 1fr));
        }
        .ct-team {
            container-type: inline-size; display: block; min-width: 0; height: 100%;
            color: inherit; text-decoration: none;
            border: 1px solid transparent; border-radius: .5rem;
            transition: border-color .12s, box-shadow .12s, background-color .12s;
        }
        .ct-team .card-body { padding: clamp(.35rem, 4cqi, .6rem) clamp(.5rem, 5cqi, 1rem); }
        .ct-team-name { font-size: clamp(.72rem, 5.5cqi, .9rem); color: var(--bs-secondary-color); line-height: 1.2; overflow-wrap: anywhere; display: flex; align-items: flex-start; gap: .3rem; }
        .ct-team-total { font-size: clamp(1rem, 10cqi, 1.3rem); font-weight: 700; line-height: 1.2; }
        .ct-team-unit { font-size: clamp(.68rem, 5cqi, .85rem); color: var(--bs-secondary-color); }
        .ct-team-stats { display: flex; flex-wrap: wrap; gap: 0 .6rem; font-size: clamp(.66rem, 5cqi, .85rem); line-height: 1.35; }
        .ct-team-stats > span { white-space: nowrap; }
        a.ct-team { cursor: pointer; }
        a.ct-team:hover { color: inherit; border-color: rgba(var(--bs-primary-rgb), .45); }
        a.ct-team:focus-visible { outline: 2px solid var(--bs-primary); outline-offset: 2px; }
        .ct-team.is-on { border-color: var(--bs-primary); box-shadow: 0 0 0 1px var(--bs-primary) inset; background-color: rgba(var(--bs-primary-rgb), .08); }
        .ct-team-check { margin-left: auto; color: var(--bs-primary); visibility: hidden; flex: none; }
        .ct-team.is-on .ct-team-check { visibility: visible; }
        .ct-team-hint { font-size: .8rem; color: var(--bs-secondary-color); }

        /* Contract cards: the full table needs ~1250px; anything narrower
           (phone, tablet, laptop with the sidebar) shows one card per contract
           — number + status on top, the details as "label … value", then the
           signed-copy slot across the card and the buttons at the bottom.
           Decided by the list's own width (container query), not the screen. */
        .ct-list { container-type: inline-size; }
        .ct-selectall { display: inline-flex; align-items: center; gap: .5rem; cursor: pointer; }
        .ct-selectall-text { display: none; }
        @container (max-width: 1249.98px) {
            .ct-list .table-responsive { overflow: visible; }
            .ct-table { display: block; width: 100%; }
            .ct-table > thead { display: block; }
            .ct-table > thead > tr { display: flex; }
            .ct-table > thead > tr > th { display: none; }
            .ct-table > thead > tr > th:first-child {
                display: block; width: 100% !important; padding: .55rem .9rem;
                font-weight: 500; font-size: .875rem; border-bottom: 1px solid var(--bs-border-color);
            }
            .ct-table .ct-selectall-text { display: inline; }
            .ct-table > tbody {
                display: grid; grid-template-columns: minmax(0, 1fr); gap: .75rem; padding: .75rem;
            }
            .ct-table > tbody > tr {
                display: grid; grid-template-columns: auto minmax(0, 1fr) auto; align-items: center;
                column-gap: 0; padding: .7rem .85rem .6rem; min-width: 0;
                border: 1px solid var(--bs-border-color); border-radius: .6rem;
                background: var(--bs-body-bg); box-shadow: 0 1px 2px rgba(0, 0, 0, .06);
            }
            .ct-table > tbody > tr:has(.contract-checkbox:checked) { border-color: var(--bs-primary); box-shadow: 0 0 0 1px var(--bs-primary) inset; }
            .ct-table > tbody > tr > td {
                display: block; grid-column: 1 / -1; min-width: 0; padding: .2rem 0;
                border: 0; background: transparent !important; box-shadow: none !important; text-align: left;
            }
            /* top line: checkbox · contract no. · status */
            .ct-table > tbody > tr > td:is(.ct-cb, .ct-no, .ct-status) {
                grid-row: 1; align-self: stretch; display: flex; align-items: center;
                padding: 0 0 .5rem; margin-bottom: .3rem;
                border-bottom: 1px solid var(--bs-border-color-translucent);
            }
            .ct-table > tbody > tr > td.ct-cb { grid-column: 1; }
            .ct-table > tbody > tr > td.ct-no { grid-column: 2; padding-left: .6rem; padding-right: .5rem; font-weight: 700; font-size: .95rem; overflow-wrap: anywhere; }
            .ct-table > tbody > tr > td.ct-status { grid-column: 3; justify-content: flex-end; }
            .ct-table > tbody > tr > td.ct-field {
                display: flex; justify-content: space-between; align-items: baseline; gap: .75rem;
                text-align: right; white-space: normal !important; overflow-wrap: anywhere; font-size: .9rem;
            }
            .ct-table > tbody > tr > td.ct-field::before {
                content: attr(data-label); flex: 0 0 auto; max-width: 45%;
                color: var(--bs-secondary-color); font-size: .78rem; font-weight: 500; text-align: left;
            }
            .ct-table > tbody > tr > td.ct-signed { padding-top: .5rem; }
            .ct-table > tbody > tr > td.ct-signed .att-card { width: 100%; }
            .ct-table > tbody > tr > td.ct-actions {
                display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .35rem;
                margin-top: .4rem; padding-top: .5rem;
                border-top: 1px solid var(--bs-border-color-translucent);
            }
            .ct-table > tbody > tr.ct-empty { display: block; }
            .ct-table > tbody > tr.ct-empty > td { text-align: center; }
        }
        /* two cards side by side once there is room (tablet / small laptop) */
        @container (min-width: 640px) and (max-width: 1249.98px) {
            .ct-table > tbody { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .ct-table > tbody > tr.ct-empty { grid-column: 1 / -1; }
        }
    </style>
    @endpush
    <div class="ct-summary" style="--ct-cols: {{ $summaryCols }}">
        @foreach($summary['rows'] as $row)
            @php $isOn = $teamFilter && isset($row['id']) && in_array($row['id'], $selectedTeamIds, true); @endphp
            @if($teamFilter && isset($row['id']))
                <a href="{{ $teamToggleUrl($row['id']) }}" class="ct-team card shadow-sm {{ $isOn ? 'is-on' : '' }}" aria-pressed="{{ $isOn ? 'true' : 'false' }}" data-team-card="{{ $row['id'] }}"
                   title="{{ $isOn ? __('Click again to remove this team from the filter') : __('Click to filter by this team') }}">
            @else
                <div class="ct-team card shadow-sm">
            @endif
                <div class="card-body">
                    <div class="ct-team-name"><span>{{ $row['label'] }}</span>@if($teamFilter)<i class="bi bi-check-circle-fill ct-team-check"></i>@endif</div>
                    <div class="d-flex align-items-baseline flex-wrap column-gap-2">
                        <span class="ct-team-total">{{ $row['total'] }}</span>
                        <span class="ct-team-unit">{{ __('issued') }}</span>
                    </div>
                    <div class="pwc-progress" title="{{ $row['total'] > 0 ? round($row['complete'] / $row['total'] * 100) : 0 }}%"><span style="width: {{ $row['total'] > 0 ? round($row['complete'] / $row['total'] * 100, 1) : 0 }}%"></span></div>
                    <div class="ct-team-stats">
                        <span class="text-success"><i class="bi bi-patch-check-fill"></i> {{ $row['complete'] }} {{ __('complete') }}</span>
                        <span class="text-muted"><i class="bi bi-hourglass-split"></i> {{ $row['total'] - $row['complete'] }} {{ __('awaiting signature') }}</span>
                    </div>
                </div>
            @if($teamFilter && isset($row['id']))
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>
    @if($teamFilter)
        <div class="ct-team-hint mb-3 d-flex flex-wrap align-items-center gap-2">
            <span><i class="bi bi-hand-index-thumb me-1"></i>{{ __('Click a team card to filter (several allowed) — click it again to remove') }}</span>
            @if($selectedTeamIds)
                <a href="{{ route('labor.contracts.index', $keepQuery) }}" class="btn btn-sm btn-outline-secondary py-0">
                    <i class="bi bi-x-lg me-1"></i>{{ __('Clear team filter') }} ({{ count($selectedTeamIds) }})
                </a>
            @endif
        </div>
    @else
        <div class="mb-3"></div>
    @endif

    <div class="card pwc-panel pwc-filter mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('labor.contracts.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">{{ __('Search') }}</label>
                    <x-live-search-filter
                        endpoint="{{ route('labor.contracts.preview_search') }}"
                        name="q"
                        placeholder="{{ __('Employer name, employer ID or contract number...') }}"
                        value="{{ request('q') }}"
                        class="form-control"
                    />
                </div>
                @if($seesAllTeams)
                    <div class="col-md-3">
                        <label class="form-label small">{{ __('Team') }}</label>
                        {{-- Same filter as the team cards above: one team here, or the
                             cards' multi-team pick (kept in hidden team_ids[] fields;
                             picking here replaces it). --}}
                        <select name="team_id" class="form-select" data-team-select>
                            <option value="">{{ __('All Teams') }}</option>
                            @if(count($selectedTeamIds) > 1)
                                <option value="__multi" selected>{{ __('Several teams (:count)', ['count' => count($selectedTeamIds)]) }}</option>
                            @endif
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}" @selected(count($selectedTeamIds) === 1 && $selectedTeamIds[0] === $team->id)>{{ $team->name }}</option>
                            @endforeach
                        </select>
                        @if(count($selectedTeamIds) > 1)
                            @foreach($selectedTeamIds as $tid)
                                <input type="hidden" name="team_ids[]" value="{{ $tid }}" data-team-ids>
                            @endforeach
                        @endif
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label small">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        <option value="complete" @selected(request('status') === 'complete')>{{ __('Complete Contract') }}</option>
                        <option value="incomplete" @selected(request('status') === 'incomplete')>{{ __('Awaiting signed copy') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i>{{ __('Search') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($searchSummary)
        {{-- Searching for an employer → its contracts per team (signed / waiting) --}}
        <div class="card pwc-panel mb-3 ct-search-summary">
            <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2 small">
                <span class="fw-semibold"><i class="bi bi-search me-1"></i>{{ __('":q" — :count contract(s)', ['q' => request('q'), 'count' => number_format($searchSummary['total'])]) }}</span>
                <span class="badge rounded-pill text-bg-success">{{ __(':n complete', ['n' => $searchSummary['signed']]) }}</span>
                <span class="badge rounded-pill text-bg-secondary">{{ __(':n awaiting signature', ['n' => $searchSummary['total'] - $searchSummary['signed']]) }}</span>
                @foreach($searchSummary['teams'] as $t)
                    <span class="badge rounded-pill bg-body-tertiary text-body border">{{ $t['name'] }}: {{ $t['total'] }} <span class="text-success">({{ $t['signed'] }} ✓)</span></span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ct-list: when the list is narrower than the full table (tablet, phone,
         small laptop with the sidebar open) each contract becomes a card —
         see the "Contract cards" styles above. data-mobile-table="scroll":
         this page has its own cards, not the generic phone cards. --}}
    <div class="card pwc-panel ct-list">
        <div class="table-responsive" data-mobile-table="scroll">
            <table class="table table-hover align-middle mb-0 ct-table pwc-table">
                <thead>
                    <tr>
                        <th style="width: 1%;">
                            <label class="ct-selectall mb-0">
                                <input type="checkbox" class="form-check-input" id="contractSelectAll" title="{{ __('Select all') }}">
                                <span class="ct-selectall-text">{{ __('Select all') }}</span>
                            </label>
                        </th>
                        <th>{{ __('Contract No.') }}</th>
                        <th>{{ __('Employer') }}</th>
                        <th>{{ __('Template') }}</th>
                        <th>{{ __('Issued By') }}</th>
                        <th>{{ __('Team') }}</th>
                        <th>{{ __('Issued At') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th style="min-width: 220px;">{{ __('Signed Copy') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract)
                    <tr>
                        <td class="ct-cb">
                            {{-- uploadSignedCopy() is gated by assertCanAccessContract()
                                 — the same, looser scope index() itself already uses to
                                 decide which rows appear here at all — so every row a
                                 user can see in this list is one they may also select/
                                 bulk-download and attach a signed copy to. --}}
                            <input type="checkbox" class="form-check-input contract-checkbox"
                                   name="ids[]" value="{{ $contract->id }}"
                                   data-has-signed="{{ $contract->signed_copy_path ? '1' : '0' }}">
                        </td>
                        <td class="ct-no"><span class="pwc-no"><i class="bi bi-hash"></i>{{ $contract->contract_no }}</span></td>
                        <td class="ct-field" data-label="{{ __('Employer') }}">
                            @php
                                $empName = $contract->employer_name_snapshot ?? '-';
                                $empShort = trim(preg_replace('/^(บริษัท|ห้างหุ้นส่วนจำกัด|ห้างหุ้นส่วน|หจก\.?|บจก\.?|Company|Co\.,?)\s*/u', '', $empName)) ?: $empName;
                            @endphp
                            <div class="pwc-who">
                                <span class="pwc-avatar" style="--h: {{ crc32($empName) % 360 }}" aria-hidden="true">{{ mb_substr($empShort, 0, 1) }}</span>
                                <div class="text-start">
                                    <div class="pwc-who-name">{{ $empName }}</div>
                                    @if($contract->employer_ref_snapshot)
                                        <div class="pwc-who-sub">{{ __('Employer ID') }}: {{ $contract->employer_ref_snapshot }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="ct-field" data-label="{{ __('Template') }}"><span class="pwc-meta"><i class="bi bi-file-earmark-text"></i>{{ $contract->template->name ?? '-' }}</span></td>
                        <td class="ct-field" data-label="{{ __('Issued By') }}"><span class="pwc-meta"><i class="bi bi-person-badge"></i><span>{{ $contract->issuer->name ?? '-' }} @if($contract->issuer?->staff_code) <span class="text-body-secondary">({{ $contract->issuer->staff_code }})</span> @endif</span></span></td>
                        <td class="ct-field" data-label="{{ __('Team') }}">@if($contract->team)<span class="pwc-tag"><i class="bi bi-people-fill"></i>{{ $contract->team->name }}</span>@else - @endif</td>
                        <td class="text-nowrap ct-field" data-label="{{ __('Issued At') }}"><span class="pwc-meta"><i class="bi bi-calendar3"></i>{{ $contract->issued_at->format('d/m/Y') }} <span class="text-body-secondary">{{ $contract->issued_at->format('H:i') }}</span></span></td>
                        <td class="ct-status">
                            @if($contract->signed_copy_path)
                                <span class="pwc-status is-done">{{ __('Complete Contract') }}</span>
                            @else
                                <span class="pwc-status is-wait">{{ __('Awaiting signed copy') }}</span>
                            @endif
                        </td>
                        <td class="ct-signed">
                            {{-- Deliberately a PLAIN file picker here, not
                                 <x-file-input-group> (which routes every selection
                                 through the camera-scan/crop tool via
                                 window.interceptFileSelect). That tool needs several
                                 sequential steps confirmed in order (capture/pick ->
                                 crop -> add to list -> finalize) — skipping any one of
                                 them silently leaves the underlying <input> empty with
                                 no error, which is exactly the bug reported here. For
                                 this quick per-row attach (the whole point is speed,
                                 no need to open the contract first), a plain <input
                                 type=file> + normal multipart submit is the simplest,
                                 unconditionally reliable mechanism the web has — the
                                 full scan/crop/rotate tool remains exactly where it
                                 already worked, on the contract's own page
                                 (show.blade.php), for whoever genuinely needs to
                                 photograph a document rather than upload one they
                                 already have as a file. --}}
                            {{-- Same attachment card as the main app, in its plain-picker
                                 form (:scanner="false" — no scan interception, see above).
                                 The Upload button appears once a file is chosen. --}}
                            <form method="POST" action="{{ route('labor.contracts.signed-copy.update', $contract) }}" enctype="multipart/form-data" class="mb-0">
                                @csrf
                                @method('PUT')
                                <x-file-input-group
                                    variant="card"
                                    :scanner="false"
                                    :id="'signed_copy_' . $contract->id"
                                    name="signed_copy"
                                    :label="__('Signed Copy')"
                                    :value="$contract->signed_copy_path"
                                    :pendingHint="__('press Upload to attach it')"
                                    :clearInPending="false"
                                >
                                    <div class="att-when-pending gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" data-att-clear>
                                            <i class="bi bi-x-lg me-1"></i>{{ __('Cancel') }}
                                        </button>
                                        <button type="submit" class="btn btn-success btn-sm flex-fill d-inline-flex justify-content-center align-items-center gap-1">
                                            <i class="bi bi-upload"></i>{{ $contract->signed_copy_path ? __('Replace') : __('Attach') }}
                                        </button>
                                    </div>
                                </x-file-input-group>
                            </form>
                        </td>
                        <td class="text-end ct-actions">
                            <a href="{{ route('labor.contracts.view', $contract) }}" target="_blank" class="btn btn-sm btn-outline-primary pwc-iconbtn" title="{{ __('Preview') }}">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-primary pwc-iconbtn" title="{{ __('Download PDF') }}" data-bs-toggle="modal" data-bs-target="#downloadChoiceModal" data-download-url="{{ route('labor.contracts.download', $contract) }}">
                                <i class="bi bi-download"></i>
                            </button>
                            {{-- Issuer-only, same rule as show.blade.php — see
                                 LaborContractController::assertCanEditContract(). --}}
                            @if(auth()->id() === $contract->issued_by)
                            <a href="{{ route('labor.contracts.edit', $contract) }}" class="btn btn-sm btn-outline-secondary pwc-iconbtn" title="{{ __('Edit') }}">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr class="ct-empty">
                        <td colspan="10" class="text-center text-muted py-5"><i class="bi bi-journal-x d-block fs-1 mb-2 opacity-50"></i>{{ __('No contracts issued yet.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($contracts->hasPages())
        <div class="card-footer">{{ $contracts->links() }}</div>
        @endif
    </div>
</div>

{{-- Floating bulk-selection bar — page-local (not <x-bulk-action-bar>, which
     carries a hardcoded "View Selected" button wired to an Employee-only
     modal function that doesn't exist here). Only one action needed
     (download), so no Actions dropdown either — just the bar itself. --}}
<div id="contractBulkBar" class="align-items-center gap-2 p-2 bg-light border rounded shadow-lg pwc pwc-bulkbar"
     style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1060; width: auto; min-width: 360px;">
    <div class="form-check mb-0 d-inline-block me-2">
        {{ __('Selected') }} (<span id="contractBulkCount">0</span>)
    </div>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#bulkDownloadModal">
        <i class="bi bi-download me-1"></i>{{ __('Download Selected') }}
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="contractBulkClear">{{ __('Clear Selection') }}</button>
</div>

@include('labor.contracts._download_choice_modal')
@include('labor.contracts._bulk_download_modal')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('contractSelectAll');
        const bar = document.getElementById('contractBulkBar');
        const countSpan = document.getElementById('contractBulkCount');
        const clearBtn = document.getElementById('contractBulkClear');

        function checkboxes() {
            return document.querySelectorAll('.contract-checkbox');
        }
        function checked() {
            return document.querySelectorAll('.contract-checkbox:checked');
        }

        function refresh() {
            const count = checked().length;
            countSpan.textContent = count;
            bar.style.display = count > 0 ? 'flex' : 'none';

            if (selectAll) {
                const all = checkboxes();
                selectAll.checked = all.length > 0 && count === all.length;
                selectAll.indeterminate = count > 0 && count < all.length;
            }
        }

        checkboxes().forEach(cb => cb.addEventListener('change', refresh));

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes().forEach(cb => { cb.checked = selectAll.checked; });
                refresh();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                checkboxes().forEach(cb => { cb.checked = false; });
                refresh();
            });
        }

        refresh();

        // Picking a team in the dropdown replaces the cards' multi-team pick.
        const teamSelect = document.querySelector('[data-team-select]');
        if (teamSelect) {
            teamSelect.addEventListener('change', function () {
                if (teamSelect.value !== '__multi') {
                    document.querySelectorAll('[data-team-ids]').forEach(el => el.remove());
                }
            });
        }
    });
</script>
@endpush
@endsection
