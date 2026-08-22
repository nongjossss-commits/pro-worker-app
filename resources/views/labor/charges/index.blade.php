@extends('labor.layout')

@section('title', 'Central Billing - Pro Walker Labor')

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
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Filed By (Team Member)') }}</label>
                                        <select name="labor_team_member_id" class="form-select" required>
                                            <option value="">-- {{ __('Select') }} --</option>
                                            @foreach($membersByTeam as $teamName => $teamMembers)
                                                <optgroup label="{{ $teamName }}">
                                                    @foreach($teamMembers as $member)
                                                        <option value="{{ $member->id }}" {{ $entry->labor_team_member_id == $member->id ? 'selected' : '' }}>
                                                            {{ $member->name }}{{ $member->is_active ? '' : ' (' . __('inactive') . ')' }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('Request No.') }}</label>
                                        <input type="text" name="request_number" class="form-control" value="{{ $entry->request_number }}" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <label class="form-label">{{ __('Quantity') }}</label>
                                            <input type="number" min="1" step="1" name="quantity" class="form-control qty-input" value="{{ $entry->quantity }}" required>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <label class="form-label">{{ __('Date') }}</label>
                                            <input type="date" name="entry_date" class="form-control" value="{{ $entry->entry_date->format('Y-m-d') }}" required>
                                        </div>
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
                    <div class="mb-3">
                        <label class="form-label">{{ __('Filed By (Team Member)') }}</label>
                        <select name="labor_team_member_id" class="form-select" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($membersByTeam as $teamName => $teamMembers)
                                @php($activeInTeam = $teamMembers->where('is_active', true))
                                @if($activeInTeam->isNotEmpty())
                                    <optgroup label="{{ $teamName }}">
                                        @foreach($activeInTeam as $member)
                                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('Their team is detected automatically.') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Request No.') }}</label>
                        <input type="text" name="request_number" class="form-control" placeholder="{{ __('e.g. RE12345') }}" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">{{ __('Quantity') }}</label>
                            <input type="number" min="1" step="1" name="quantity" class="form-control qty-input" value="1" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">{{ __('Date') }}</label>
                            <input type="date" name="entry_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Live "Qty x Rate" preview for both the add and every edit form.
    document.querySelectorAll('.charge-form').forEach(function (form) {
        const typeSelect = form.querySelector('.charge-type-select');
        const qtyInput = form.querySelector('.qty-input');
        const preview = form.querySelector('.amount-preview');

        function recalc() {
            const opt = typeSelect.options[typeSelect.selectedIndex];
            const rate = parseFloat(opt?.dataset.rate || 0);
            const qty = parseInt(qtyInput.value || 0, 10);
            preview.textContent = (rate * qty).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        typeSelect.addEventListener('change', recalc);
        qtyInput.addEventListener('input', recalc);
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
