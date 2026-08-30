@php
    $jobCheckSession = auth()->check()
        ? \App\Models\JobCheckSession::current()->where('user_id', auth()->id())->first()
        : null;
@endphp

<button type="button"
        id="jobCheckBtn"
        class="btn {{ $jobCheckSession ? 'btn-warning' : 'btn-outline-secondary' }} d-none d-md-flex align-items-center ms-2"
        onclick="jobCheckOpen()">
    <i class="bi bi-clipboard-check-fill me-1"></i> {{ __('Job Check Mode') }}
    @if($jobCheckSession)
        <span id="jobCheckStatusBadge"
              class="badge {{ $jobCheckSession->status === 'paused' ? 'bg-secondary' : 'bg-danger' }} ms-1">
            {{ $jobCheckSession->status === 'paused' ? __('Paused') : __('Active') }}
        </span>
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
                <p class="text-muted small mb-0">{{ __('Need a break? Press Pause — you can work anywhere in the meantime and resume later without losing progress or restarting the check.') }}</p>
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
{{-- Active/paused mode modal --}}
<div class="modal fade" id="jobCheckActiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header {{ $jobCheckSession->status === 'paused' ? 'bg-secondary-subtle' : 'bg-warning-subtle' }}" id="jobCheckActiveModalHeader">
                <h5 class="modal-title"><i class="bi bi-clipboard-check-fill me-1"></i>
                    <span id="jobCheckModalTitleActive" {{ $jobCheckSession->status === 'paused' ? 'hidden' : '' }}>{{ __('Job Check Mode is active') }}</span>
                    <span id="jobCheckModalTitlePaused" {{ $jobCheckSession->status === 'paused' ? '' : 'hidden' }}>{{ __('Job Check Mode is paused') }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">{{ __('Started at') }}: {{ $jobCheckSession->started_at->format('d/m/Y H:i') }}</p>
                <div id="jobCheckBodyPaused" {{ $jobCheckSession->status === 'paused' ? '' : 'hidden' }}>
                    <p class="text-muted small mb-0">{{ __('Paused — you can work anywhere in every tab until you resume. Your progress so far is kept; resuming does not restart the check.') }}</p>
                </div>
                <div id="jobCheckBodyActive" {{ $jobCheckSession->status === 'paused' ? 'hidden' : '' }}>
                    <p class="text-muted small mb-2">{{ __('You are confined to Pre-Production, Workflow, Registration Resolution, and Renewal Resolution in this tab until you finish, cancel, or pause.') }}</p>
                    <p class="small text-muted mb-1" id="jobCheckTabStatusText"></p>
                    <button type="button" id="jobCheckTabJoinBtn" class="btn btn-link btn-sm p-0 text-decoration-none d-none" onclick="jobCheckJoinTab()">{{ __('Join Job Check Mode in this tab') }}</button>
                    <button type="button" id="jobCheckTabLeaveBtn" class="btn btn-link btn-sm p-0 text-decoration-none d-none" onclick="jobCheckLeaveTab()">{{ __('Work normally in this tab instead') }}</button>
                </div>
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
                    <form method="POST" action="{{ route('job-check.resume') }}" id="jobCheckResumeForm" class="{{ $jobCheckSession->status === 'paused' ? 'd-inline' : 'd-none' }}">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm">{{ __('Resume') }}</button>
                    </form>
                    <form method="POST" action="{{ route('job-check.pause') }}" id="jobCheckPauseForm" class="{{ $jobCheckSession->status === 'paused' ? 'd-none' : 'd-inline' }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">{{ __('Pause') }}</button>
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
    // ------------------------------------------------------------------
    // Per-tab Job Check Mode marker — sessionStorage is isolated per browser
    // tab, so this is what lets a second tab stay unconfined by default
    // instead of silently inheriting the mode from whichever tab actually
    // started/resumed it. The tab that runs start()/resume() lands back
    // here with ?_jc=1 in the URL (see JobCheckSessionController), which
    // latches 'in' into this tab's sessionStorage; from then on this
    // script re-attaches the marker (query param on links/forms, header on
    // fetch/XHR) to every same-tab request so EnforceJobCheckMode keeps
    // recognizing it as confined. A tab with no marker is never blocked —
    // the user can open a new tab to work elsewhere freely, and opt it
    // into Job Check Mode explicitly via the widget if they want it to
    // participate too.
    // ------------------------------------------------------------------
    (function () {
        var STORAGE_KEY = 'jobCheckTabMode'; // 'in' | 'out'

        function getTabMode() {
            try { return sessionStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
        }
        function setTabMode(v) {
            try { sessionStorage.setItem(STORAGE_KEY, v); } catch (e) {}
        }

        var params = new URLSearchParams(window.location.search);
        if (params.get('_jc') === '1') {
            setTabMode('in');
            params.delete('_jc');
            var cleanUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + window.location.hash;
            window.history.replaceState({}, '', cleanUrl);
        }

        function isSameOrigin(url) {
            try { return new URL(url, window.location.origin).origin === window.location.origin; } catch (e) { return false; }
        }

        document.addEventListener('click', function (e) {
            if (getTabMode() !== 'in') return;
            var a = e.target.closest('a[href]');
            if (!a) return;
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
            // File downloads and "open in new tab" links are not navigation
            // away from the current page — tagging them with _jc=1 would
            // either get the download blocked or latch a fresh tab into the
            // confined mode against the user's intent.
            if (a.hasAttribute('download')) return;
            if ((a.getAttribute('target') || '').toLowerCase() === '_blank') return;
            if (!isSameOrigin(a.href)) return;
            var url = new URL(a.href, window.location.origin);
            if (!url.searchParams.has('_jc')) {
                url.searchParams.set('_jc', '1');
                a.href = url.toString();
            }
        }, true);

        document.addEventListener('submit', function (e) {
            if (getTabMode() !== 'in') return;
            var form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            var action = form.getAttribute('action') || window.location.href;
            if (!isSameOrigin(action)) return;
            if (!form.querySelector('input[name="_jc"]')) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_jc';
                input.value = '1';
                form.appendChild(input);
            }
        }, true);

        var origFetch = window.fetch;
        if (origFetch) {
            window.fetch = function (input, init) {
                if (getTabMode() === 'in' && typeof input === 'string' && isSameOrigin(input)) {
                    init = init ? Object.assign({}, init) : {};
                    var headers = new Headers(init.headers || {});
                    headers.set('X-Job-Check-Tab', '1');
                    init.headers = headers;
                }
                return origFetch.call(this, input, init);
            };
        }

        var origOpen = XMLHttpRequest.prototype.open;
        var origSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.open = function (method, url) {
            this.__jcUrl = url;
            return origOpen.apply(this, arguments);
        };
        XMLHttpRequest.prototype.send = function () {
            if (getTabMode() === 'in' && this.__jcUrl && isSameOrigin(this.__jcUrl)) {
                try { this.setRequestHeader('X-Job-Check-Tab', '1'); } catch (e) {}
            }
            return origSend.apply(this, arguments);
        };

        function renderTabStatus() {
            var el = document.getElementById('jobCheckTabStatusText');
            var joinBtn = document.getElementById('jobCheckTabJoinBtn');
            var leaveBtn = document.getElementById('jobCheckTabLeaveBtn');
            if (!el) return;
            if (getTabMode() === 'in') {
                el.textContent = '{{ __('This tab: participating in Job Check Mode.') }}';
                if (joinBtn) joinBtn.classList.add('d-none');
                if (leaveBtn) leaveBtn.classList.remove('d-none');
            } else {
                el.textContent = '{{ __('This tab: working normally, not confined.') }}';
                if (joinBtn) joinBtn.classList.remove('d-none');
                if (leaveBtn) leaveBtn.classList.add('d-none');
            }
        }

        window.jobCheckJoinTab = function () {
            setTabMode('in');
            window.location.href = '{{ route('workflow.index') }}?_jc=1';
        };
        window.jobCheckLeaveTab = function () {
            setTabMode('out');
            renderTabStatus();
        };

        document.addEventListener('DOMContentLoaded', renderTabStatus);

        // ------------------------------------------------------------------
        // Pause / Resume without reloading the page. The user asked to keep
        // working from wherever they are instead of being bounced back to
        // Workflow and losing their place. We flip the session status via a
        // background POST and repaint the widget + modal in place. The tab
        // marker ('in') is never cleared, so once the session is 'active'
        // again this tab is confined again automatically.
        // ------------------------------------------------------------------
        var ON_CONFINED_PAGE = {{ request()->routeIs('workflow.*', 'production.*') ? 'true' : 'false' }};

        function applyStatus(status) {
            var paused = status === 'paused';

            var badge = document.getElementById('jobCheckStatusBadge');
            if (badge) {
                badge.classList.toggle('bg-secondary', paused);
                badge.classList.toggle('bg-danger', !paused);
                badge.textContent = paused ? '{{ __('Paused') }}' : '{{ __('Active') }}';
            }

            var header = document.getElementById('jobCheckActiveModalHeader');
            if (header) {
                header.classList.toggle('bg-secondary-subtle', paused);
                header.classList.toggle('bg-warning-subtle', !paused);
            }

            var titleActive = document.getElementById('jobCheckModalTitleActive');
            var titlePaused = document.getElementById('jobCheckModalTitlePaused');
            if (titleActive) titleActive.hidden = paused;
            if (titlePaused) titlePaused.hidden = !paused;

            var bodyActive = document.getElementById('jobCheckBodyActive');
            var bodyPaused = document.getElementById('jobCheckBodyPaused');
            if (bodyActive) bodyActive.hidden = paused;
            if (bodyPaused) bodyPaused.hidden = !paused;

            var pauseForm = document.getElementById('jobCheckPauseForm');
            var resumeForm = document.getElementById('jobCheckResumeForm');
            if (pauseForm) { pauseForm.classList.toggle('d-none', paused); pauseForm.classList.toggle('d-inline', !paused); }
            if (resumeForm) { resumeForm.classList.toggle('d-none', !paused); resumeForm.classList.toggle('d-inline', paused); }

            renderTabStatus();
        }

        function submitStatusChange(form, targetStatus) {
            var btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;

            fetch(form.getAttribute('action'), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(res); })
            .then(function () {
                if (btn) btn.disabled = false;
                applyStatus(targetStatus);

                if (targetStatus === 'paused') {
                    if (window.showToast) showToast('{{ __('Job Check Mode paused. You can work anywhere until you resume.') }}', 'success');
                } else {
                    // Resuming from outside the 4 menus: there is nothing to
                    // "continue" on this page, so send them back in. From
                    // inside, stay exactly where they are.
                    if (!ON_CONFINED_PAGE) {
                        setTabMode('in');
                        window.location.href = '{{ route('workflow.index') }}?_jc=1';
                        return;
                    }
                    setTabMode('in');
                    if (window.showToast) showToast('{{ __('Job Check Mode resumed in this tab.') }}', 'success');
                }
            })
            .catch(function () {
                if (btn) btn.disabled = false;
                // Fall back to the plain form submit (full reload) so the
                // action still goes through if the AJAX call failed.
                form.submit();
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            var pauseForm = document.getElementById('jobCheckPauseForm');
            var resumeForm = document.getElementById('jobCheckResumeForm');
            if (pauseForm) {
                pauseForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    submitStatusChange(pauseForm, 'paused');
                });
            }
            if (resumeForm) {
                resumeForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    submitStatusChange(resumeForm, 'active');
                });
            }
        });
    })();

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
