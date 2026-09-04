@extends('labor.layout')

@section('title', __('Contract History'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">{{ __('Contract History') }} (ประวัติการเบิกสัญญา)</h4>
        <a href="{{ route('labor.contracts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Issue Contract') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Total/complete/incomplete counts — strictly bounded to this
         viewer's own access tier (own issuances only / whole own team /
         every team broken out) — see
         LaborContractController::buildCompletionSummary(). --}}
    <div class="row g-2 mb-3">
        @foreach($summary['rows'] as $row)
            <div class="col-md-{{ $summary['scope'] === 'all_teams' ? '3' : '4' }}">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="small text-muted text-truncate">{{ $row['label'] }}</div>
                        <div class="d-flex align-items-baseline gap-2">
                            <span class="fs-5 fw-bold">{{ $row['total'] }}</span>
                            <span class="text-muted small">{{ __('issued') }}</span>
                        </div>
                        <div class="small">
                            <span class="text-success"><i class="bi bi-patch-check-fill"></i> {{ $row['complete'] }} {{ __('complete') }}</span>
                            <span class="text-muted ms-2"><i class="bi bi-hourglass-split"></i> {{ $row['total'] - $row['complete'] }} {{ __('awaiting signature') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('labor.contracts.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">{{ __('Search') }}</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="{{ __('Employer name or contract number...') }}">
                </div>
                @if($seesAllTeams)
                    <div class="col-md-3">
                        <label class="form-label small">{{ __('Team') }}</label>
                        <select name="team_id" class="form-select">
                            <option value="">{{ __('All Teams') }}</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}" @selected((string) request('team_id') === (string) $team->id)>{{ $team->name }}</option>
                            @endforeach
                        </select>
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

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 1%;">
                            <input type="checkbox" class="form-check-input" id="contractSelectAll" title="{{ __('Select all') }}">
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
                        <td>
                            {{-- uploadSignedCopy() is gated by assertCanAccessContract()
                                 — the same, looser scope index() itself already uses to
                                 decide which rows appear here at all — so every row a
                                 user can see in this list is one they may also select/
                                 bulk-download and attach a signed copy to. --}}
                            <input type="checkbox" class="form-check-input contract-checkbox"
                                   name="ids[]" value="{{ $contract->id }}"
                                   data-has-signed="{{ $contract->signed_copy_path ? '1' : '0' }}">
                        </td>
                        <td class="font-monospace">{{ $contract->contract_no }}</td>
                        <td>{{ $contract->employer_name_snapshot ?? '-' }}</td>
                        <td>{{ $contract->template->name ?? '-' }}</td>
                        <td>{{ $contract->issuer->name ?? '-' }} @if($contract->issuer?->staff_code) ({{ $contract->issuer->staff_code }}) @endif</td>
                        <td>{{ $contract->team->name ?? '-' }}</td>
                        <td class="text-nowrap">{{ $contract->issued_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($contract->signed_copy_path)
                                <span class="badge bg-success">{{ __('Complete Contract') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('Awaiting signed copy') }}</span>
                            @endif
                        </td>
                        <td>
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
                            <form method="POST" action="{{ route('labor.contracts.signed-copy.update', $contract) }}" enctype="multipart/form-data" class="mb-0">
                                @csrf
                                @method('PUT')
                                @if($contract->signed_copy_path)
                                    <div class="mb-1">
                                        <a href="#" onclick="event.preventDefault(); viewPDF('{{ asset('storage/' . $contract->signed_copy_path) }}', '{{ __('Signed Copy') }}')" class="btn btn-success btn-sm text-white">
                                            <i class="bi bi-eye-fill"></i> {{ __('View') }}
                                        </a>
                                    </div>
                                @endif
                                <div class="input-group input-group-sm">
                                    <input type="file" name="signed_copy" class="form-control form-control-sm @error('signed_copy') is-invalid @enderror" accept="image/*,application/pdf">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-upload me-1"></i>{{ $contract->signed_copy_path ? __('Replace') : __('Attach') }}
                                    </button>
                                </div>
                                @error('signed_copy')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('labor.contracts.view', $contract) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="{{ __('Preview') }}">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-primary" title="{{ __('Download PDF') }}" data-bs-toggle="modal" data-bs-target="#downloadChoiceModal" data-download-url="{{ route('labor.contracts.download', $contract) }}">
                                <i class="bi bi-download"></i>
                            </button>
                            {{-- Issuer-only, same rule as show.blade.php — see
                                 LaborContractController::assertCanEditContract(). --}}
                            @if(auth()->id() === $contract->issued_by)
                            <a href="{{ route('labor.contracts.edit', $contract) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">{{ __('No contracts issued yet.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($contracts->hasPages())
        <div class="card-footer bg-white">{{ $contracts->links() }}</div>
        @endif
    </div>
</div>

{{-- Floating bulk-selection bar — page-local (not <x-bulk-action-bar>, which
     carries a hardcoded "View Selected" button wired to an Employee-only
     modal function that doesn't exist here). Only one action needed
     (download), so no Actions dropdown either — just the bar itself. --}}
<div id="contractBulkBar" class="align-items-center gap-2 p-2 bg-light border rounded shadow-lg"
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
    });
</script>
@endpush
@endsection
