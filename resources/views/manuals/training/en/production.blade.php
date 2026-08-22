{{-- Training Edition: Pre-Production (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-clipboard-data-fill"></i> {{ __('P Production (Pre-Production)') }} — {{ __('The document-prep hub before entering Workflow') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Pre-Production"</strong> menu is where <strong>documents</strong> and customer data are prepared before being sent to Workflow.
        Used for new customers whose sale has closed in Sales → prepare Pre-Prod → send into Workflow for further processing
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (view only)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Enter the Pre-Production page</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/01-main-view',
        'alt' => 'Pre-Production main page showing employer job cards',
        'caption' => 'Pre-Production main view — each card is one employer\'s job',
        'callouts' => [
            '<strong>Summary stat cards at the top:</strong> nearing deadline / in progress / awaiting review',
            '<strong>Filter:</strong> employer / job owner / job type (MOU/Visa)',
            '<strong>Job card:</strong> sales rep photo + employer name + employee count + status',
            '<strong>Most recent card floats to the top:</strong> whenever there\'s recent activity (a step ticked/data edited)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Pre-Production</strong></li>
            <li>Check the summary stat cards at the top</li>
            <li>Use the filters to narrow down by employer or owner</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Open a job + edit each employee individually</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/02-edit-job',
        'alt' => 'Edit Job page with multiple tabs: Employees, Documents, Finance',
        'caption' => 'Edit Job page — Tabs: Employees / Documents / Finance / Timeline',
        'callouts' => [
            '<strong>Tab Bar:</strong> switch between Employee / Document / Financial / Timeline',
            '<strong>Employee Card:</strong> each employee has an edit + view-documents button',
            '<strong>Document Scanner:</strong> capture photos straight from the camera into the system',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click an employer's job card → opens the Edit Job page</li>
            <li>Select the <strong>"Employees"</strong> tab</li>
            <li>Click the edit ✏️ button to edit each employee's data</li>
            <li>Upload documents via Upload or the Document Scanner</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Add a Custom Field</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/03-custom-fields',
        'alt' => 'Modal for adding a Custom Field for a special job',
        'caption' => 'Custom Fields — add fields specific to this job',
        'callouts' => [
            '<strong>"Fields" button:</strong> on the MOU card',
            '<strong>Add a new field:</strong> e.g. "Medical certificate number", "Appointment date"',
            '<strong>Specify the type:</strong> text / number / date / dropdown',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click the <strong>"Fields"</strong> button on the job card</li>
            <li>Click "+ Add New Field"</li>
            <li>Name the field + select the type → Save</li>
            <li>The new field appears on each employee's Custom Fields tab</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">The Financial tab</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/04-financial-tab',
        'alt' => 'Financial tab with pricing tier cards',
        'caption' => 'Financial Tab — create pricing tiers + installments + billing',
        'callouts' => [
            '<strong>+ Add Tab:</strong> create multiple financial tabs per job (e.g. "Service Fee", "Employer Change Installment")',
            '<strong>Pricing Tiers:</strong> set per-head prices in tiers + headcount + notes',
            '<strong>Note popup:</strong> click the note → a large popup with a 500-character counter',
            '<strong>Pencil / trash buttons:</strong> edit/delete a tier (delete asks for confirmation)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Open Edit Job → click the <strong>"Financial"</strong> tab or the "Finance" button</li>
            <li>Click <strong>"+ Add Tab"</strong> → name it (cannot be blank/duplicate)</li>
            <li>Select "Per-head" mode → add a Pricing Tier</li>
            <li>Click the <strong>note box</strong> → a popup opens for typing</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>This note also appears on invoices/receipts</strong> — use it to explain to the customer what the charge is for
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Send a job to Workflow</h2>

    @include('manuals.training._screenshot', [
        'src' => 'production/05-send-to-workflow',
        'alt' => 'Send to Workflow + Bulk Send buttons',
        'caption' => 'Send to Workflow — send one at a time or the whole batch at once',
        'callouts' => [
            '<strong>Send to Workflow:</strong> sends the job into the Workflow process',
            '<strong>Bulk Send:</strong> send the entire MOU batch in a single click',
            '<strong>Permission:</strong> only approve-production (Admin/Super Admin)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Make sure the documents + data are ready</li>
            <li>Click <strong>"Send to Workflow"</strong> (individually) or <strong>"Send Whole Batch"</strong> (Bulk)</li>
            <li>The job moves into the <strong>Workflow</strong> menu</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>Careful:</strong> once a job has been sent to Workflow, it can no longer be edited in Pre-Prod — you must edit it in Workflow instead
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: Why don't I see the "Send to Workflow" button?</dt>
        <dd>A: Check your role — you need the <code>approve-production</code> permission (Admin/Super Admin)</dd>

        <dt>Q: An employee resigned during Pre-Prod?</dt>
        <dd>A: Remove that employee from the Pre-Prod job, or Cancel the whole job if everyone resigned</dd>

        <dt>Q: Can one employee be in multiple Pre-Prod jobs?</dt>
        <dd>A: Yes, if they're different Work Types (e.g. MOU + Visa Renewal at the same time) — the same Work Type cannot be duplicated</dd>
    </dl>
</section>
