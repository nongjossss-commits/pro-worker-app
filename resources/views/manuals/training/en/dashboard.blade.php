{{-- Training Edition: Dashboard (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-speedometer2"></i> {{ __('Dashboard') }} — {{ __('System-wide overview and summary') }}
    </h3>
    <p class="training-intro-desc">
        <strong>Dashboard</strong> is the first page a user sees after logging in — shows a <strong>summary</strong> of
        employee/employer counts, pending jobs, recent notifications, and <strong>quick links</strong> to frequently-used menus
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker</span>
        <span class="role-pill role-readonly">Employer</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open Dashboard + view the overview</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/01-overview',
        'alt' => 'Dashboard page with summary cards + quick links',
        'caption' => 'Dashboard — Summary cards + Quick links + Recent activity',
        'callouts' => [
            '<strong>Summary cards:</strong> number of employers/employees/pending jobs',
            '<strong>Expiry alerts:</strong> employees nearing expiry (60/30/7 days)',
            '<strong>Quick links:</strong> jump to frequently-used menus',
            '<strong>Recent notifications:</strong> the last 5 items',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Log in → automatically go to Dashboard</li>
            <li>Check the summary cards at the top</li>
            <li>Click a card or quick link to go to the menu you need</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Each role sees a different Dashboard</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/02-role-variants',
        'alt' => 'Dashboard variants for Admin / Caretaker / Employer',
        'caption' => 'Dashboard by role — different visible data',
        'callouts' => [
            '<strong>Admin/Staff:</strong> sees all data system-wide',
            '<strong>Caretaker:</strong> only sees the employers+employees they manage',
            '<strong>Employer:</strong> only sees their own employees',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: The numbers in the summary cards don't look right?</dt>
        <dd>A: Cache updates every 60 seconds — refresh, or wait a moment</dd>

        <dt>Q: Why does Caretaker see less data?</dt>
        <dd>A: Caretaker only sees employers assigned via the employer_caretaker pivot</dd>
    </dl>
</section>
