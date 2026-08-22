{{-- Training Edition: Registration Resolution (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-card-checklist"></i> {{ __('Registration Resolution') }} — {{ __('Manage cabinet resolutions for worker registration') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Registration Resolution"</strong> menu manages <strong>cabinet resolutions</strong> regarding registering new migrant workers,
        e.g. the September 16 cabinet resolution, a special COVID-era resolution — the system supports <strong>multiple resolutions at once</strong> as tabs
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
    <h2 class="slide-title">Select a Resolution Tab</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/01-tab-bar',
        'alt' => 'Resolution tab bar with a create-new button',
        'caption' => 'Resolution Tab Bar — each tab is one cabinet resolution round',
        'callouts' => [
            '<strong>Tab Bar:</strong> each tab is 1 resolution round (e.g. "Sep 16 \'24 Cabinet Resolution")',
            '<strong>+ Add Tab:</strong> create a new resolution (Super Admin only)',
            '<strong>⚙️ Edit Tab:</strong> rename / delete a resolution',
            '<strong>⭐ Default:</strong> the default tab shown when you first enter',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Registration Resolution</strong></li>
            <li>Click the tab of the resolution you want</li>
            <li>The page refreshes to show that resolution's data</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">The employee progress color system</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/02-color-legend',
        'alt' => 'Legend showing the 5 progress colors',
        'caption' => 'Color Legend — 5 colors show each employee\'s progress',
        'callouts' => [
            '<strong>⚪ Not renewed yet:</strong> hasn\'t started',
            '<strong>🟣 Visa updated:</strong> visa renewed, WP pending',
            '<strong>🟡 Work permit updated:</strong> WP renewed, visa pending',
            '<strong>🔵 Both renewed:</strong> fully renewed, ready to close',
            '<strong>🟢 Finalized:</strong> closed out',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>Colors change automatically:</strong> whenever an employee's expiry date is updated to match the Auto Settings target → the color changes immediately
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Configure Auto Settings (per-tab)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/03-auto-settings',
        'alt' => 'Auto Settings popup showing the tab name and visa/wp/mou fields',
        'caption' => 'Auto Settings — configured separately per resolution tab',
        'callouts' => [
            '<strong>Popup header:</strong> shows the tab name, so it\'s clear this only applies to that tab',
            '<strong>Auto WP Expiry:</strong> the target work permit expiry date',
            '<strong>Auto Visa Expiry:</strong> the target visa expiry date',
            '<strong>Auto MOU Group:</strong> the target MOU type',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Open the resolution tab you want → click <strong>"Auto Settings"</strong></li>
            <li>Fill in the WP + Visa expiry dates + MOU Group</li>
            <li>Click Save → applies to <strong>this tab only</strong></li>
            <li>Employees with matching dates → are auto-pulled into the menu immediately</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>Add-only:</strong> employees already in the menu are <strong>never bumped out</strong> when dates change — only clicking Complete/Cancel removes them
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">Tick steps + track progress</h2>

    @include('manuals.training._screenshot', [
        'src' => 'registration_resolution/04-progress',
        'alt' => 'Employer card with employees and steps',
        'caption' => 'Employer Card — employees with a checkbox for each step',
        'callouts' => [
            '<strong>Employer card:</strong> groups together all of that employer\'s employees',
            '<strong>Checkbox steps:</strong> tick to record a step as done',
            '<strong>Most recent card floats to the top:</strong> tick something, then refresh → that card moves to the top',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Open the employer card → view the employee list</li>
            <li>Tick the checkbox for each completed step</li>
            <li>The system logs the timestamp automatically</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: What's the difference between Registration Resolution and Renewal Resolution?</dt>
        <dd>A: Registration = new employees entering the system for the first time, Renewal = existing employees nearing expiry</dd>

        <dt>Q: Do one tab's Auto Settings overlap with another tab's?</dt>
        <dd>A: No — each tab has its own Auto Settings (per-tab keys)</dd>

        <dt>Q: Why did an employee disappear from the menu?</dt>
        <dd>A: The system <strong>doesn't auto-remove</strong> anyone — only manually clicking "Complete"/"Cancel", or the employee transferring employer, removes them</dd>
    </dl>
</section>
