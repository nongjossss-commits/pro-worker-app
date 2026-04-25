@include('financial.partials._stats_cards')

@if(request('unpriced') == 1)
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
        <span><i class="bi bi-info-circle me-2"></i> {{ __('Showing only employers with unpriced employees.') }}</span>
        <a href="{{ route('finance.index', ['tab' => 'renewal']) }}" class="btn btn-sm btn-outline-info">{{ __('Clear Filter') }}</a>
    </div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('finance.index') }}" method="GET" class="row g-3">
            <input type="hidden" name="tab" value="renewal">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="{{ __('Search Employer...') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-4 d-grid">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> {{ __('Search') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">{{ __('Renewal Resolution Employers') }}</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Employer') }}</th>
                        <th class="text-center">{{ __('Total Emp.') }}</th>
                        <th class="text-center">{{ __('Priced') }}</th>
                        <th class="text-center">{{ __('Unpriced') }}</th>
                        <th class="text-end">{{ __('Total Amount') }}</th>
                        <th class="text-end">{{ __('Billed') }}</th>
                        <th class="text-end">{{ __('Pending') }}</th>
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->employer ? $order->employer->employerNameTh : __('Unknown') }}</td>
                        <td class="text-center">{{ $order->total_employees }}</td>
                        <td class="text-center text-success">{{ $order->priced_employees_count }}</td>
                        <td class="text-center text-danger">{{ $order->unpriced_employees_count }}</td>
                        <td class="text-end fw-bold">{{ number_format($order->total_amount, 2) }}</td>
                        <td class="text-end">{{ number_format($order->billed_amount, 2) }}</td>
                        <td class="text-end text-warning fw-bold">{{ number_format($order->pending_amount, 2) }}</td>
                        <td class="text-center">
                            @if($order->employer)
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadFinancialTabModal('{{ route('production.renewal.finance.tab', ['resolutionTab' => $order->resolution_tab_id ?? \App\Models\ResolutionTab::where('type', 'renewal')->where('is_default', true)->value('id'), 'employer' => $order->employer->id]) }}', '{{ $order->employer->employerNameTh }}')">
                                    <i class="bi bi-wallet2"></i> {{ __('Manage Finance') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">{{ __('No records found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">
            {{ $orders->links() }}
        </div>
    </div>
</div>

<!-- Modal for Financial Tab is shared and should be included via registration tab or similar mechanism, but since they are separate partials, we should include the modal definition here too. -->
@if($tab === 'renewal')
<div class="modal fade" id="financialTabModal" tabindex="-1" aria-labelledby="financialTabModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="financialTabModalLabel">{{ __('Financial Data') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="financialTabModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let _fmScriptPromise = null;

    function _ensureFinancialManagerScript() {
        if (!_fmScriptPromise) {
            _fmScriptPromise = new Promise((resolve, reject) => {
                if (typeof financialManager === 'function') { resolve(); return; }
                const s = document.createElement('script');
                s.src = '{{ asset("js/financial-manager.js") }}';
                s.onload  = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }
        return _fmScriptPromise;
    }

    async function loadFinancialTabModal(url, title) {
        document.getElementById('financialTabModalLabel').innerText = "{{ __('Financial Data') }} - " + title;
        const body = document.getElementById('financialTabModalBody');
        body.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('financialTabModal'));
        modal.show();

        try {
            const [html] = await Promise.all([
                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                }).then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                }),
                _ensureFinancialManagerScript()
            ]);

            // Parse HTML and remove <script> tags (financial-manager.js already loaded)
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            doc.querySelectorAll('script').forEach(s => s.remove());
            body.innerHTML = doc.body.innerHTML;

            // Initialize Alpine.js for the dynamically injected component
            if (window.Alpine) Alpine.initTree(body);

        } catch (error) {
            console.error('Error fetching financial tab:', error);
            body.innerHTML = `
                <div class="alert alert-danger">
                    Error loading financial data. Please try again or check your permissions.
                </div>
            `;
        }
    }

    // Refresh page when modal is closed to update stats
    document.getElementById('financialTabModal').addEventListener('hidden.bs.modal', function () {
        window.location.reload();
    });
</script>
@endpush
@endif
