@extends('labor.layout')

@section('title', 'Central Billing - Pro Walker Labour')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">{{ __('Central Billing') }}</h4>
    <div>
        @role('super-admin')
        <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#manageChargeTypesModal">
            <i class="bi bi-tags me-1"></i>{{ __('Manage Charge Types') }}
        </button>
        @endrole
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addChargeModal">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Record Charge') }}
        </button>
    </div>
</div>

@php
    // Precomputed once and reused by every Filed-By searchable dropdown below
    // (identical member list for the Add modal and every Edit modal — only
    // the pre-selected member differs per entry).
    $addMemberGroups = $membersByTeam->map(function ($teamMembers, $teamName) {
        return [
            'team' => $teamName,
            'members' => $teamMembers->where('is_active', true)->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values()->all(),
        ];
    })->filter(fn ($g) => count($g['members']) > 0)->values()->all();

    $editMemberGroups = $membersByTeam->map(function ($teamMembers, $teamName) {
        return [
            'team' => $teamName,
            'members' => $teamMembers->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name . ($m->is_active ? '' : ' (' . __('inactive') . ')'),
            ])->values()->all(),
        ];
    })->values()->all();

    // Filed-By filter needs everyone (any team, active or not) — a historical
    // entry can reference someone since deactivated or moved teams.
    $filterMemberGroups = $allMembersByTeam->map(function ($teamMembers, $teamName) {
        return [
            'team' => $teamName,
            'members' => $teamMembers->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name . ($m->is_active ? '' : ' (' . __('inactive') . ')'),
            ])->values()->all(),
        ];
    })->values()->all();
    $filterSelectedMember = ($filters['member_id'] ?? null)
        ? $allMembersByTeam->flatten()->firstWhere('id', (int) $filters['member_id'])
        : null;

    $hasActiveFilters = collect($filters)->filter()->isNotEmpty();
@endphp

