{{-- Training Edition: Renewal Resolution (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-arrow-clockwise"></i> {{ __('Renewal Resolution') }} — {{ __('Manage cabinet resolutions for worker renewal') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Renewal Resolution"</strong> menu manages <strong>cabinet resolutions</strong> for <strong>renewing</strong> employees who are about to expire.
        Uses the same mechanism as Registration Resolution — but focused on existing employees nearing Work Permit or Visa expiry
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (view only)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open the menu + select a resolution tab</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/01-tab-bar',
        'alt' => 'Renewal Resolution main page + tab bar',
        'caption' => 'Renewal Resolution — each tab is one renewal round',
        'callouts' => [
            '<strong>Tab Bar:</strong> e.g. "2025 Renewal Round 1"',
            '<strong>Stats cards:</strong> total employees / completed / pending',
            '<strong>Filter pills:</strong> 5 colors based on progress',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Renewal Resolution</strong></li>
            <li>Click the tab for the resolution you want</li>
            <li>Check the overview in the summary cards at the top</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Filter pills — filter by progress</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/02-filter-pills',
        'alt' => '5 colored filter pills',
        'caption' => 'Filter pills — select multiple at once',
        'callouts' => [
            '<strong>⚪ Not renewed yet:</strong> employees who haven\'t started renewing',
            '<strong>🟣 Visa updated:</strong> visa renewed, WP remaining',
            '<strong>🟡 Work permit updated:</strong> WP renewed, visa remaining',
            '<strong>🔵 Fully renewed – ready to finish:</strong> both done, ready to close',
            '<strong>🟢 Finished:</strong> closed out',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click a pill for the status you want to filter by</li>
            <li>You can select multiple at once (toggle on/off)</li>
            <li>The number inside a pill shows how many match that filter</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Configure Auto Settings (per-tab)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/03-auto-settings',
        'alt' => 'Auto Settings popup for a renewal tab',
        'caption' => 'Auto Settings — set the target expiry dates separately per tab',
        'callouts' => [
            '<strong>Popup header shows the tab name:</strong> avoids confusion with other tabs',
            '<strong>Auto WP/Visa Expiry:</strong> the expiry date used for this tab',
            '<strong>Auto MOU Group:</strong> the MOU type for this tab',
            '<strong>Save Settings:</strong> applies to this tab only',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Open a tab → click <strong>"Auto Settings"</strong></li>
            <li>Fill in the target expiry dates</li>
            <li>Click Save</li>
            <li>Employees with a matching expiry date → are auto-pulled into the tab</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">Track progress + tick off steps</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/04-progress-tracking',
        'alt' => 'Employer card with progress steps',
        'caption' => 'Employer card — each employee has steps to tick off',
        'callouts' => [
            '<strong>Employee card:</strong> color changes based on progress (5 colors)',
            '<strong>Step checkboxes:</strong> tick as you complete each step',
            '<strong>Card floats to the top:</strong> every time you tick something/edit data → refresh, and the most recent card is on top',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Open the employer card</li>
            <li>Tick the checkbox for each step</li>
            <li>The employee card's color changes based on progress</li>
            <li>Once fully renewed → it turns green → click <strong>"Finish"</strong> to close the job</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Summary stat cards + overview</h2>

    @include('manuals.training._screenshot', [
        'src' => 'renewal_resolution/05-stats-cards',
        'alt' => 'Summary stat cards at the top of the page',
        'caption' => 'Summary Stat Cards — an overview of this tab',
        'callouts' => [
            '<strong>Total employees:</strong> total in this resolution',
            '<strong>Total cancelled:</strong> those that were cancelled',
            '<strong>Recorded in the database:</strong> completed',
            '<strong>Biometrics Collected:</strong> biometrics already collected',
            '<strong>Total employers:</strong> number of employers in this tab',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>Click a stat card:</strong> = filters to that category immediately (e.g. click "Finished" → filters to only completed ones)
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: An employee already expired, can they still be renewed?</dt>
        <dd>A: Depends on the resolution's conditions — some resolutions allow retroactive renewal, check the ministerial regulation first</dd>

        <dt>Q: Why can't I renew this employee?</dt>
        <dd>A: Check that the employee's status is "Active" (not terminated/contract-ended)</dd>

        <dt>Q: An employee got bumped out after I updated the expiry date?</dt>
        <dd>A: This shouldn't happen — the system is "add-only" and doesn't auto-remove anyone (this was a bug that has since been fixed)</dd>
    </dl>
</section>
