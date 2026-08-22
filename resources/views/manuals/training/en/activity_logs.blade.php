{{-- Training Edition: Activity Logs (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-clock-history"></i> {{ __('Activity Logs') }} — {{ __('An audit trail of every change in the system') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Activity Logs"</strong> menu records <strong>every data change</strong> in the system —
        who changed what, and when — for <strong>audit + retrospective review</strong>,
        for transparency + fraud prevention
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">View + filter the history</h2>

    @include('manuals.training._screenshot', [
        'src' => 'activity_logs/01-list-filter',
        'alt' => 'Activity log list + filter bar',
        'caption' => 'Activity Logs — sortable table + filters',
        'callouts' => [
            '<strong>Filter by user:</strong> select a specific user',
            '<strong>Filter by date:</strong> specify a date range',
            '<strong>Filter by type:</strong> create / update / delete',
            '<strong>Filter by entity:</strong> Employee / Employer / etc.',
            '<strong>Details:</strong> see the before/after data for the change',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Activity Logs</strong></li>
            <li>Use the filters to search</li>
            <li>Click a row to see the before/after details</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Using it for Audit + Investigation</h2>

    <div class="slide-instructions">
        <strong>Common use cases:</strong>
        <ol>
            <li>An employee record is missing — check who deleted it + when</li>
            <li>Passport data changed — check who edited it</li>
            <li>An invoice number was voided — check who + why</li>
            <li>Reviewing a staff member's work each month</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Pro tip:</strong> the Activity Log cannot be deleted — even Super Admin can't edit it (immutable)
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: How far back does the history go?</dt>
        <dd>A: There's no auto-delete — it's kept permanently for audit purposes (Super Admin can purge old entries if needed)</dd>

        <dt>Q: Can Staff/Caretaker see their own log entries?</dt>
        <dd>A: No — only Super Admin/Admin can view the Activity Log</dd>
    </dl>
</section>
