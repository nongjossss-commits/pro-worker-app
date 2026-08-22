{{-- Training Edition: Group & Team (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('Group & Team') }} — {{ __('Organize employees into smaller teams') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Group & Team"</strong> menu is used to <strong>group employees</strong> into smaller teams,
        e.g. "Factory A Morning Shift", "Housekeeping Team" — so they can be managed as a unit.
        Split into 2 types: <strong>Affiliated</strong> (tied to an employer) + <strong>Independent</strong> (not tied)
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (only groups they manage)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Select the group type — Affiliated vs Independent</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/01-type-selection',
        'alt' => 'Group type selection page with 2 cards',
        'caption' => 'Group Type Selection — choose Affiliated or Independent',
        'callouts' => [
            '<strong>Affiliated:</strong> tied to 1 employer — employees in the group must belong to that employer',
            '<strong>Independent:</strong> not tied to an employer — employees from any employer can be added',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Group & Team</strong></li>
            <li>Select Affiliated or Independent</li>
            <li>If Affiliated → select an employer first</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Create a group + add members + sub-teams</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/02-manage',
        'alt' => 'Manage Groups page with sub-team accordion',
        'caption' => 'Manage Groups — an accordion of each group + its sub-teams',
        'callouts' => [
            '<strong>+ Create New Group:</strong> name it → confirm',
            '<strong>+ Add Member:</strong> search for an employee → tick → confirm',
            '<strong>+ Create Sub-Team:</strong> split the group into sub-teams',
            '<strong>Drag & Drop:</strong> drag employees between teams',
            '<strong>Highlight pulse:</strong> the most recently added team flashes orange',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click "+ Create New Group" → name it</li>
            <li>Click "+ Add Member" → search → tick → confirm</li>
            <li>Click "+ Create Sub-Team" if you need to split the group further</li>
            <li>You can drag employees between teams</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Use a group when creating a job</h2>

    @include('manuals.training._screenshot', [
        'src' => 'group_team/03-use-in-workflow',
        'alt' => 'Using a Group Name when creating a Production / Workflow item',
        'caption' => 'Group Name — used when specifying a Production/Workflow item',
        'callouts' => [
            '<strong>"Group Name" field:</strong> present on every job creation form',
            '<strong>Auto-link:</strong> the system pulls in the group\'s employees together',
            '<strong>Manage as one unit:</strong> batch billing + batch PDF generation',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Open the job creation form in Production / Workflow</li>
            <li>Specify a Group Name matching the group you created</li>
            <li>The system links them automatically</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: How many groups can one employee be in?</dt>
        <dd>A: Multiple at the same time — e.g. Affiliated with Employer A + Independent "Interview 25/3" simultaneously</dd>

        <dt>Q: An employee transferred employer — what happens to their original Affiliated group?</dt>
        <dd>A: They're <strong>automatically removed</strong> from the old Affiliated group, since it's tied to an employer — Independent groups are unaffected</dd>

        <dt>Q: Can two groups share the same name?</dt>
        <dd>A: Not within the same employer — the system will warn you</dd>
    </dl>
</section>
