{{-- Training Edition: Workflow — slide-friendly with annotated screenshots (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-diagram-3-fill"></i> {{ __('Workflow') }} — {{ __('The hub for jobs currently in progress') }}
    </h3>
    <p class="training-intro-desc">
        This menu is the <strong>hub for every job</strong> moving through the various process steps —
        e.g. filing with the Department of Employment, passport processing, visa applications, work permit issuance.
        Users can <strong>tick off steps</strong> for each employee, and the system tracks progress for them
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (view only)</span>
    </div>
</div>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Enter Workflow + select a Tab</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/01-main-view',
        'alt' => 'Workflow main page showing tabs for the various Work Types',
        'caption' => 'Workflow main page — the top bar has a tab for each Work Type',
        'callouts' => [
            '<strong>Tab Bar:</strong> select a job type (Notify In / Visa Renewal / Imported MOU / Notify Out)',
            '<strong>+ Add Employee button:</strong> add an employee to a job',
            '<strong>Filter:</strong> filter by operator, status, search by name',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click <strong>Sidebar → Workflow</strong></li>
            <li>Select the <strong>Tab</strong> for the Work Type you're working on</li>
            <li>Each employer's card shows with its list of employees</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Tip:</strong> a card with <strong>recent activity</strong> moves to the top every time you refresh
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Tick off an employee's step</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/02-tick-step',
        'alt' => 'Employee card with a checkbox for each step',
        'caption' => 'Employee card with a checkbox for each step',
        'callouts' => [
            '<strong>Checkbox:</strong> tick it to record that step as done',
            '<strong>Step name:</strong> the name of the step (e.g. "Submit application", "Pay the fee")',
            '<strong>Progress bar:</strong> overall progress percentage',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click the <strong>checkbox</strong> for a completed step</li>
            <li>The system logs the <strong>timestamp + who did it</strong> automatically</li>
            <li>The progress bar updates immediately</li>
            <li>Once every step is done → click <strong>Finish</strong> to close the job</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>Careful:</strong> if you tick something by mistake you can click it again to undo, but the Activity Log will record the change either way
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Add an employee to a Workflow job</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/03-add-employee-modal',
        'alt' => 'Modal for adding an employee to Workflow',
        'caption' => 'Add Employee Modal — select the job type + employer + employees',
        'callouts' => [
            '<strong>Searchable employer dropdown:</strong> type a name/code to search',
            '<strong>Employee list:</strong> that employer\'s employees',
            '<strong>Bulk select:</strong> select multiple people at once',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click <strong>"+ Add Employee"</strong> at the top of the tab</li>
            <li>Select the <strong>Employer</strong> (you can type to search)</li>
            <li>Select the <strong>employees</strong> (multiple selection supported)</li>
            <li>Click <strong>"Add"</strong> — the employees appear on the employer's card immediately</li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">The "Notify Out" tab</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/04-notify-out',
        'alt' => 'Employee card in the Notify Out tab with date + reason fields',
        'caption' => 'Notify Out tab — a yellow bar for entering the notify-out date and reason',
        'callouts' => [
            '<strong>Notify-out date (required):</strong> a date picker, required before clicking Finish',
            '<strong>Reason:</strong> Resigned / Dismissed / Contract Ended / Employer Change / Other',
            '<strong>Colored badge:</strong> yellow = needs to be filled in, green = ready to finish',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Open the <strong>"Notify Out"</strong> tab</li>
            <li>Add an employee (search across the entire system — global search)</li>
            <li>Fill in the <strong>notify-out date</strong> + <strong>reason</strong> in the yellow bar</li>
            <li>Click <strong>Finish</strong> — the system auto-updates the employee's status to "resigned"</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Tip:</strong> if the employee is transferring employer (not actually resigning) → the notify_out entry is auto-cancelled
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Imported MOU — create a Demand Card</h2>

    @include('manuals.training._screenshot', [
        'src' => 'workflow/05-mou-import',
        'alt' => 'Imported MOU card with a subtype color badge',
        'caption' => 'MOU Import card — shows the subtype (Return/New/Pending) via color and badge',
        'callouts' => [
            '<strong>Border color:</strong> 🟢 Return | 🔵 New from Origin | 🟠 Pending',
            '<strong>Badge:</strong> click to change the type later',
            '<strong>Searchable employer:</strong> type to search instead of scrolling',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Open the <strong>"Imported MOU"</strong> tab → click <strong>"Create Job"</strong></li>
            <li>Select the employer (you can type to search) + specify the type:
                <ul>
                    <li>🟢 <strong>Return</strong> — the employee is already in Thailand</li>
                    <li>🔵 <strong>New from Origin</strong> — a new person from the origin country</li>
                    <li>🟠 <strong>Not sure yet</strong> — decide later</li>
                </ul>
            </li>
            <li>Fill in nationality + male/female count</li>
            <li>Click <strong>Create Demand Card</strong></li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: Why doesn't my card move to the top?</dt>
        <dd>A: The system only reorders cards on <strong>refresh</strong> or when you come back from another menu — the UI doesn't jump around mid-task (to avoid being distracting)</dd>

        <dt>Q: An employee disappeared from the Notify Out tab?</dt>
        <dd>A: Auto-cancelled when they were transferred to a new employer — notify_out means "leaving the old employer", which no longer applies</dd>

        <dt>Q: Caretaker sees some cards but not others?</dt>
        <dd>A: Caretaker only sees employers assigned to them</dd>
    </dl>
</section>
