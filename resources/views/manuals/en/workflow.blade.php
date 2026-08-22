{{-- User Manual: Workflow (English) --}}

<h4><i class="bi bi-diagram-3-fill me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Workflow"</strong> menu is the hub for jobs moving through the office's various process steps —
    e.g. filing with the Department of Employment, passport processing, visa applications, work permit issuance, etc.
    Each job moves through predefined "Steps"
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — can access</li>
    <li><span class="manual-role">Caretaker</span> — view only, cannot edit</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Step bar</strong> at the top — shows each Step a job must pass through + the number of jobs in each Step</li>
    <li><strong>Filter bar</strong> — filter by step, employer, job type</li>
    <li><strong>Job cards</strong> — shows all jobs currently in the selected step</li>
    <li><strong>"Auto-apply MOU" button</strong> — for MOU renewal jobs the system handles automatically</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. View jobs in each step</h5>
<div class="manual-step">
    Click a Step on the step bar → shows only jobs currently in that Step
</div>

<h5>2. Move a job to the next step</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the job's card</li>
        <li>Click <strong>"Continue / Next Step"</strong></li>
        <li>Fill in the information the new Step requires (e.g. receipt number, date)</li>
        <li>Click "Confirm" — the job moves to the new Step</li>
    </ol>
</div>

<h5>3. Send a job back a step</h5>
<div class="manual-step">
    If there's an error, use the <strong>"Send Back"</strong> button to return the job to the previous Step,
    or <strong>"Send Back to Pre-Production"</strong> to return it to the Production menu
</div>

<h5>4. Set additional fields (Custom Fields)</h5>
<div class="manual-step">
    Click the "Fields" button on an MOU card → add the extra data your step requires
    (e.g. "Pink card number", "Interview appointment date")
</div>

<h5>5. Auto-apply MOU renewal</h5>
<div class="manual-step">
    The system <strong>auto-applies</strong> MOU renewal jobs automatically every 24 hours.
    Admin can configure this in Super Admin Settings → the Workflow section
</div>

<h5>6. Imported MOU — create a Demand Card</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the <strong>"Imported MOU"</strong> tab → click <strong>"Create Job"</strong></li>
        <li>Select Work Type = Imported MOU</li>
        <li><strong>Select an employer</strong> — you can type to search (Thai name/EN/code) instead of scrolling</li>
        <li><strong>Select the Imported MOU type</strong>:
            <ul>
                <li><span class="badge bg-success">Return</span> = the employee is already in Thailand → their data can be recorded immediately</li>
                <li><span class="badge bg-primary">New from Origin</span> = a new person from the origin country → no employee data yet, awaiting Demand → Name list</li>
                <li>If you're not sure yet → leave it blank; the system will show it as <span class="badge bg-warning text-dark">Pending Classification</span></li>
            </ul>
        </li>
        <li>Fill in nationality + the number of male/female workers to import</li>
        <li>Click "Create Demand Card"</li>
    </ol>
</div>

<h5>7. Change the Imported MOU type later</h5>
<div class="manual-step">
    On the Workflow "Imported MOU" tab → click the <strong>colored badge (Return/New/Pending)</strong> on the card → select a new type → click Save
</div>

<h5>8. The "Notify Out" tab — enter a date and reason before closing</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the <strong>"Notify Out"</strong> tab → click <strong>"+ Add Employee"</strong></li>
        <li>You can search <strong>any employee in the system</strong> (global search) — not limited to your employer scope</li>
        <li>An employee currently in a <strong>renewal / employer transfer</strong> job can still be added to notify_out (both can run in parallel)</li>
        <li>An employee already in notify_out <strong>cannot be added again</strong> until it's completed</li>
        <li>The system will <strong>auto-group by the employee's current employer</strong> (1 employer = 1 order)</li>
    </ol>
</div>

<div class="manual-step">
    <strong>Before clicking Finish on a notify_out entry:</strong>
    <ol class="mb-0">
        <li>The employee card will show a <strong>yellow bar</strong> at the bottom</li>
        <li>Fill in the <strong>notify-out date</strong> (date picker — required)</li>
        <li>Select a <strong>reason</strong> (dropdown: Resigned / Dismissed / Contract Ended / Employer Change / Absconded / Deceased) — or type your own</li>
        <li>The system autosaves every change → the badge turns to a green <strong>"Ready to Finish"</strong></li>
        <li>Click "Finish" → the system will immediately <strong>auto-update employee.terminated_at + termination_reason + status='resigned'</strong></li>
        <li><strong>If the notify-out date isn't filled in yet</strong> → you can't click Finish; it will warn "you must enter a notify-out date first"</li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Use multiple filters:</strong> you can select several Steps at once to see an overview
</div>

<div class="manual-tip">
    <strong>Job owner:</strong> use the "Job Owner" filter to see only the jobs handled by your own staff member
</div>

<div class="manual-warn">
    <strong>Careful:</strong> moving a Step affects the notification sent to the customer — double-check before clicking
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: A job is missing — I can't find it in Workflow?</dt>
    <dd>A: Check your filters — it may be in a Step you haven't selected; try selecting "All" or filtering by employer name</dd>

    <dt>Q: Can I add a new Step?</dt>
    <dd>A: Yes — contact an Admin to add it through the Step settings menu</dd>

    <dt>Q: Can I delete a Step that's currently in use?</dt>
    <dd>A: No — the jobs inside it would get stuck; move them to another Step first</dd>

    <dt>Q: An employee's notify_out entry disappeared from the tab on its own?</dt>
    <dd>A: The system <strong>auto-cancels</strong> a pending notify_out when the employee is transferred to a new employer — because notify_out means "leaving the old employer", which no longer applies. You can view the history in the "Activity Logs" menu, or create a new notify_out under the new employer if needed</dd>

    <dt>Q: Can I do a manual notify_out from the Employees menu?</dt>
    <dd>A: Yes — Employees menu → "Notify Out" button → enter the date + reason (Workflow isn't required; you can do it directly there if you need it done quickly)</dd>
</dl>