@if($chargeTypeStats->isNotEmpty())
<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-steps me-2"></i>{{ __('Headcount by Charge Type & Nationality') }}</h6>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 320px; width: 100%;">
            <canvas id="chargeTypeNationalityChart"></canvas>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Charge Type') }}</th>
                    <th class="text-end">{{ __('Total') }}</th>
                    <th class="text-end">ลาว</th>
                    <th class="text-end">เมียนมา</th>
                    <th class="text-end">กัมพูชา</th>
                    <th class="text-end">เวียดนาม</th>
                    <th class="text-end text-muted">{{ __('Unspecified') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chargeTypeStats as $stat)
                <tr>
                    <td>{{ $stat['type']->name }}</td>
                    <td class="text-end fw-bold">{{ number_format($stat['total']) }}</td>
                    <td class="text-end">{{ number_format($stat['laos']) }}</td>
                    <td class="text-end">{{ number_format($stat['myanmar']) }}</td>
                    <td class="text-end">{{ number_format($stat['cambodia']) }}</td>
                    <td class="text-end">{{ number_format($stat['vietnam']) }}</td>
                    <td class="text-end text-muted">{{ $stat['unspecified'] > 0 ? number_format($stat['unspecified']) : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<form method="GET" action="{{ route('labor.charges.index') }}" class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small">{{ __('Quick search (Request No.)') }}</label>
                <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('e.g. RE12345') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">{{ __('Team') }}</label>
                <select name="team_id" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}" {{ (string) ($filters['team_id'] ?? '') === (string) $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 position-relative" x-data="filedBySelector(@js($filterMemberGroups), {{ $filters['member_id'] ?? 'null' }}, @js(optional($filterSelectedMember)->name ?? ''))" @click.outside="open = false">
                <label class="form-label small">{{ __('Filed By') }}</label>
                <input type="text" class="form-control" autocomplete="off" placeholder="{{ __('All') }}"
                       x-model="query" @focus="open = true; $el.select()" @input="open = true; selectedId = ''">
                <input type="hidden" name="member_id" :value="selectedId">
                <div class="list-group position-absolute w-100 shadow" style="z-index:1060; max-height:240px; overflow-y:auto;"
                     x-show="open && filteredGroups.length" x-cloak>
                    <template x-for="group in filteredGroups" :key="group.team">
                        <div>
                            <div class="list-group-item list-group-item-secondary small fw-bold py-1" x-text="group.team"></div>
                            <template x-for="member in group.members" :key="member.id">
                                <button type="button" class="list-group-item list-group-item-action py-1" @click="select(member)" x-text="member.name"></button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">{{ __('Charge Type') }}</label>
                <select name="charge_type_id" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach($chargeTypes as $type)
                        <option value="{{ $type->id }}" {{ (string) ($filters['charge_type_id'] ?? '') === (string) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>{{ __('Search') }}</button>
                @if($hasActiveFilters)
                    <a href="{{ route('labor.charges.index') }}" class="btn btn-outline-secondary" title="{{ __('Clear filters') }}"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Team') }}</th>
                    <th>{{ __('Filed By') }}</th>
                    <th>{{ __('Charge Type') }}</th>
                    <th>{{ __('Request No.') }}</th>
                    <th class="text-end">{{ __('Qty') }}</th>
                    <th class="text-end">{{ __('Rate') }}</th>
                    <th class="text-end">{{ __('Amount') }}</th>
                    <th>{{ __('Recorded By') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->entry_date->format('d/m/Y') }}</td>
                    <td>{{ $entry->team->name ?? '-' }}</td>
                    <td>{{ $entry->member->name ?? '-' }}</td>
                    <td>{{ $entry->chargeType->name ?? '-' }}</td>
                    <td>{{ $entry->request_number }}</td>
                    <td class="text-end">{{ $entry->quantity }}</td>
                    <td class="text-end">{{ number_format($entry->unit_rate, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($entry->amount, 2) }}</td>
                    <td class="text-muted small">
                        {{ $entry->creator->name ?? '-' }}
                        @if($entry->updater)
                            <br><span class="fst-italic">{{ __('edited by') }} {{ $entry->updater->name }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#editChargeModal{{ $entry->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('labor.charges.destroy', $entry) }}" class="d-inline"
                              onsubmit="return confirm('{{ __('Remove this charge?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">{{ __('No charges recorded yet.') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
    <div class="card-footer bg-white">
        {{ $entries->links() }}
    </div>
    @endif
</div>

{{-- Edit Charge modals — one per entry. Must live here, outside the
     <table>/<tbody>, not inline inside the row loop: a <div> placed directly
     inside <tbody> is invalid HTML and browsers "foster parent" it, which
     was silently mangling the <form> inside (collapsing it to zero children
     and stranding its own Save button outside of it — Edit Charge could
     never actually save, and the qty×rate live-total script crashed trying
     to bind listeners on the orphaned, now-childless form). --}}
@foreach($entries as $entry)
<div class="modal fade" id="editChargeModal{{ $entry->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.charges.update', $entry) }}" class="charge-form">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Charge') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Charge Type') }}</label>
                        <select name="labor_charge_type_id" class="form-select charge-type-select" required>
                            @foreach($chargeTypes as $type)
                                <option value="{{ $type->id }}" data-rate="{{ $type->rate }}"
                                    {{ $entry->labor_charge_type_id == $type->id ? 'selected' : '' }}
                                    {{ !$type->is_active && $entry->labor_charge_type_id != $type->id ? 'disabled' : '' }}>
                                    {{ $type->name }} ({{ number_format($type->rate, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 position-relative" x-data="filedBySelector(@js($editMemberGroups), {{ $entry->labor_team_member_id ?? 'null' }}, @js(optional($entry->member)->name ?? ''))" @click.outside="open = false">
                        <label class="form-label">{{ __('Filed By (Team Member)') }}</label>
                        <input type="text" class="form-control" autocomplete="off" placeholder="-- {{ __('Select') }} --"
                               x-model="query" @focus="open = true; $el.select()" @input="open = true; selectedId = ''">
                        <input type="hidden" name="labor_team_member_id" :value="selectedId" required>
                        <div class="list-group position-absolute w-100 shadow" style="z-index:1060; max-height:240px; overflow-y:auto;"
                             x-show="open && filteredGroups.length" x-cloak>
                            <template x-for="group in filteredGroups" :key="group.team">
                                <div>
                                    <div class="list-group-item list-group-item-secondary small fw-bold py-1" x-text="group.team"></div>
                                    <template x-for="member in group.members" :key="member.id">
                                        <button type="button" class="list-group-item list-group-item-action py-1" @click="select(member)" x-text="member.name"></button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Request No.') }}</label>
                        <input type="text" name="request_number" class="form-control" value="{{ $entry->request_number }}" required>
                    </div>
                    @php($hasBreakdown = !is_null($entry->qty_laos) || !is_null($entry->qty_myanmar) || !is_null($entry->qty_cambodia) || !is_null($entry->qty_vietnam))
                    <div class="mb-3">
                        <label class="form-label">{{ __('Quantity by Nationality') }}</label>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">ลาว</label>
                                <input type="number" min="0" step="1" name="qty_laos" class="form-control qty-nat-input" placeholder="0" value="{{ $hasBreakdown ? $entry->qty_laos : '' }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">เมียนมา</label>
                                <input type="number" min="0" step="1" name="qty_myanmar" class="form-control qty-nat-input" placeholder="0" value="{{ $hasBreakdown ? $entry->qty_myanmar : '' }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">กัมพูชา</label>
                                <input type="number" min="0" step="1" name="qty_cambodia" class="form-control qty-nat-input" placeholder="0" value="{{ $hasBreakdown ? $entry->qty_cambodia : '' }}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">เวียดนาม</label>
                                <input type="number" min="0" step="1" name="qty_vietnam" class="form-control qty-nat-input" placeholder="0" value="{{ $hasBreakdown ? $entry->qty_vietnam : '' }}">
                            </div>
                        </div>
                        <div class="form-text">{{ __('Total headcount') }}: <span class="fw-bold qty-total-preview">{{ $hasBreakdown ? $entry->quantity : 0 }}</span></div>
                        @unless($hasBreakdown)
                            <div class="form-text text-warning">
                                {{ __('Not yet broken down by nationality') }} — {{ __('current total') }}: <strong>{{ $entry->quantity }}</strong>. {{ __('Fill in the boxes above to record the breakdown, or leave them all blank to keep the total unchanged.') }}
                            </div>
                        @endunless
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="text" name="entry_date" class="form-control js-charge-date" value="{{ $entry->entry_date->format('Y-m-d') }}" required>
                    </div>
                    <div class="alert alert-light border small mb-0">
                        {{ __('Total') }}: <span class="fw-bold amount-preview">{{ number_format($entry->amount, 2) }}</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- Record Charge modal --}}
<div class="modal fade" id="addChargeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.charges.store') }}" class="charge-form">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Record Charge') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Charge Type') }}</label>
                        <select name="labor_charge_type_id" class="form-select charge-type-select" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($chargeTypes->where('is_active', true) as $type)
                                <option value="{{ $type->id }}" data-rate="{{ $type->rate }}">
                                    {{ $type->name }} ({{ number_format($type->rate, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3 position-relative" x-data="filedBySelector(@js($addMemberGroups), null, '')" @click.outside="open = false">
                        <label class="form-label">{{ __('Filed By (Team Member)') }}</label>
                        <input type="text" class="form-control" autocomplete="off" placeholder="-- {{ __('Select') }} --"
                               x-model="query" @focus="open = true; $el.select()" @input="open = true; selectedId = ''">
                        <input type="hidden" name="labor_team_member_id" :value="selectedId" required>
                        <div class="list-group position-absolute w-100 shadow" style="z-index:1060; max-height:240px; overflow-y:auto;"
                             x-show="open && filteredGroups.length" x-cloak>
                            <template x-for="group in filteredGroups" :key="group.team">
                                <div>
                                    <div class="list-group-item list-group-item-secondary small fw-bold py-1" x-text="group.team"></div>
                                    <template x-for="member in group.members" :key="member.id">
                                        <button type="button" class="list-group-item list-group-item-action py-1" @click="select(member)" x-text="member.name"></button>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <div class="form-text">{{ __('Their team is detected automatically.') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Request No.') }}</label>
                        <input type="text" name="request_number" class="form-control" placeholder="{{ __('e.g. RE12345') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Quantity by Nationality') }}</label>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">ลาว</label>
                                <input type="number" min="0" step="1" name="qty_laos" class="form-control qty-nat-input" placeholder="0">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">เมียนมา</label>
                                <input type="number" min="0" step="1" name="qty_myanmar" class="form-control qty-nat-input" placeholder="0">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">กัมพูชา</label>
                                <input type="number" min="0" step="1" name="qty_cambodia" class="form-control qty-nat-input" placeholder="0">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-1">เวียดนาม</label>
                                <input type="number" min="0" step="1" name="qty_vietnam" class="form-control qty-nat-input" placeholder="0">
                            </div>
                        </div>
                        <div class="form-text">{{ __('Total headcount') }}: <span class="fw-bold qty-total-preview">0</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Date') }}</label>
                        <input type="text" name="entry_date" class="form-control js-charge-date" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="alert alert-light border small mb-0">
                        {{ __('Total') }}: <span class="fw-bold amount-preview">0.00</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@role('super-admin')
{{-- Manage Charge Types modal --}}
<div class="modal fade" id="manageChargeTypesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Manage Charge Types') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th class="text-end">{{ __('Rate') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chargeTypes as $type)
                        <tr>
                            <td>{{ $type->name }}</td>
                            <td class="text-end">{{ number_format($type->rate, 2) }}</td>
                            <td class="text-center">
                                @if($type->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#editChargeTypeModal{{ $type->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">{{ __('No charge types yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <hr>

                <form method="POST" action="{{ route('labor.charge-types.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-6">
                        <label class="form-label small">{{ __('New Charge Type Name') }}</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-3">
                        <label class="form-label small">{{ __('Rate (per head)') }}</label>
                        <input type="number" step="0.01" min="0" name="rate" class="form-control" required>
                    </div>
                    <div class="col-3">
                        <button type="submit" class="btn btn-primary w-100">{{ __('Add') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach($chargeTypes as $type)
<div class="modal fade" id="editChargeTypeModal{{ $type->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('labor.charge-types.update', $type) }}">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Charge Type') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ $type->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Rate (per head)') }}</label>
                        <input type="number" step="0.01" min="0" name="rate" class="form-control" value="{{ $type->rate }}" required>
                        <div class="form-text">{{ __('Only affects charges recorded after this change — past entries keep their original rate.') }}</div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="chargeTypeActive{{ $type->id }}" {{ $type->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="chargeTypeActive{{ $type->id }}">{{ __('Active') }}</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach
@endrole

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Defined at top level (not inside DOMContentLoaded) so it already exists as
// a global by the time Alpine's deferred script runs and evaluates the
// x-data="filedBySelector(...)" expressions — deferred scripts execute
// before DOMContentLoaded fires, so anything Alpine needs must be ready
// before that point, not after it.
function filedBySelector(groups, initialId, initialName) {
    return {
        open: false,
        query: initialName || '',
        selectedId: initialId !== null && initialId !== undefined ? String(initialId) : '',
        groups: groups,
        get filteredGroups() {
            const q = this.query.trim().toLowerCase();
            if (!q) return this.groups;
            return this.groups
                .map(function (g) {
                    return { team: g.team, members: g.members.filter(function (m) { return m.name.toLowerCase().includes(q); }) };
                })
                .filter(function (g) { return g.members.length > 0; });
        },
        select(member) {
            this.selectedId = String(member.id);
            this.query = member.name;
            this.open = false;
        },
    };
}

document.addEventListener('DOMContentLoaded', function () {
    @if($chargeTypeStats->isNotEmpty())
    const chargeTypeCtx = document.getElementById('chargeTypeNationalityChart').getContext('2d');
    // Grouped (not stacked) on purpose: stacking squeezes a small
    // nationality's count into a sliver a few pixels tall inside a much
    // bigger total, making it unreadable and basically unclickable. Each bar
    // getting its own full-height column (plus minBarLength so even a count
    // of 1 stays visibly clickable) keeps every value legible regardless of
    // how the totals compare across charge types.
    new Chart(chargeTypeCtx, {
        type: 'bar',
        data: {
            labels: @json($chargeTypeStats->pluck('type.name')),
            datasets: [
                { label: 'ลาว', data: @json($chargeTypeStats->pluck('laos')), backgroundColor: '#2a78d6', minBarLength: 4 },
                { label: 'เมียนมา', data: @json($chargeTypeStats->pluck('myanmar')), backgroundColor: '#eb6834', minBarLength: 4 },
                { label: 'กัมพูชา', data: @json($chargeTypeStats->pluck('cambodia')), backgroundColor: '#1baf7a', minBarLength: 4 },
                { label: 'เวียดนาม', data: @json($chargeTypeStats->pluck('vietnam')), backgroundColor: '#eda100', minBarLength: 4 },
                { label: '{{ __('Unspecified') }}', data: @json($chargeTypeStats->pluck('unspecified')), backgroundColor: '#898781', minBarLength: 4 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { borderDash: [2, 4] } },
            },
            plugins: {
                legend: { position: 'bottom' },
            },
        },
    });
    @endif

    // DD/MM/YYYY everywhere, regardless of the browser/OS locale, whether the
    // user picks from the calendar or types manually. Scoped to .js-charge-date
    // only — this page's own Flatpickr init, deliberately separate from the
    // shared .js-flatpickr convention (which displays Y-m-d) so no other page
    // is affected.
    document.querySelectorAll('.js-charge-date').forEach(function (el) {
        flatpickr(el, {
            locale: 'th',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true,
        });
    });

    // Live "sum of nationality qty x Rate" preview for both the add and every
    // edit form. On an edit form for an entry that hasn't been broken down by
    // nationality yet, all 4 boxes start blank — recalc only ever runs when
    // the user actually touches one of them (never on page load), so the
    // server-rendered existing total stays visible and correct until then.
    document.querySelectorAll('.charge-form').forEach(function (form) {
        const typeSelect = form.querySelector('.charge-type-select');
        const qtyInputs = form.querySelectorAll('.qty-nat-input');
        const preview = form.querySelector('.amount-preview');
        const totalPreview = form.querySelector('.qty-total-preview');
        if (!typeSelect || !qtyInputs.length || !preview) return;

        function recalc() {
            const opt = typeSelect.options[typeSelect.selectedIndex];
            const rate = parseFloat(opt?.dataset.rate || 0);
            let totalQty = 0;
            qtyInputs.forEach(function (inp) { totalQty += parseInt(inp.value || 0, 10) || 0; });
            preview.textContent = (rate * totalQty).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (totalPreview) totalPreview.textContent = totalQty;
        }

        typeSelect.addEventListener('change', recalc);
        qtyInputs.forEach(function (inp) { inp.addEventListener('input', recalc); });
    });

    // Submit both the "Record Charge" and every "Edit Charge" form over AJAX
    // instead of a normal POST. The reason is specifically the duplicate
    // request-number case: a plain form submit reloads the whole page, which
    // wipes out everything else the user already filled in (team member,
    // quantity, date...) just because one field was wrong. Over AJAX we can
    // keep the modal open with all of that intact and just point out the
    // conflicting record.
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.querySelectorAll('.charge-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: new FormData(form),
            })
                .then(function (res) {
                    return res.json().then(function (data) { return { ok: res.ok, status: res.status, data: data }; });
                })
                .then(function (result) {
                    if (result.ok) {
                        window.location.reload();
                        return;
                    }

                    if (submitBtn) submitBtn.disabled = false;

                    if (result.status === 422 && result.data.duplicate) {
                        showDuplicateAlert(result.data.existing);
                        return;
                    }

                    const messages = result.data.errors
                        ? Object.values(result.data.errors).flat()
                        : [result.data.message || '{{ __('Something went wrong. Please try again.') }}'];
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Could not save') }}',
                        html: messages.map(function (m) { return '<div>' + m + '</div>'; }).join(''),
                    });
                })
                .catch(function () {
                    if (submitBtn) submitBtn.disabled = false;
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Network error') }}',
                        text: '{{ __('Please check your connection and try again.') }}',
                    });
                });
        });
    });

    // Duplicate request number. The "Record/Edit Charge" modal underneath is
    // never touched by any choice here — nothing in it is ever closed,
    // reloaded, or cleared. First ask what the user wants: see who/what it
    // was already recorded as, or skip straight back to fixing the number.
    // "See details" opens a second, stacked alert on top of this one; closing
    // that just drops back to the still-open, still-intact entry form —
    // exactly where they'd land from "fix the number" directly.
    function showDuplicateAlert(existing) {
        Swal.fire({
            icon: 'warning',
            title: '{{ __('Duplicate Request No.') }}',
            text: '{{ __('This request number has already been recorded in the system.') }}',
            showDenyButton: true,
            confirmButtonText: '{{ __('View details') }}',
            denyButtonText: '{{ __('Fix the request number') }}',
            reverseButtons: true,
        }).then(function (result) {
            if (!result.isConfirmed) return; // "fix the number" or dismissed — form is untouched, just type the fix

            Swal.fire({
                icon: 'info',
                title: '{{ __('Recorded As') }}',
                html: '<div class="text-start">' +
                      '<div><b>{{ __('Team') }}:</b> ' + existing.team + '</div>' +
                      '<div><b>{{ __('Filed By') }}:</b> ' + existing.filed_by + '</div>' +
                      '<div><b>{{ __('Charge Type') }}:</b> ' + existing.charge_type + '</div>' +
                      '<div><b>{{ __('Quantity') }}:</b> ' + existing.quantity + '</div>' +
                      '<div><b>{{ __('Amount') }}:</b> ' + existing.amount + '</div>' +
                      '<div><b>{{ __('Date') }}:</b> ' + existing.date + '</div>' +
                      '</div>',
                confirmButtonText: '{{ __('Back to editing') }}',
            });
            // Confirming just closes this second alert — the entry form
            // underneath was never hidden, navigated away from, or reset.
        });
    }
});
</script>
@endpush
@endsection
