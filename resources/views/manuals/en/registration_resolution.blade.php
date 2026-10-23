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
    <li><span class="manual-role">Caretaker</span> — can do day-to-day work (employee data, appointments) but cannot change workflow structure or finance data; ticks steps only when granted the "update progress steps" permission</li>
    <li><span class="manual-role">Employer</span> (customer account) — cannot open this menu</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Resolution tabs</strong> — each tab is 1 cabinet resolution (e.g. Nov 2023 Resolution, Mar 2024 Resolution)</li>
    <li><strong>Search box</strong> — type part of an employer or employee name and matching suggestions appear instantly (no need to type the full name); click a suggestion to filter the list right away · searches name (Thai / English), passport no., work permit no., ID no., RA no., pink card no. and <strong>request no.</strong> — including the request no. on Workflow / Pre-Production work cards and on registration / renewal resolution cards · the suggestions shown while typing use the same rules as pressing Search</li>
    <li><strong>Two search boxes</strong> — box 1 takes an employee (name / passport / any number), an employer or a job owner; pick from the list: an employee = only that person, an employer = only that employer, a job owner = every employer under them (typing and pressing Enter searches the typed text) · box 2 opens once box 1 has a value and narrows box 1's result further, with the same kinds of text · inside an employer card, a term that matches the employer / job owner shows all of its employees; a term that matches an employee shows only the matching ones</li>
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

<h5>7. Selecting completed employees and 3-way Select All</h5>
<div class="manual-step">
    A completed employee's checkbox is hidden during the 24-hour Undo window; after it the employee moves into the history — click the <strong>Saved to Database</strong> box to list and select everyone finished. Select All on an employer with both completed and not-completed employees asks: <strong>All / Completed only / Not completed only</strong> (for export, bulk edit, download).
</div>

<h5>8. Teams</h5>
<div class="manual-step">
    <strong>Manage Team</strong> on the employee card → pick or create a team (e.g. "Batch 1"). Teams are separate per resolution tab and per employer; rename/delete updates instantly without reloading.
    <br><strong>Finished employees keep their team:</strong> renaming / deleting a team or changing someone's team only affects employees not finished yet — finished ones stay in their old team and can be found by that name in the history.
    <br><strong>History (clock button on the employer card):</strong> a finished employee stays in the list for 24 hours, then moves into that employer's <strong>Job History</strong> popup. View one month at a time (opens on the latest month) or a date range; search employees (name / passport / request no. / RA no.) and teams — typing a search looks in every month. People finished before dates were kept are under "No date (older records)". To select finished people for export / download, click the <strong>Saved to Database</strong> box, which still lists everyone finished.
</div>

<h5>9. Appointments and request numbers per tab</h5>
<div class="manual-step">
    Appointment date/place and request number are stored <strong>per resolution tab</strong> — setting them in one tab never shows up in another tab or menu.
</div>

<h5>10. Tab badge switch (Super Admin)</h5>
<div class="manual-step">
    Next to the tab edit button — for a tab prepared in advance but not live yet: employees still work normally but the "in resolution group…" card badge and notification badge are hidden, so it doesn't clash with the current round.
</div>

<h5>11. Move attachment files for many employees (Super Admin)</h5>
<div class="manual-step">
    Tick employees → <strong>Move Attachment Files</strong> → swap / move & delete source / <strong>merge into B</strong> (combines both PDFs). Selected employees only; can be delegated to specific Admin/Staff.
</div>

<h5>12. Deleting a tab that already has bills</h5>
<div class="manual-step">
    Deleting a tab hides it for 7 days (restorable), then deletes it permanently. Jobs <strong>with bills</strong> are never deleted — bills and payment history stay and can still be collected or credited in Finance (red "tab deleted" badge).
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Progress badge colors:</strong> the system uses 4 color levels so you can quickly see which step each employee is on
</div>

<div class="manual-tip">
    <strong>The status filter shows only matches:</strong> if you filter "Visa only", you'll only see employers with employees doing visa only
</div>
<div class="manual-tip">
    <strong>Send to a LINE chat:</strong> drag the <i class="bi bi-grid-3x2-gap-fill"></i> button on a card into the chat, or click it to copy text / a card image / share — see the Employees manual, section 11
</div>
<div class="manual-tip">
    <strong>Operator (<i class="bi bi-person-badge-fill"></i> button, bottom-right of the card):</strong> pick a user, or "Outside person (type a name)" and type any name, or "None" to clear — the same in Registration, Renewal (every tab), Pre-Production and Workflow · typed names show "(outside)" · choosing an operator never changes the card's status
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

<div class="manual-tip">
    <strong>Details on the employee card:</strong> under the name the card shows up to 6 details — the same set as the Employees menu cards (Super Admin picks and orders them under Super Admin Settings → "Employee Card Details") · nationality always shows · each detail has a <strong>copy button</strong> <i class="bi bi-copy"></i> — one click copies it, no text selecting · editing the employee updates the card right away
</div>
