{{-- User Manual: Registration Resolution (English) --}}

<h4><i class="bi bi-file-earmark-text-fill me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Registration Resolution"</strong> menu is used to manage
    <strong>cabinet resolutions</strong> regarding periodic new registration rounds for migrant workers issued by the government.
    The system stores the forms, timelines, and employees entering that resolution
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — full access</li>
    <li><span class="manual-role">Caretaker</span> — view only</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Resolution tabs</strong> — each tab is 1 cabinet resolution (e.g. Nov 2023 Resolution, Mar 2024 Resolution)</li>
    <li><strong>Employer cards</strong> — shows employers who have employees in this resolution</li>
    <li><strong>Status filter</strong> — filter by step (pending, in progress, done)</li>
    <li><strong>Progress filter</strong> — filter by visa-only / both / renewal</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. Select the resolution you want</h5>
<div class="manual-step">
    Click that resolution's tab → shows every employer + employee in that resolution
</div>

<h5>2. Register an employee into the resolution</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the employer's card</li>
        <li>Click "Add Employee to Resolution"</li>
        <li>Select the employees to include in this resolution</li>
        <li>Click "Confirm"</li>
    </ol>
</div>

<h5>3. Track each employee's status</h5>
<div class="manual-step">
    Employee cards show:
    <ul class="mb-0">
        <li>Light blue = visa only (only doing visa)</li>
        <li>Dark blue = both (doing visa + work permit)</li>
        <li>Solid border = the highest step completed so far</li>
    </ul>
</div>

<h5>4. Filter with multiple criteria</h5>
<div class="manual-step">
    Hold Ctrl/Cmd to select several statuses at once — filter by multiple progress states in one go
</div>

<h5>5. Auto Settings — per-tab configuration</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the resolution tab you want → click <strong>"Auto Settings"</strong></li>
        <li>The popup header shows the <strong>tab name</strong> + a note that it only applies to this tab</li>
        <li>Fill in the Auto WP/Visa Expiry + MOU Group → Save</li>
        <li>Each tab has its own independent Auto Settings, with no overlap</li>
    </ol>
</div>

<h5>6. Auto-pull employees into the menu automatically (Add-only)</h5>
<div class="manual-step">
    An employee whose WP or Visa expiry matches the Auto Settings is <strong>auto-pulled into the menu immediately</strong>
    <br>
    An employee already in the menu <strong>is never bumped out</strong> when dates are updated — only their color changes based on progress (none / visa_only / work_permit_only / both)
    <br>
    Only manually clicking Finish / Cancel removes an employee from the menu
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Progress badge colors:</strong> the system uses 4 color levels so you can quickly see which step each employee is on
</div>

<div class="manual-tip">
    <strong>The status filter shows only matches:</strong> if you filter "Visa only", you'll only see employers with employees doing visa only
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: Can I add a new resolution?</dt>
    <dd>A: Yes — Super Admin can add one via the Resolution Tabs Settings menu</dd>

    <dt>Q: Can one employee be in multiple resolutions?</dt>
    <dd>A: Yes — one employee can be part of several resolutions, and will appear on the card of every resolution they're in</dd>

    <dt>Q: Why did an employer's card disappear?</dt>
    <dd>A: If that employer has no employees within the current filter scope, their card won't be shown</dd>
</dl>
