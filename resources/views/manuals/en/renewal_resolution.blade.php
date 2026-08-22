{{-- User Manual: Renewal Resolution (English) --}}

<h4><i class="bi bi-arrow-clockwise me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Renewal Resolution"</strong> menu manages
    <strong>cabinet resolutions</strong> regarding <strong>renewing</strong> expiring migrant worker documents,
    such as Work Permit renewal, Visa renewal, MOU renewal
</p>
<p>
    Similar to <strong>Registration Resolution</strong> — but focused on renewing workers already in the system, not registering new ones
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — can access</li>
    <li><span class="manual-role">Caretaker</span> — view only</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Renewal Resolution tabs</strong> — each tab is one renewal round (e.g. "2024 Renewal Round 1")</li>
    <li><strong>Employer + Employee cards</strong> — uses the same mechanism as Registration Resolution</li>
    <li><strong>Progress filter</strong> — visa-only, work-permit-only, both</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. Select a Renewal Resolution</h5>
<div class="manual-step">
    Click the tab for the resolution you want
</div>

<h5>2. Register an employee into the resolution</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the employer's card</li>
        <li>Click "Add Employee to Resolution"</li>
        <li>Select employees who are <strong>about to expire</strong> (the system highlights them for you)</li>
        <li>Click "Confirm"</li>
    </ol>
</div>

<h5>3. View overall progress</h5>
<div class="manual-step">
    The summary card at the top shows: total employees in this resolution, completed, and remaining
</div>

<h5>4. Auto-apply</h5>
<div class="manual-step">
    The <strong>Workflow MOU Auto-apply</strong> system works together with this resolution —
    renewal work done in Workflow is auto-applied back to this resolution every 24 hours
</div>

<h5>5. Auto Settings — per-tab configuration</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the resolution tab you want → click <strong>"Auto Settings"</strong> in the top-right corner</li>
        <li>The popup header shows the <strong>tab name</strong> (e.g. "31/03/2026") + a note that this setting only applies to this tab</li>
        <li>Fill in:
            <ul>
                <li><strong>Auto Work Permit Expiry Date</strong> — the target WP expiry date</li>
                <li><strong>Auto Visa Expiry Date</strong> — the target Visa expiry date</li>
                <li><strong>Auto MOU Group</strong> — the target MOU group</li>
            </ul>
        </li>
        <li>Click Save → applies to <strong>this tab only</strong>, does not affect other tabs</li>
        <li>Each tab has its own Auto Settings — employees in the 31/03/2026 tab are evaluated for color/progress based on that tab's settings, not any other tab's</li>
    </ol>
</div>

<h5>6. Employee progress color system</h5>
<div class="manual-step">
    Employees on each card get a color showing their progress, based on Auto Settings:
    <ul class="mb-0">
        <li>⚪ <strong>none</strong> = not yet renewed</li>
        <li>🟦 <strong>visa_only</strong> = visa renewed (waiting on WP)</li>
        <li>🟧 <strong>work_permit_only</strong> = WP renewed (waiting on visa)</li>
        <li>🟩 <strong>both</strong> = fully renewed, ready to close</li>
        <li>✅ <strong>completed</strong> = closed out</li>
    </ul>
</div>

<h5>7. Auto-pull employees into the menu automatically</h5>
<div class="manual-step">
    An employee whose WP or Visa expiry matches a tab's Auto Settings is <strong>auto-pulled into that tab</strong> immediately whenever the date is updated
    <br>
    <strong>"Add-only" behavior:</strong> an employee already in the menu <strong>is never bumped out</strong> when dates are updated — only their color changes based on progress (they can only be removed by manually finishing or cancelling)
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Start renewing 60 days before expiry:</strong> the system highlights employees nearing expiry
    in the Notifications menu + the Incomplete Data menu
</div>

<div class="manual-tip">
    <strong>Registration vs. Renewal Resolution:</strong> Registration = new employees entering the system, Renewal = existing employees about to expire
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: An employee already expired, can they still be renewed?</dt>
    <dd>A: Depends on the resolution's conditions — some resolutions allow retroactive renewal, check the ministerial regulation first</dd>

    <dt>Q: Why can't I renew this employee?</dt>
    <dd>A: Check that the employee's status is "Active" (not terminated/contract-ended)</dd>
</dl>
