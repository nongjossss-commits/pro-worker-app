{{-- User Manual: Central Trash (English) --}}

<h4><i class="bi bi-trash-fill me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Central Trash"</strong> menu holds <strong>all deleted data</strong> in the system —
    employers, employees, delegates, addresses, etc. — all <strong>in one place</strong>,
    so it's easy to <strong>Restore</strong> or <strong>Force Delete</strong>
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — can access</li>
    <li>Requires the <code>view-trash</code> permission + <code>restore-*</code> or <code>force-delete-*</code> depending on the data type</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Tabs</strong> — split by data type (Employers, Employees, Delegates, etc.)</li>
    <li><strong>Table</strong> — shows deleted items + date deleted + who deleted them</li>
    <li><strong>Restore button + Permanent Delete button</strong> on each row</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. Restore a deleted item</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Select the data type tab (e.g. Employees)</li>
        <li>Find the record you want to restore</li>
        <li>Click the <i class="bi bi-arrow-counterclockwise"></i> "Restore" button</li>
        <li>Confirm — the record returns to its original menu</li>
    </ol>
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i>
        Restoring an Employee may exceed the system quota — if so, restoration will fail until the quota is increased
    </div>
</div>

<h5>2. Permanently delete (don't do this casually!)</h5>
<div class="manual-warn">
    Click the <i class="bi bi-x-circle-fill text-danger"></i> "Permanent Delete" button → confirm twice → the record is <strong>gone forever</strong> and cannot be restored
    <br><br>
    Only use this for:
    <ul class="mb-0">
        <li>Duplicate data entered by mistake</li>
        <li>Test data that was forgotten and left behind</li>
        <li>Legacy data that genuinely needs to be cleaned up</li>
    </ul>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Soft Delete vs Force Delete:</strong> Soft Delete = moved to trash (can be restored), Force Delete = truly deleted (cannot be restored)
</div>

<div class="manual-tip">
    <strong>After restoring:</strong> check that the restored record doesn't have conflicting data (e.g. an employer that's been restored while its employees are still sitting in the trash)
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: I permanently deleted something, can I get it back?</dt>
    <dd>A: <strong>No</strong> — you would need to check a database backup with your server administrator</dd>

    <dt>Q: How long does the trash keep items?</dt>
    <dd>A: Indefinitely — the system doesn't auto-delete; you must delete manually if you want to clean up</dd>

    <dt>Q: I deleted an Employer, where did the employees go?</dt>
    <dd>A: The employees are still there as normal — only the link to the Employer is broken, so you'll need to reassign it</dd>
</dl>
