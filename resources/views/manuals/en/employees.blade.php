{{--
    User Manual: Employees (English)
--}}

<h4><i class="bi bi-people-fill me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Employees"</strong> menu is the database of foreign workers (Myanmar/Laos/Cambodia) managed by the office.
    It stores basic information (full name, nationality, passport number, visa, work permit, MOU contract)
    and links to the <strong>employer</strong> the employee is working for.
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — full access, including terminate/restore/permanent delete</li>
    <li><span class="manual-role">Staff</span> — can view / add / edit, cannot permanently delete</li>
    <li><span class="manual-role">Caretaker</span> — can view / add / edit + terminate employees</li>
    <li><span class="manual-role">Employer</span> — can only see their own employees</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Filter bar</strong> at the top — filter by nationality, visa status, employer, search by name/passport number</li>
    <li><strong>Status tabs</strong> — Active / Terminated / Contract Ended / Trash</li>
    <li><strong>Employee cards</strong> — each card shows a photo, name, nationality, visa status, employer</li>
    <li><strong>"+ Add New Employee"</strong> and <strong>"Import from Excel"</strong> buttons in the top-right corner</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>Common tasks</h4>

<h5>1. Add an employee one at a time</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Click <strong>"+ Add New Employee"</strong> in the top-right corner</li>
        <li>Fill in the basic info — full name (Thai/English), gender, nationality, date of birth</li>
        <li>Fill in the <strong>passport</strong> number + expiry date</li>
        <li>Select the <strong>employer</strong> this employee works for</li>
        <li>Upload a photo + documents (passport copy, visa, work permit) as available</li>
        <li>Click <strong>"Save"</strong></li>
    </ol>
</div>

<h5>2. Import employees in bulk (Excel Bulk Import)</h5>
<div class="manual-step">
    Best for employers bringing in many employees at once
    <ol class="mb-0 mt-2">
        <li>Click <strong>"Import from Excel"</strong></li>
        <li>First download the <strong>"Template"</strong> file — the sample has the column headers the system requires</li>
        <li>Fill in employee data in Excel following the column headers (1 person per row)</li>
        <li>Upload the file back into the system</li>
        <li>Check the <strong>preview</strong> before confirming — the system shows the records to be added and warns about any errors</li>
        <li>Click <strong>"Confirm"</strong> — the system adds all of them at once</li>
    </ol>
</div>

<h5>3. Terminate / end contract</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the card of the employee to be terminated</li>
        <li>Click <strong>"Terminate / End Contract"</strong></li>
        <li>Select a reason + the termination date</li>
        <li>Click <strong>"Confirm"</strong> — the status changes to "Terminated" and no longer counts toward the active quota</li>
    </ol>
    <div class="manual-tip mt-2 mb-0">
        <i class="bi bi-info-circle-fill"></i> <strong>Tip:</strong>
        A terminated employee isn't gone — they stay under the "Terminated" tab and can be restored if re-hired
    </div>
</div>

<h5>4. Restore a terminated employee (Reinstate)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Click the <strong>"Terminated"</strong> status tab at the top</li>
        <li>Find the employee you want to restore</li>
        <li>Click <strong>"Reinstate"</strong></li>
    </ol>
</div>

<h5>5. Permanently delete an employee (Force Delete)</h5>
<div class="manual-warn">
    <strong>Admin/Super Admin only</strong> — used for employees entered by mistake or duplicated; once deleted it <strong>cannot be undone</strong>
    <br>
    Steps: go to the "Trash" tab → click the permanent delete icon → confirm twice
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Things to know / Tips</h4>

<div class="manual-tip">
    <strong>Maximum employee quota:</strong> Super Admin can limit the number of active employees
    (set in Super Admin Settings → Quota Settings) — if the quota is full, you cannot add new employees
</div>

<div class="manual-tip">
    <strong>Employees vs. Delegates:</strong> "Employee Info" in the left menu for some roles
    actually refers to <strong>employer Delegates</strong>, not migrant workers — these are two separate menus
</div>

<div class="manual-tip">
    <strong>Visa/passport nearing expiry:</strong> the system automatically alerts you in <strong>Notifications</strong>
    when something is about to expire — check every morning
</div>

<div class="manual-warn">
    <strong>Before permanently deleting an employee:</strong> make sure no Production / Workflow job or tax invoice
    still references this employee
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>

<dl>
    <dt>Q: I can't add an employee, it shows "quota exceeded"?</dt>
    <dd>A: The system quota is full — contact Super Admin to increase it, or terminate old employees no longer in use</dd>

    <dt>Q: Why does the Excel import show an error?</dt>
    <dd>A: Check that the column headers in your Excel file match the downloaded template exactly — don't rename the columns</dd>

    <dt>Q: An employee moved to a new employer?</dt>
    <dd>A: Edit the employee → change "Employer" to the new one → Save</dd>

    <dt>Q: I only see employees from one company?</dt>
    <dd>A: If you're logged in with the "Employer" role, you'll only see your own employees — you need an Admin/Staff role or higher to see everyone</dd>
</dl>
