{{-- Training Edition: Employees — slide-friendly with annotated screenshots (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-people-fill"></i> {{ __('Employees') }} — {{ __('Manage all migrant worker data') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Employees"</strong> menu is used to <strong>add / edit / view</strong> every employee's data —
        personal details, passport, visa, work permit, photo, attached documents.
        It's the starting point for every type of job (Production, Workflow, Registration Resolution, Renewal Resolution)
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (only their own employees)</span>
    </div>
</div>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open the employee list page</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/01-list-view',
        'alt' => 'Employee list page (card view) with filter bar',
        'caption' => 'Employee List Page — switch between Card view and Table view',
        'callouts' => [
            '<strong>Filter bar:</strong> search, filter by nationality, MOU group, passport',
            '<strong>+ Add Employee:</strong> create a new employee',
            '<strong>Card/Table view:</strong> switch as needed',
            '<strong>Bulk Action:</strong> tick multiple people to Export, transfer employer, generate PDF',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click <strong>Sidebar → Employees</strong></li>
            <li>Select the view type (<strong>Card</strong> or <strong>Table</strong>)</li>
            <li>Use the filters at the top to find the employee you need</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Tip:</strong> the "Employment History" menu shows everyone including terminated employees — unlike this menu, which only shows active ones
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Add a new employee</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/02-add-employee',
        'alt' => 'New employee creation form',
        'caption' => 'New Employee Form — multiple tabs for each data category',
        'callouts' => [
            '<strong>Select Employer:</strong> an employee must always be linked to an employer',
            '<strong>Required fields:</strong> name, nationality, passport',
            '<strong>Tabs:</strong> General Info → Passport/Visa → Documents → Photo',
            '<strong>Document Scanner:</strong> take a photo directly from the camera into the system',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click <strong>"+ Add Employee"</strong> (top-right corner)</li>
            <li>Select the <strong>employer</strong> (you can type to search)</li>
            <li>Fill in <strong>name + nationality + passport</strong> (required)</li>
            <li>Fill in additional data on each tab (optional — can be edited later)</li>
            <li>Click <strong>"Save"</strong></li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>Careful:</strong> Employee Cap — the system limits the total number of employees based on the subscription tier
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Edit employee data</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/03-edit-employee',
        'alt' => 'Employee edit page with data and document tabs',
        'caption' => 'Employee Edit Page — Personal, Documents, History tabs',
        'callouts' => [
            '<strong>Personal:</strong> name, address, nationality, date of birth',
            '<strong>Documents:</strong> passport, visa, work permit + upload PDF/image',
            '<strong>Other Documents:</strong> 10 slots for extra documents (default labels set in Super Admin)',
            '<strong>History tab:</strong> view the change history + activity log',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click the <strong>employee card</strong> or the pencil ✏️ button</li>
            <li>Each tab has its own fields to fill in</li>
            <li>Upload files via the <strong>Upload</strong> button or the <strong>Document Scanner</strong></li>
            <li>Click <strong>"Save"</strong> — the system logs the change in the Activity Log</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Tip:</strong> after editing an employee's data, their job card moves to the top in Workflow/Production
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">The Employee Preview button</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/04-preview-popup',
        'alt' => 'Preview popup showing employee data read-only',
        'caption' => 'Preview Popup — quickly view employee data without opening the edit page',
        'callouts' => [
            '<strong>Preview 🔍 button:</strong> on every employee card, on every page',
            '<strong>Read-only:</strong> viewable but not editable',
            '<strong>Covers:</strong> Personal, passport, visa, documents, photo',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Look for the <strong>magnifying glass 🔍</strong> icon on the employee card</li>
            <li>Click it → a modal pops up showing all the data</li>
            <li>Close the modal or click outside it to return</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Caretaker:</strong> Preview only works for employees of employers assigned to them
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Bulk Actions — handle multiple people at once</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employees/05-bulk-actions',
        'alt' => 'Floating bulk action bar when multiple people are ticked',
        'caption' => 'Bulk Action Bar — pops up when you tick multiple employees',
        'callouts' => [
            '<strong>Tick checkbox:</strong> every card has a checkbox in the top-left corner',
            '<strong>Action menu:</strong> Export, transfer employer, generate PDF, send to Production',
            '<strong>Counter:</strong> shows how many are selected',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Tick the <strong>checkbox</strong> of the employees you want (multiple selection supported)</li>
            <li>The Bulk Action Bar pops up at the bottom</li>
            <li>Select an action from the dropdown:
                <ul>
                    <li><strong>Export CSV / Advanced Export</strong></li>
                    <li><strong>Transfer Employer</strong> (Bulk Transfer)</li>
                    <li><strong>Automated PDF</strong> (generate PDFs from a template)</li>
                    <li><strong>Send to Production</strong></li>
                </ul>
            </li>
        </ol>
    </div>
</section>

{{-- ═════════════════════════════════════════════════════════════════════ --}}

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: I can't add a new employee — I get an error?</dt>
        <dd>A: Check the Employee Cap — the system limits the count based on the subscription; contact Super Admin if you need it raised</dd>

        <dt>Q: An employee is missing from the list?</dt>
        <dd>A: Check the "Employment History" menu — they may have been notified out/had their contract end, or been deleted into "Central Trash"</dd>

        <dt>Q: Caretaker sees fewer employees than expected?</dt>
        <dd>A: Caretaker only sees employees of employers assigned to them</dd>

        <dt>Q: The Preview button doesn't work — Error 500?</dt>
        <dd>A: There used to be a bug here — it's now fixed, and Caretaker can preview normally (only for the employees they manage)</dd>
    </dl>
</section>
