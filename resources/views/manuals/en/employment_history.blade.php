{{-- User Manual: Employment History (English) --}}

<h4><i class="bi bi-person-badge me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Employment History"</strong> menu gathers <strong>every employee</strong> that has ever been in the system,
    whether <em>currently active</em>, <em>terminated (Resigned)</em>, <em>contract ended</em>, or <em>already moved to a different employer</em>.
    Used to <strong>review past history</strong>, <strong>search for old employees</strong>, and <strong>bulk-transfer</strong> former employees to a new employer
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — full access</li>
    <li><span class="manual-role">Caretaker</span> — only sees employees of the employers they manage</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Filter bar</strong> at the top — search, filter by nationality/MOU type/pink card/passport type</li>
    <li><strong>Top-right corner</strong> — Export CSV button, view toggle (card/table), items-per-page selector</li>
    <li><strong>Employee list</strong> — shows everyone, both active and inactive (including terminated/contract-ended)</li>
    <li><strong>Bulk Action Bar</strong> (floating at the bottom) — after ticking multiple employees → transfer employer / Export / Generate PDF</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. Search past employees</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Type a name / passport number in the "Search..." box</li>
        <li>Select other filters (nationality / MOU / passport) as needed</li>
        <li>Click "Filter" — results include both active + inactive employees</li>
        <li>Click "Clear" to reset the filters</li>
    </ol>
</div>

<h5>2. Bulk-transfer old employees to a new employer</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Tick the checkbox for the employees you want to move (multiple selection supported)</li>
        <li>The Bulk Action Bar pops up at the bottom → click "Actions" → <strong>"Transfer Employer"</strong></li>
        <li>Select the destination employer → confirm</li>
        <li>The system moves all selected employees to the new employer immediately</li>
    </ol>
</div>

<h5>3. Export data as CSV / Excel</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Filter to the data you want first (if you only need a specific group)</li>
        <li>Click "Export CSV" in the top-right corner — the file downloads immediately</li>
        <li>Or use the Bulk Action Bar → "Advanced Export" — lets you pick which columns to include</li>
    </ol>
</div>

<h5>4. Batch-generate PDFs</h5>
<div class="manual-step">
    Tick multiple employees → Bulk Action Bar → "Automated PDF" → select a template → the system generates PDFs for everyone at once
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>How this differs from the "Employees" menu:</strong> the regular Employees menu only shows active employees,
    while this menu shows <strong>everyone</strong>, including those terminated/contract-ended — use it when you need to find someone old or review past history
</div>

<div class="manual-tip">
    <strong>Table vs. card view:</strong> table view is best for comparing many people's data side by side,
    card view is best for reviewing one person's details at a time with photo and badges
</div>

<div class="manual-warn">
    <strong>Transferring an employer:</strong> a transferred employee's <strong>employer_id changes</strong>.
    The system will <strong>auto-cancel</strong> that employee's pending "notify out" record immediately (if any)
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: Why do I see fewer employees than expected?</dt>
    <dd>A: Check the user's permissions — if they're a Caretaker, they only see employees for the employers they manage</dd>

    <dt>Q: After transferring an employer, the employee immediately disappeared from the "notify out" menu?</dt>
    <dd>A: That's correct — when employer_id changes, the system auto-cancels that employee's pending "notify out" record, since "notify out" was for leaving the old employer and no longer applies</dd>

    <dt>Q: Do deleted (trashed) employees show up in this menu?</dt>
    <dd>A: No — check the "Central Trash" menu instead; they can be restored from there</dd>

    <dt>Q: What columns does the CSV export include?</dt>
    <dd>A: The basic set — use "Advanced Export" to choose your own columns (including MOU, expiry date, status, etc.)</dd>
</dl>
