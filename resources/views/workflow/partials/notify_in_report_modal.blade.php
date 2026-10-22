{{--
    Tab 5 "แจ้งเข้า บต.52 / บต.55 / บต.56" — Add Employees.
    Lists employees FINISHED in the change-employer tab (incl. those still in
    the 24h transfer countdown), grouped by their new employer; tick people /
    a whole employer / everything → one NEW card per employer
    (WorkflowController::notifyInReportCandidates() / notifyInReportStore()).
--}}
<div class="modal fade" id="notifyInReportModal" tabindex="-1" aria-hidden="true" x-data="notifyInReportPicker()" x-init="init()">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus-fill me-1"></i>{{ __('Add Employees') }} — {{ $reportTab->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border small py-2 mb-2">
                    <i class="bi bi-info-circle me-1"></i>{{ __('Employees finished in the change-employer tab, grouped by their new employer. Tick people (or a whole employer) — each employer gets a new card; steps are ticked on the card for everyone at once.') }}
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <input type="search" class="form-control form-control-sm" style="max-width: 280px" x-model="search" placeholder="{{ __('Search name, passport or employer...') }}">
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="setAll(true)" :disabled="!total"><i class="bi bi-check2-all me-1"></i>{{ __('Select All') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="setAll(false)" :disabled="!selectedCount"><i class="bi bi-x-lg me-1"></i>{{ __('Clear Selection') }}</button>
                    <span class="ms-auto small">
                        {{ __('Selected') }}: <strong x-text="selectedCount"></strong> {{ __('people') }} · <strong x-text="selectedEmployerCount"></strong> {{ __('card(s)') }}
                    </span>
                </div>

                <div x-show="loading" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-2"></span>{{ __('Loading...') }}</div>
                <div x-show="!loading && !groups.length" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-1"></i>{{ __('No employees waiting — employees appear here after they are finished in the change-employer tab.') }}
                </div>

                <template x-for="g in visibleGroups()" :key="g.employer_id">
                    <div class="border rounded mb-2">
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-body-tertiary border-bottom">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" :id="'nirEr' + g.employer_id" :checked="groupAll(g)" :indeterminate.prop="groupSome(g)" @change="setGroup(g, $event.target.checked)">
                                <label class="form-check-label fw-semibold" :for="'nirEr' + g.employer_id">
                                    <i class="bi bi-building me-1"></i><span x-text="g.employer_name"></span>
                                    <span class="text-muted small fw-normal" x-show="g.employer_name_en" x-text="'(' + g.employer_name_en + ')'"></span>
                                </label>
                            </div>
                            <span class="small text-muted"><span x-text="g.employees.length"></span> {{ __('people') }}</span>
                        </div>
                        <template x-for="e in visibleEmployees(g)" :key="e.source_item_id">
                            <label class="d-flex align-items-center gap-2 px-2 py-1 border-bottom mb-0" style="cursor: pointer;">
                                <input class="form-check-input m-0" type="checkbox" :value="e.source_item_id" x-model.number="selected">
                                <img :src="e.photo" class="rounded-circle border" width="32" height="32" style="object-fit: cover;" alt="">
                                <span class="flex-grow-1 min-w-0">
                                    <span class="fw-semibold" x-text="e.name"></span>
                                    <span class="text-muted small ms-1" x-show="e.name_th" x-text="e.name_th"></span>
                                    <span class="d-block small text-muted">
                                        <span x-show="e.passport" x-text="'PP: ' + e.passport"></span>
                                        · {{ __('Finished') }} <span x-text="e.completed_at"></span>
                                    </span>
                                </span>
                                <span class="badge bg-warning-subtle text-warning-emphasis text-wrap" x-show="e.transfer_pending" :title="'{{ __('Moves to the new employer at') }} ' + e.transfer_at">
                                    <i class="bi bi-hourglass-split me-1"></i>{{ __('In the 24h countdown') }}
                                </span>
                            </label>
                        </template>
                    </div>
                </template>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" @click="submit()" :disabled="!selectedCount || saving">
                    <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                    <i class="bi bi-plus-lg me-1" x-show="!saving"></i>{{ __('Create cards') }} (<span x-text="selectedEmployerCount"></span>)
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function notifyInReportPicker() {
        return {
            groups: [], selected: [], search: '', loading: false, saving: false,
            init() {
                this.$el.addEventListener('show.bs.modal', () => this.load());
            },
            load() {
                this.loading = true;
                this.selected = [];
                fetch(@json(route('workflow.notify_in_report.candidates')), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(d => { this.groups = d.groups || []; })
                    .catch(() => appToast(@json(__('Could not load the items. Please try again.')), 'error'))
                    .finally(() => { this.loading = false; });
            },
            norm(v) { return String(v || '').toLowerCase(); },
            matches(g, e) {
                const q = this.norm(this.search).trim();
                if (!q) return true;
                return [g.employer_name, g.employer_name_en, e.name, e.name_th, e.passport].some(v => this.norm(v).includes(q));
            },
            visibleEmployees(g) { return g.employees.filter(e => this.matches(g, e)); },
            visibleGroups() { return this.groups.filter(g => this.visibleEmployees(g).length); },
            get total() { return this.groups.reduce((n, g) => n + g.employees.length, 0); },
            get selectedCount() { return this.selected.length; },
            get selectedEmployerCount() {
                return this.groups.filter(g => g.employees.some(e => this.selected.includes(e.source_item_id))).length;
            },
            groupAll(g) { const v = this.visibleEmployees(g); return v.length > 0 && v.every(e => this.selected.includes(e.source_item_id)); },
            groupSome(g) { const v = this.visibleEmployees(g); const n = v.filter(e => this.selected.includes(e.source_item_id)).length; return n > 0 && n < v.length; },
            setGroup(g, on) {
                const ids = this.visibleEmployees(g).map(e => e.source_item_id);
                this.selected = on ? Array.from(new Set([...this.selected, ...ids])) : this.selected.filter(id => !ids.includes(id));
            },
            setAll(on) {
                if (!on) { this.selected = []; return; }
                this.selected = this.visibleGroups().flatMap(g => this.visibleEmployees(g).map(e => e.source_item_id));
            },
            submit() {
                if (!this.selectedCount) return;
                this.saving = true;
                fetch(@json(route('workflow.notify_in_report.store')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ source_item_ids: this.selected }),
                })
                    .then(r => r.json().then(d => ({ ok: r.ok, d })))
                    .then(({ ok, d }) => {
                        if (!ok || !d.success) { appToast(d.message || 'Error', 'error'); this.saving = false; return; }
                        appToast(d.message, 'success');
                        setTimeout(() => location.reload(), 700);
                    })
                    .catch(() => { appToast('Error', 'error'); this.saving = false; });
            },
        };
    }
    window.openNotifyInReportModal = function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('notifyInReportModal')).show();
    };
</script>
@endpush
