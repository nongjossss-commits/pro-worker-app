@extends('layouts.app')

@section('title', __('Welcome'))

@section('content')
<div class="d-flex flex-column justify-content-center align-items-center min-vh-100 bg-light">
    <div class="text-center p-5 bg-white shadow-sm rounded">
        <h1 class="display-1 fw-bold text-primary mb-4">{{ __('WELCOME') }}</h1>
        <p class="lead text-muted mb-4">{{ __('Please select a menu from the sidebar to continue.') }}</p>

        @auth
            <div class="d-flex justify-content-center gap-3 mt-4">
                @if(\App\Facades\SuperAdmin::isVisible('dashboard'))
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-bar-chart-fill me-2"></i>{{ __('Go to Statistics') }}
                </a>
                @endif

                @if(\App\Facades\SuperAdmin::isVisible('employees'))
                <a href="{{ route('employees.index') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-people-fill me-2"></i>{{ __('Manage Employees') }}
                </a>
                @endif
            </div>
        @endauth
    </div>
</div>

@push('scripts')
<script>
    // Combined appointment reminder, then notification digest — both pop up
    // every time this Welcome page is reached, not just right after login
    // (an already-authenticated session landing here directly, e.g.
    // reopening a closed tab, should still see them). Each modal lives in
    // layouts/app.blade.php and is only rendered there for users with the
    // matching permission, so this just no-ops for whichever one a given
    // user can't see. The two are chained (calendar closes -> notification
    // digest fetches and shows, if it has anything to show) rather than
    // shown together, so they don't visually stack.
    document.addEventListener('DOMContentLoaded', function () {
        const calModalEl = document.getElementById('appointmentReminderModal');
        const notifModalEl = document.getElementById('notificationSummaryModal');

        function tryShowNotificationSummary() {
            if (!notifModalEl) return;

            fetch(notifModalEl.dataset.summaryUrl, { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.total) return;
                    renderNotificationSummary(notifModalEl, data);
                    new bootstrap.Modal(notifModalEl).show();
                })
                .catch(function () { /* silent — this is a heads-up popup, not a critical flow */ });
        }

        if (calModalEl) {
            calModalEl.addEventListener('hidden.bs.modal', tryShowNotificationSummary, { once: true });
            new bootstrap.Modal(calModalEl).show();
        } else {
            tryShowNotificationSummary();
        }
    });

    function renderNotificationSummary(modalEl, data) {
        const body = document.getElementById('notificationSummaryModalBody');
        if (!body) return;

        if (!data.groups || !data.groups.length) {
            body.innerHTML = '<p class="text-center text-muted py-4">' + escapeHtml(modalEl.dataset.labelEmpty) + '</p>';
            return;
        }

        const overdueLabel = modalEl.dataset.labelOverdue || 'Overdue by :n days';
        const remainingLabel = modalEl.dataset.labelRemaining || ':n days left';

        body.innerHTML = data.groups.map(function (g) {
            const items = g.items.map(function (item) {
                const days = item.days_remaining;
                const daysText = days < 0
                    ? overdueLabel.replace(':n', Math.abs(days))
                    : remainingLabel.replace(':n', days);
                const daysClass = days < 0 ? 'text-danger' : (days <= 7 ? 'text-warning' : 'text-muted');

                return '' +
                    '<a href="' + item.view_url + '" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">' +
                        '<span>' + escapeHtml(item.name) + '</span>' +
                        '<span class="small ' + daysClass + '">' + escapeHtml(item.due_date || '-') + ' (' + escapeHtml(daysText) + ')</span>' +
                    '</a>';
            }).join('');

            return '' +
                '<div class="mb-3">' +
                    '<div class="d-flex align-items-center gap-2 mb-1">' +
                        '<span class="fw-bold">' + escapeHtml(g.label) + '</span>' +
                        '<span class="badge bg-danger rounded-pill">' + g.count + '</span>' +
                    '</div>' +
                    '<div class="list-group list-group-flush border rounded">' + items + '</div>' +
                '</div>';
        }).join('');
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str === null || str === undefined ? '' : String(str);
        return div.innerHTML;
    }
</script>
@endpush
@endsection
