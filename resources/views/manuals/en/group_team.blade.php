{{-- User Manual: Group & Team (English) --}}

<h4><i class="bi bi-people-fill me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Group &amp; Team"</strong> menu is used to <strong>organize employees</strong> into smaller teams
    so they can be managed as a unit, e.g. <em>"Factory A Morning Shift Team"</em>, <em>"Housekeeping Team"</em>, <em>"Construction Team"</em>.
    Used for <strong>creating Production / Workflow jobs in bulk</strong>, <strong>batch billing</strong>, and <strong>keeping data organized</strong>
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> <span class="manual-role">Staff</span> — full access</li>
    <li><span class="manual-role">Caretaker</span> — can only manage groups for the employers they oversee</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<p>When you enter the menu, you'll see <strong>2 options</strong>:</p>
<ol>
    <li><strong>Affiliated with Employer</strong> — a group tied to a specific employer; employees in the group must belong to that employer</li>
    <li><strong>Independent / No Employer</strong> — a group not tied to any employer; employees from any employer can be added</li>
</ol>
<p>Both types have a <strong>Manage</strong> page showing an <strong>accordion of each group</strong> + the <strong>sub-teams</strong> within it</p>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. Create a new group</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Go to "Group &amp; Team" → select the group type (Affiliated / Independent)</li>
        <li>If Affiliated → select an employer</li>
        <li>Click "<strong>+ Create New Group</strong>"</li>
        <li>Name the group (e.g. "Factory A Morning Shift Team") → confirm</li>
    </ol>
</div>

<h5>2. Add employees to a group</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the group's accordion → click "+ Add Member"</li>
        <li>Type a name / passport number to search → select the employee from the list → confirm</li>
        <li>Affiliated: only shows employees of that employer</li>
        <li>Independent: shows every employee in the system</li>
    </ol>
</div>

<h5>3. Split a group into sub-teams</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the group → click "<strong>+ Create Sub-Team</strong>"</li>
        <li>Name the team (e.g. "Team A1", "Team A2")</li>
        <li>Drag employees into the team (Drag &amp; Drop) or click "Add" on the team</li>
    </ol>
</div>

<h5>4. Use a group when creating a job</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open Pre-Prod / Workflow / Production</li>
        <li>When clicking "+ Add Job" → specify the Group Name you created</li>
        <li>The system pulls in the group's employees together — manage them as a single unit</li>
    </ol>
</div>

<h5>5. Move / delete / rename a group</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Open the group's accordion → the <i class="bi bi-pencil-square"></i> icon renames it</li>
        <li>The <i class="bi bi-trash"></i> icon deletes the group (employees are removed from the group, but not deleted from the system)</li>
        <li>You can drag-and-drop employees between teams or out of a group</li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Pick the right group type:</strong>
    <ul class="mb-0">
        <li><strong>Affiliated</strong> — best for employees of the same employer, e.g. "All employees of ABC Factory"</li>
        <li><strong>Independent</strong> — best for employees across employers, e.g. "Employees who need to interview on the same day" pulled from several factories</li>
    </ul>
</div>

<div class="manual-tip">
    <strong>Group Name in jobs:</strong> when creating a Production Order or Workflow item, specify the Group Name that matches the group here — the system links them automatically
</div>

<div class="manual-warn">
    <strong>Deleting a group:</strong> deleting a group only removes employees from that group — it does not delete the employees from the system.
    But <strong>deleting a sub-team</strong> just moves its employees back to the parent group (they're not lost)
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: How many groups can one employee be in?</dt>
    <dd>A: <strong>Multiple groups</strong> at the same time — e.g. both "Factory A Employees" (Affiliated) and "Interview Team 25/3/26" (Independent)</dd>

    <dt>Q: An employee transferred employer — what happens to their original Affiliated group?</dt>
    <dd>A: The employee is <strong>automatically removed</strong> from the old Affiliated group, since Affiliated groups are tied to an employer — Independent groups are unaffected</dd>

    <dt>Q: Can two groups share the same name?</dt>
    <dd>A: Not within the same employer — the system will warn "duplicate group name" and ask you to change it</dd>

    <dt>Q: I can't drag an employee between teams?</dt>
    <dd>A: They must be in the same group — for teams in different groups, use "Add Member" → select instead</dd>
</dl>
