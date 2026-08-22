{{-- User Manual: User Management (English) --}}

<h4><i class="bi bi-person-fill-gear me-2"></i>What is this menu?</h4>
<p>
    The <strong>"User Management"</strong> menu is used to create/edit/delete
    system <strong>user accounts</strong> and assign a <strong>Role</strong> to each person,
    plus view the <strong>Permissions</strong> for each Role at the bottom of the page
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — can access</li>
    <li>(Super Admin sees all super-admin users; Admin sees everyone except super-admins)</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Search bar</strong> + filter by role</li>
    <li><strong>Role tabs</strong> — Super Admin, Admin, Caretaker, Staff, Employer</li>
    <li><strong>User table</strong> — name, email, status, actions</li>
    <li><strong>"Roles & Permissions" section</strong> at the bottom (only visible to those with the manage-roles permission) — view all roles + their assigned permissions</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. Create a new user</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Click "+ Add New User"</li>
        <li>Fill in the name, email, password</li>
        <li>Select a Role (e.g. Staff, Caretaker)</li>
        <li>Click "Save" — the user can now log in</li>
    </ol>
</div>

<h5>2. Change a user's Role</h5>
<div class="manual-step">
    Click the user's name → select a new Role → Save
</div>

<h5>3. Enable/disable an account (Status)</h5>
<div class="manual-step">
    Click the Status switch on the user's row — Active = can log in, Inactive = cannot log in
</div>

<h5>4. Reset a password</h5>
<div class="manual-step">
    Click the name → click "Change Password" → enter a new one → Save
</div>

<h5>5. View permissions for each Role</h5>
<div class="manual-step">
    Scroll down to the bottom of the page — the "Roles & Permissions" section shows every role + the permissions attached to it
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>The 5 Roles in Pro-Worker:</strong>
    <ul class="mb-0 mt-1">
        <li><span class="manual-role">Super Admin</span> — full access to everything</li>
        <li><span class="manual-role">Admin</span> — full access except Super Admin Settings</li>
        <li><span class="manual-role">Staff</span> — day-to-day work (employees, employers, finance)</li>
        <li><span class="manual-role">Caretaker</span> — manages employees (cannot delete)</li>
        <li><span class="manual-role">Employer</span> — a customer who logs in to view their own data</li>
    </ul>
</div>

<div class="manual-warn">
    <strong>Careful:</strong> don't give the <strong>Admin</strong> role to anyone who isn't a manager — Admin can delete and edit everything
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: I deleted the wrong user, can I get them back?</dt>
    <dd>A: Go to Central Trash → click restore (Super Admin only)</dd>

    <dt>Q: Can I change what permissions a Role has?</dt>
    <dd>A: Not through the UI — this requires the command line (Tinker) or editing the Seeder</dd>

    <dt>Q: Why can't my Staff user access certain menus?</dt>
    <dd>A: Check the "Roles & Permissions" section — see what permissions Staff has; if something's missing, ask a Super Admin to add it</dd>
</dl>
