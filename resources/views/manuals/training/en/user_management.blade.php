{{-- Training Edition: User Management (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('User Management') }} — {{ __('Create / edit users + assign roles + permissions') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"User Management"</strong> menu is used to create/edit <strong>user accounts</strong>
        for office staff + customers (the employer role) + assign <strong>roles</strong> + configure <strong>permissions</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin (limited)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Add a new User</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/01-create-user',
        'alt' => 'New user creation form + role selector',
        'caption' => 'New User Form — fill in the data + select a role',
        'callouts' => [
            '<strong>Name + email:</strong> used to log in',
            '<strong>Password:</strong> a default one, or set your own',
            '<strong>Role:</strong> super-admin / admin / staff / caretaker / employer',
            '<strong>Linked Employer:</strong> only for role = employer (which employer they\'re tied to)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>User Management</strong></li>
            <li>Click "+ Add User"</li>
            <li>Fill in the name + email + password</li>
            <li>Select the Role + Linked Employer (if it's an employer)</li>
            <li>Click Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">The 5 main Roles + Permissions</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/02-roles-permissions',
        'alt' => 'Role list + permission matrix',
        'caption' => 'Roles & Permissions — 5 roles + 27+ permissions',
        'callouts' => [
            '<strong>super-admin:</strong> full access (root)',
            '<strong>admin:</strong> manages all data, but can\'t edit permissions/roles',
            '<strong>staff:</strong> day-to-day work — entering/editing data',
            '<strong>caretaker:</strong> only sees employers assigned to them',
            '<strong>employer:</strong> a customer — only sees their own data',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Assign a Caretaker to an Employer</h2>

    @include('manuals.training._screenshot', [
        'src' => 'user_management/03-caretaker-assign',
        'alt' => 'Employer Edit page → Caretakers tab',
        'caption' => 'Caretaker Assignment — select caretaker users for each employer',
        'callouts' => [
            '<strong>Caretakers tab:</strong> on the Employer Edit page',
            '<strong>Multi-select:</strong> one employer can have several caretakers',
            '<strong>Caretaker only sees what\'s assigned to them:</strong> prevents data leaks',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Employers menu → select an employer → the Caretakers tab</li>
            <li>Select caretaker users (multi-select)</li>
            <li>Click Save</li>
            <li>Those Caretakers will now see this employer in their sidebar</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: Can I add a new role?</dt>
        <dd>A: Possible via the Spatie Permission package — Super Admin only</dd>

        <dt>Q: Can I reset a user's password?</dt>
        <dd>A: Yes — Edit user → enter a new password → Save</dd>

        <dt>Q: If I delete a user, what happens to the data they created?</dt>
        <dd>A: The data stays — only the user account is deleted (still visible in the audit log)</dd>
    </dl>
</section>
