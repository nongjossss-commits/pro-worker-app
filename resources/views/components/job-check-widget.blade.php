@php
    $jobCheckSession = auth()->check()
        ? \App\Models\JobCheckSession::active()->where('user_id', auth()->id())->first()
        : null;
@endphp

<button type="button"
        id="jobCheckBtn"
        class="btn {{ $jobCheckSession ? 'btn-warning' : 'btn-outline-secondary' }} d-none d-md-flex align-items-center ms-2"
        onclick="jobCheckOpen()">
    <i class="bi bi-clipboard-check-fill me-1"></i> {{ __('Job Check Mode') }}
    @if($jobCheckSession)
        <span class="badge bg-danger ms-1">{{ __('Active') }}</span>
    @endif
</button>

{{-- Explanation / start modal --}}
<div class="modal fade" id="jobCheckStartModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard-check-fill me-1"></i> {{ __('Job Check Mode') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('When you enter Job Check Mode, the system immediately records the current step/status of every employee in Pre-Production, Workflow, Registration Resolution, and Renewal Resolution.') }}</p>
                <p>{{ __('After this, you may only work inside these 4 menus until you finish. When you finish and confirm, the system compares the final state against what it recorded at the start, and exports two Excel files: employees with movement, and employees with no movement — organized by menu.') }}</p>
                <p class="text-muted small mb-0">{{ __('A wrong tick that gets corrected back to its original state will not count as movement — only the final state matters.') }}</p>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none" data-bs-toggle="modal" data-bs-target="#jobCheckHistoryModal" data-bs-dismiss="modal">
                        <i class="bi bi-clock-history me-1"></i>{{ __('View Check History') }}
                    </button>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none" data-bs-toggle="modal" data-bs-target="#jobCheckSummaryModal" data-bs-dismiss="modal">
                        <i class="bi bi-bar-chart-line me-1"></i>{{ __('Deep Summary Report') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <form method="POST" action="{{ route('job-check.start') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning">{{ __('Start Job Check Mode') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@if($jobCheckSession)
{{-- Active mode modal --}}
<div class="modal fade" id="jobCheckActiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title"><i class="bi bi-clipboard-check-fill me-1"></i> {{ __('Job Check Mode is active') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">{{ __('Started at') }}: {{ $jobCheckSession->started_at->format('d/m/Y H:i') }}</p>
                <p class="text-muted small">{{ __('You are confined to Pre-Production, Workflow, Registration Resolution, and Renewal Resolution until you finish or cancel.') }}</p>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none" data-bs-toggle="modal" data-bs-target="#jobCheckHistoryModal" data-bs-dismiss="modal">
                        <i class="bi bi-clock-history me-1"></i>{{ __('View Check History') }}
                    </button>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none" data-bs-toggle="modal" data-bs-target="#jobCheckSummaryModal" data-bs-dismiss="modal">
                        <i class="bi bi-bar-chart-line me-1"></i>{{ __('Deep Summary Report') }}
                    </button>
                </div>
                <div>
                    <form method="POST" action="{{ route('job-check.cancel') }}" class="d-inline" onsubmit="return confirm('{{ __('Cancel Job Check Mode without exporting a report?') }}');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('Cancel Mode (No Export)') }}</button>
                    </form>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#jobCheckFinishConfirmModal" data-bs-dismiss="modal">
                        {{ __('Finish') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Finish confirmation modal --}}
<div class="modal fade" id="jobCheckFinishConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Confirm Finish') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">{{ __('This will compare the current state against what was recorded at the start and generate the report. Are you sure you want to finish Job Check Mode?') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-success" id="jobCheckConfirmFinishBtn" onclick="jobCheckFinish()">
                    <span id="jobCheckFinishBtnText">{{ __('Confirm Finish') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Results modal --}}
<div class="modal fade" id="jobCheckResultsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success-subtle">
                <h5 class="modal-title"><i class="bi bi-check-circle-fill me-1"></i> {{ __('Job Check Mode finished') }}</h5>
            </div>
            <div class="modal-body">
                <p id="jobCheckResultsSummary" class="mb-3"></p>
                <div class="d-grid gap-2">
                    <a id="jobCheckDownloadMoved" href="#" class="btn btn-outline-primary">
                        <i class="bi bi-file-earmark-excel me-1"></i> {{ __('Download: With Movement') }}
                    </a>
                    <a id="jobCheckDownloadNotMoved" href="#" class="btn btn-outline-secondary">
                        <i class="bi bi-file-earmark-excel me-1"></i> {{ __('Download: No Movement') }}
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="window.location.reload()">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- History modal — last 7 business days of completed check sessions --}}
<div class="modal fade" id="jobCheckHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clock-history me-1"></i> {{ __('Job Check History') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body" id="jobCheckHistoryBody" style="max-height: 60vh; overflow-y: auto;">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm me-2"></div>{{ __('Loading...') }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Deep summary report modal — upload previously-exported files, regroup by employer --}}
<div class="modal fade" id="jobCheckSummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-bar-chart-line me-1"></i> {{ __('Deep Summary Report') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">{{ __('Upload the Excel file(s) you already exported from Job Check Mode (from Finish or from Check History). This regroups every row by employer — how many progressed to which step, and how many are unchanged since last time.') }}</p>
                <form id="jobCheckSummaryForm">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('With Movement file (optional)') }}</label>
                            <input type="file" name="moved_file" accept=".xlsx" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">{{ __('No Movement file (optional)') }}</label>
                            <input type="file" name="not_moved_file" accept=".xlsx" class="form-control form-control-sm">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" id="jobCheckSummarySubmitBtn">
                        <span id="jobCheckSummarySubmitBtnText">{{ __('Generate Summary') }}</span>
                    </button>
                </form>
                <div id="jobCheckSummaryResults" class="mt-3" style="max-height: 50vh; overflow-y: auto;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    function jobCheckOpen() {
        var activeModalEl = document.getElementById('jobCheckActiveModal');
        if (activeModalEl) {
            bootstrap.Modal.getOrCreateInstance(activeModalEl).show();
        } else {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('jobCheckStartModal')).show();
        }
    }

    function jobCheckFinish() {
        var btn = document.getElementById('jobCheckConfirmFinishBtn');
        var btnText = document.getElementById('jobCheckFinishBtnText');
        btn.disabled = true;
        btnText.textContent = '{{ __('Processing...') }}';

        fetch('{{ route('job-check.finish') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btnText.textContent = '{{ __('Confirm Finish') }}';

            if (!data.success) {
                alert('{{ __('Something went wrong. Please try again.') }}');
                return;
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('jobCheckFinishConfirmModal')).hide();

            document.getElementById('jobCheckResultsSummary').textContent =
                '{{ __('With movement') }}: ' + data.moved_count + ' — {{ __('No movement') }}: ' + data.not_moved_count;
            document.getElementById('jobCheckDownloadMoved').href = data.download_moved;
            document.getElementById('jobCheckDownloadNotMoved').href = data.download_not_moved;

            bootstrap.Modal.getOrCreateInstance(document.getElementById('jobCheckResultsModal')).show();
        })
        .catch(() => {
            btn.disabled = false;
            btnText.textContent = '{{ __('Confirm Finish') }}';
            alert('{{ __('Something went wrong. Please try again.') }}');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var historyModalEl = document.getElementById('jobCheckHistoryModal');
        if (!historyModalEl) return;

        historyModalEl.addEventListener('show.bs.modal', function () {
            var body = document.getElementById('jobCheckHistoryBody');
            body.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>{{ __('Loading...') }}</div>';

            fetch('{{ route('job-check.history') }}', {
                headers: { 'Accept': 'application/json' },
            })
            .then(res => res.json())
            .then(data => {
                var days = Object.keys(data.history || {});
                if (days.length === 0) {
                    body.innerHTML = '<p class="text-muted text-center py-4 mb-0">{{ __('No check history yet.') }}</p>';
                    return;
                }

                days.sort().reverse();
                var html = '';
                days.forEach(function (day) {
                    html += '<h6 class="mt-2">' + day + '</h6>';
                    html += '<div class="list-group mb-3">';
                    data.history[day].forEach(function (item) {
                        html += '<div class="list-group-item d-flex justify-content-between align-items-center">';
                        html += '<div><strong>{{ __('Round') }} ' + item.sequence + '</strong>';
                        if (item.ended_at) html += ' <span class="text-muted small">(' + item.ended_at + ')</span>';
                        if (item.user_name) html += ' <span class="text-muted small">— ' + item.user_name + '</span>';
                        html += '</div>';
                        html += '<div>';
                        html += '<a href="' + item.download_moved + '" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-file-earmark-excel"></i> {{ __('With movement') }}</a>';
                        html += '<a href="' + item.download_not_moved + '" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-excel"></i> {{ __('No movement') }}</a>';
                        html += '</div></div>';
                    });
                    html += '</div>';
                });
                body.innerHTML = html;
            })
            .catch(function () {
                body.innerHTML = '<p class="text-danger text-center py-4 mb-0">{{ __('Something went wrong. Please try again.') }}</p>';
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('jobCheckSummaryForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var btn = document.getElementById('jobCheckSummarySubmitBtn');
            var btnText = document.getElementById('jobCheckSummarySubmitBtnText');
            var results = document.getElementById('jobCheckSummaryResults');

            btn.disabled = true;
            btnText.textContent = '{{ __('Processing...') }}';
            results.innerHTML = '';

            var formData = new FormData(form);

            fetch('{{ route('job-check.summary') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: formData,
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btnText.textContent = '{{ __('Generate Summary') }}';

                if (!data.success) {
                    results.innerHTML = '<div class="alert alert-danger mb-0">' + (data.message || '{{ __('Something went wrong. Please try again.') }}') + '</div>';
                    return;
                }

                var employers = Object.keys(data.summary || {});
                if (employers.length === 0) {
                    results.innerHTML = '<p class="text-muted text-center py-4 mb-0">{{ __('No rows found in the uploaded file(s).') }}</p>';
                    return;
                }

                var html = '<table class="table table-sm table-bordered align-middle">';
                html += '<thead class="table-light"><tr>';
                html += '<th class="text-center">{{ __('No.') }}</th>';
                html += '<th>{{ __('Employer') }}</th>';
                html += '<th class="text-center">{{ __('Total') }}</th>';
                html += '<th>{{ __('With movement') }} — {{ __('by step reached') }}</th>';
                html += '<th>{{ __('No movement') }} — {{ __('by current step') }}</th>';
                html += '</tr></thead><tbody>';

                var seq = 1;
                employers.forEach(function (employer) {
                    var row = data.summary[employer];
                    var movedBreakdown = Object.keys(row.moved_by_step || {}).map(function (step) {
                        return step + ': ' + row.moved_by_step[step];
                    }).join('<br>') || '-';
                    var notMovedBreakdown = Object.keys(row.not_moved_by_step || {}).map(function (step) {
                        return step + ': ' + row.not_moved_by_step[step];
                    }).join('<br>') || '-';

                    html += '<tr>';
                    html += '<td class="text-center text-muted">' + (seq++) + '</td>';
                    html += '<td>' + employer + '</td>';
                    html += '<td class="text-center fw-bold">' + row.total + '</td>';
                    html += '<td><span class="badge bg-primary mb-1">' + row.moved_count + '</span><br>' + movedBreakdown + '</td>';
                    html += '<td><span class="badge bg-secondary mb-1">' + row.not_moved_count + '</span><br>' + notMovedBreakdown + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';
                results.innerHTML = html;
            })
            .catch(function () {
                btn.disabled = false;
                btnText.textContent = '{{ __('Generate Summary') }}';
                results.innerHTML = '<div class="alert alert-danger mb-0">{{ __('Something went wrong. Please try again.') }}</div>';
            });
        });
    });
</script>
@endpush
@endonce
