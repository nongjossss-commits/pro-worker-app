{{--
    User Manual: Employers (English)
    Audience: New office staff who may have attended training but forgotten steps.
    Tone: Friendly, step-by-step, plain English.
--}}

<h4><i class="bi bi-building me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Employers"</strong> menu is the database of companies or individuals who hire foreign workers through our office.
    It stores basic information such as company name, tax ID, address, phone number, and various documents,
    and links to the <strong>employees</strong> working for that employer.
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — full access to everything</li>
    <li><span class="manual-role">Staff</span> — can view / add / edit</li>
    <li><span class="manual-role">Caretaker</span> — can view / add / edit (cannot delete)</li>
    <li><span class="manual-role">Employer</span> — can only see their own data</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Filter bar</strong> at the top — filter by status, has/doesn't have employees, search by name/tax ID</li>
    <li><strong>Employer cards</strong> — each card shows 1 company: name, number of employees, status</li>
    <li><strong>"+ Add New Employer"</strong> button in the top-right corner — for creating a new employer</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>Common tasks</h4>

<h5>1. Add a new employer</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Click <strong>"+ Add New Employer"</strong> in the top-right corner</li>
        <li>Fill in the basic info — company name (Thai/English), 13-digit tax ID, address</li>
        <li>Select a <strong>"Job Owner"</strong> if applicable (the staff member who manages this client)</li>
        <li>Upload documents (VAT registration copy, commercial registration, etc.) if available</li>
        <li>Click <strong>"Save"</strong> — the system creates the employer immediately</li>
    </ol>
</div>

<h5>2. Search for an employer</h5>
<div class="manual-step">
    Type the name or tax ID in the search box at the top — the list filters automatically
</div>

<h5>3. Edit employer information</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Click the card of the employer you want to edit</li>
        <li>Click the <i class="bi bi-pencil"></i> <strong>"Edit"</strong> icon</li>
        <li>Change the information you need, then click <strong>"Save"</strong></li>
    </ol>
</div>

<h5>4. View this employer's employees</h5>
<div class="manual-step">
    Click the employer card → you'll see the full list of employees working for this employer, along with each one's status
</div>

<h5>5. Delete an employer</h5>
<div class="manual-step">
    Click the <i class="bi bi-trash text-danger"></i> icon on the card — the system will ask for confirmation first
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i> <strong>Warning:</strong>
        If the employer still has employees, the system will <strong>not allow deletion</strong> — you must transfer or terminate all employees first
    </div>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Things to know / Tips</h4>

<div class="manual-tip">
    <strong>Thai vs English:</strong> Fill in the name in both languages —
    the English name is used when printing official documents (MOU, employment contracts)
</div>

<div class="manual-tip">
    <strong>Tax ID:</strong> must be exactly 13 digits — the system will warn you if it's entered incorrectly
</div>

<div class="manual-tip">
    <strong>Multiple addresses:</strong> one employer can have several addresses — a registered address and a workplace address
</div>

<div class="manual-warn">
    <strong>Careful:</strong> before deleting an employer, make sure there is no ongoing <strong>Production</strong> or <strong>Workflow</strong> job —
    deleting will cause that job's status to become invalid
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>

<dl>
    <dt>Q: Why can't I add an employer?</dt>
    <dd>A: Check that you filled in every field marked with an asterisk (*), especially the name and tax ID</dd>

    <dt>Q: An employee disappeared from the employer's card?</dt>
    <dd>A: Check the status filter — they may be under "Terminated" or "Contract Ended", try selecting "All"</dd>

    <dt>Q: I deleted the wrong employer, can I get it back?</dt>
    <dd>A: Yes — go to the <strong>"Central Trash"</strong> menu and click restore (Admin/Super Admin only)</dd>
</dl>
