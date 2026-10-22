{{--
    "Job History" popup body (employer card → history button) — Workflow,
    Registration and Renewal resolution. Finished employees by month (or a
    date range) with two search boxes: employee (name / passport / request
    no. / RA …) and team. App\Support\HistoryFilter builds the data;
    public/js/history-filter.js reloads only [data-history-list] on change.

    @include('partials._history_panel', [
        'historyMonths' => [...], 'historyMonth' => 'YYYY-MM'|'all'|'none',
        'historyParams' => [...], 'historyCount' => n,
        'listView' => 'workflow.partials.order_items', 'listData' => [...],
    ])
--}}
<div data-history-panel>
    <form class="hf-bar d-flex flex-wrap align-items-end gap-2 mb-3 p-2 rounded border bg-body" data-history-filter onsubmit="return false;">
        <div class="hf-field">
            <label class="form-label small mb-1">{{ __('Month') }}</label>
            <select class="form-select form-select-sm" name="month" data-hf>
                <option value="all" @selected($historyMonth === 'all')>{{ __('All months') }}</option>
                @foreach($historyMonths as $key => $m)
                    <option value="{{ $key }}" @selected($historyMonth === (string) $key)>{{ $m['label'] }} ({{ $m['count'] }})</option>
                @endforeach
            </select>
        </div>
        <div class="hf-field">
            <label class="form-label small mb-1">{{ __('From date') }}</label>
            <input type="date" class="form-control form-control-sm" name="from" value="{{ $historyParams['from'] }}" data-hf>
        </div>
        <div class="hf-field">
            <label class="form-label small mb-1">{{ __('To date') }}</label>
            <input type="date" class="form-control form-control-sm" name="to" value="{{ $historyParams['to'] }}" data-hf>
        </div>
        <div class="hf-field hf-grow">
            <label class="form-label small mb-1"><i class="bi bi-search me-1"></i>{{ __('Search employee') }}</label>
            <input type="search" class="form-control form-control-sm" name="q" value="{{ $historyParams['q'] }}" data-hf
                   placeholder="{{ __('Name, passport, request no., RA no....') }}">
        </div>
        <div class="hf-field hf-grow">
            <label class="form-label small mb-1"><i class="bi bi-people-fill me-1"></i>{{ __('Search team') }}</label>
            <input type="search" class="form-control form-control-sm" name="team" value="{{ $historyParams['team'] }}" data-hf
                   placeholder="{{ __('Team Name') }}">
        </div>
        <div class="hf-field">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-hf-clear><i class="bi bi-x-lg me-1"></i>{{ __('Clear') }}</button>
        </div>
    </form>

    <div data-history-list>
        <div class="small text-muted mb-2" data-history-count>
            {{ __('Found :count finished employee(s)', ['count' => $historyCount]) }}
            @if($historyMonth !== 'all' && isset($historyMonths[$historyMonth]))
                · {{ $historyMonths[$historyMonth]['label'] }}
            @endif
        </div>
        @if($historyCount === 0)
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 d-block mb-1"></i>{{ __('No finished employees for this filter.') }}
            </div>
        @else
            @include($listView, $listData)
        @endif
    </div>
</div>
@once
<style>
    [data-history-panel] .hf-field { min-width: 9rem; }
    [data-history-panel] .hf-grow { flex: 1 1 13rem; }
    @media (max-width: 575.98px) { [data-history-panel] .hf-field { flex: 1 1 calc(50% - .5rem); min-width: 0; } [data-history-panel] .hf-grow { flex-basis: 100%; } }
</style>
@endonce
