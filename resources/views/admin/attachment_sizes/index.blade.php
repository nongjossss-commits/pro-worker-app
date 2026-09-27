@extends('layouts.app')

@section('title', __('Attachment File Sizes'))

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">{{ __('Attachment File Sizes') }}</h1>
            <p class="text-muted mb-0">{{ __('Uploaded files larger than the limit below. Check whether each one really needs to be that large — a one-page scan or photo rarely does — and re-upload a smaller copy to save storage.') }}</p>
        </div>
        <form method="GET" id="asz-form" class="d-flex align-items-center flex-wrap gap-2">
            {{-- Empty until the user picks a tab, so the list can open the tab that has files. --}}
            <input type="hidden" name="view" value="{{ request()->filled('view') ? $view : '' }}">
            <label class="small text-muted text-nowrap" for="minSize">{{ __('Show files from') }}</label>
            <select id="minSize" name="min" class="form-select form-select-sm" style="width: auto;">
                @foreach($thresholds as $t)
                    <option value="{{ $t }}" @selected($t === $minMb)>{{ $t }} MB</option>
                @endforeach
            </select>
            <label class="small text-muted text-nowrap" for="perPage">{{ __('Per page') }}</label>
            <select id="perPage" name="per_page" class="form-select form-select-sm" style="width: auto;">
                @foreach($perPageOptions as $n)
                    <option value="{{ $n }}" @selected($n === $perPage)>{{ $n }}</option>
                @endforeach
            </select>
            <button type="button" id="asz-refresh" class="btn btn-sm btn-outline-secondary text-nowrap" title="{{ __('Results are kept for 10 minutes — scan again now') }}">
                <i class="bi bi-arrow-clockwise"></i> {{ __('Scan again') }}
            </button>
        </form>
    </div>

    {{-- Filled from admin.attachment-sizes.list (see _list.blade.php) --}}
    <div id="asz-list" aria-live="polite">
        <div class="card"><div class="card-body text-center py-5 text-muted" data-asz-loading>
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <div>{{ __('Scanning files… the first scan can take a minute.') }}</div>
        </div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const box = document.getElementById('asz-list');
    const form = document.getElementById('asz-form');
    const LIST_URL = @json(route('admin.attachment-sizes.list'));
    const T = @json(['loading' => __('Scanning files… the first scan can take a minute.'), 'failed' => __('Could not load the list. The scan may have taken too long — please try again.'), 'retry' => __('Try again')]);
    let current = new URLSearchParams(window.location.search);
    let inflight = null;

    function loadingHtml() {
        return '<div class="card"><div class="card-body text-center py-5 text-muted" data-asz-loading>' +
            '<div class="spinner-border text-primary mb-3" role="status"></div><div>' + T.loading + '</div></div></div>';
    }

    function load(params, opts) {
        opts = opts || {};
        current = params;
        const qs = new URLSearchParams(params);
        if (opts.refresh) qs.set('refresh', '1');
        // Keep the table visible (dimmed) while paging; full spinner only for a new scan.
        if (opts.refresh || !box.querySelector('[data-asz-result]')) box.innerHTML = loadingHtml();
        else box.style.opacity = '.55';
        if (inflight) inflight.abort();
        inflight = new AbortController();
        fetch(LIST_URL + '?' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }, credentials: 'same-origin', signal: inflight.signal })
            .then(r => r.ok ? r.text() : Promise.reject(new Error('HTTP ' + r.status)))
            .then(html => {
                box.innerHTML = html;
                box.style.opacity = '';
                const shown = new URLSearchParams(params);
                const v = box.querySelector('[data-asz-result]');
                if (v && v.dataset.view) { shown.set('view', v.dataset.view); form.elements.view.value = v.dataset.view; }
                history.replaceState(null, '', window.location.pathname + '?' + shown);
                if (opts.scrollTop) box.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                box.style.opacity = '';
                box.innerHTML = '<div class="card"><div class="card-body text-center py-5">' +
                    '<i class="bi bi-exclamation-triangle text-warning" style="font-size:2rem;"></i>' +
                    '<p class="mt-2 text-muted">' + T.failed + '</p>' +
                    '<button type="button" class="btn btn-primary btn-sm" data-asz-retry><i class="bi bi-arrow-clockwise me-1"></i>' + T.retry + '</button></div></div>';
            });
    }

    function formParams(extra) {
        const p = new URLSearchParams(new FormData(form));
        Object.entries(extra || {}).forEach(([k, v]) => p.set(k, v));
        if (!p.get('view')) p.delete('view');
        return p;
    }

    form.addEventListener('change', () => load(formParams({ page: 1 })));
    form.addEventListener('submit', e => { e.preventDefault(); load(formParams({ page: 1 })); });
    document.getElementById('asz-refresh').addEventListener('click', () => load(formParams({ page: 1 }), { refresh: true }));

    box.addEventListener('click', function (e) {
        if (e.target.closest('[data-asz-retry]')) { load(current); return; }
        const tab = e.target.closest('[data-asz-view]');
        if (tab) {
            e.preventDefault();
            form.elements.view.value = tab.dataset.aszView;
            load(formParams({ page: 1 }));
            return;
        }
        const link = e.target.closest('.pagination a.page-link');
        if (link) {
            e.preventDefault();
            const u = new URL(link.href, window.location.origin);
            load(u.searchParams, { scrollTop: true });
        }
    });

    load(formParams({ page: current.get('page') || 1 }));
})();
</script>
@endpush
