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
    <div class="slide-number">STEP 0</div>
    <h2 class="slide-title">Logging in + security</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/03-login',
        'alt' => 'Login page — colours and logo follow the system branding',
        'caption' => 'Login page — colours and logo follow the system branding',
    ])

    <div class="slide-instructions">
        <ol>
            <li>Closing the browser / app → log in again next time</li>
            <li>No page open for more than 5 minutes → signed out automatically</li>
            <li>Refresh or new tab → keeps working</li>
            <li>"Remember me" remembers the email; "Forgot your password?" sends a reset link (valid 60 minutes)</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open Dashboard + view the overview</h2>

    @include('manuals.training._screenshot', [
        'src' => 'dashboard/01-overview',
        'alt' => 'Executive Dashboard with figure cards + charts',
        'caption' => 'Dashboard — business at a glance: key figure cards + monthly charts + work in progress by tab',
        'callouts' => [
            '<strong>Header:</strong> date + highlight figures (finished in 12 months / nationalities / tabs with work in progress / 14-day actions)',
            '<strong>Figure cards:</strong> hover = breakdown + how it is counted',
            '<strong>Charts:</strong> finished per month · new employees vs notified out · work in progress by tab (click to open) · nationality · document / 90-day report due dates · top employers',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Log in → automatically go to Dashboard</li>
            <li>Check the figure cards at the top — hover a card or chart for details</li>
            <li>Click a bar in "Work in progress by tab" to open that tab · the dashboard shows no finance figures (see the Finance menu)</li>
            <li>The button after the language button = <strong>Light / Dark / Device default</strong> mode — switches instantly with no refresh, remembered per device</li>
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
