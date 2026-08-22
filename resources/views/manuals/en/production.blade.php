{{-- User Manual: P Production (English) --}}

<h4><i class="bi bi-clipboard-data-fill me-2"></i>What is this menu?</h4>
<p>
    The <strong>"P Production"</strong> menu is the hub for all jobs currently being processed by the office —
    from a new customer that closed via the Sales menu → entering document preparation (Pre-Production)
    → then handed off to Workflow for further processing
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — full access</li>
    <li><span class="manual-role">Caretaker</span> — view only, can't edit some things</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Summary stat cards</strong> at the top — jobs nearing deadline, in progress, awaiting review</li>
    <li><strong>Filter bar</strong> — filter by employer, job owner, job type (MOU/Visa)</li>
    <li><strong>Job cards</strong> — each card shows the sales rep's photo + employer name + number of employees + each step's status</li>
    <li><strong>"Send to Workflow" button</strong> — sends the job into the Workflow process (only for roles with the approve-production permission)</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. Open a job to view its details</h5>
<div class="manual-step">
    Click a job card → opens the Edit Job page with multiple tabs: Employees, Documents, Finance, Timeline
</div>

<h5>2. Edit each employee individually</h5>
<div class="manual-step">
    On the "Employees" tab — each employee card has buttons for edit, view documents, upload photo.
    Use the <strong>Document Scanner</strong> to capture photos straight from the camera into the system
</div>

<h5>3. Add a Custom Field</h5>
<div class="manual-step">
    Some jobs need extra special data — click the "Fields" button on the card → add the new field you need
    (e.g. "Medical certificate number", "Orientation date")
</div>

<h5>4. Send a job to Workflow</h5>
<div class="manual-step">
    Once the documents are ready → click <strong>"Send to Workflow"</strong>
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Only for roles with the <code>approve-production</code> permission (Admin/Super Admin)
    </div>
</div>

<h5>5. Send the whole batch at once (Bulk Send)</h5>
<div class="manual-step">
    If there are multiple employees under the same MOU — click "Send Whole Batch" on the MOU card to send them all at once
</div>

<h5>6. The Financial tab on a job</h5>
<div class="manual-step">
    When you open Edit Job → click the <strong>"Financial"</strong> tab, or click "Finance" on the employer's card
    <ul class="mb-0">
        <li>You can <strong>create multiple financial tabs</strong> within a single job (e.g. "MOU Myanmar Service Fee", "Employer Change Installment")
            — click <strong>"+ Add Tab"</strong> → name the tab (required — cannot be blank / cannot duplicate)</li>
        <li>Click the <i class="bi bi-pencil-square"></i> icon, or <strong>double-click the tab name</strong>, to rename it</li>
        <li>Click the <i class="bi bi-trash"></i> icon to delete a tab (a confirmation showing the impact appears)</li>
    </ul>
</div>

<h5>7. Set per-head pricing + installment notes</h5>
<div class="manual-step">
    In the Financial tab, select the <strong>"Per-head"</strong> mode:
    <ul class="mb-0">
        <li>Add pricing tiers — each tier has a price + headcount + a <strong>note</strong></li>
        <li>Click the <strong>note box</strong> or the <i class="bi bi-pencil-square"></i> icon to open a larger popup editor (500-character counter + Ctrl+Enter to save)</li>
        <li>This note also appears on any invoice/receipt issued for that tier</li>
        <li>Click <i class="bi bi-trash"></i> to delete a tier — a confirmation appears, warning if employees are still assigned to it</li>
    </ul>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Check overall status at a glance:</strong> the card's color shows urgency — yellow = nearing deadline, red = past deadline
</div>

<div class="manual-tip">
    <strong>Pre-Production vs. Workflow:</strong> jobs in this menu are in Pre-Production (document preparation).
    After clicking Send to Workflow, the job moves into the "Workflow" menu
</div>

<div class="manual-warn">
    <strong>Careful:</strong> once a job has been sent to Workflow, it can no longer be edited in Production — edits must be made in Workflow instead
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: Why don't I see the "Send to Workflow" button?</dt>
    <dd>A: Your role doesn't have the approve-production permission — ask an Admin to add it</dd>

    <dt>Q: Where did the job go after I clicked Send to Workflow?</dt>
    <dd>A: It moved into the "Workflow" menu — filter by employer name to find it</dd>

    <dt>Q: An employee resigned while their job was still in Production?</dt>
    <dd>A: Open the employee's card → click "Terminate" — the job is automatically removed from Production</dd>
</dl>
