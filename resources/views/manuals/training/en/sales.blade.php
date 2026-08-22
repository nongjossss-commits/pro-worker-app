{{-- Training Edition: Sales (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-cart-fill"></i> {{ __('Sales') }} — {{ __('The pipeline from Lead to closed sale') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Sales"</strong> menu manages the <strong>sales process</strong> from a new customer (Lead)
        → closing the sale → handing off into Production.
        Uses a <strong>Kanban board</strong> so you can drag-and-drop to change status
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open the Sales menu — Kanban Board</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/01-kanban-board',
        'alt' => 'Kanban board showing columns for sales stages',
        'caption' => 'Sales Kanban — each column is a stage (New / Contacted / Quoted / Closed)',
        'callouts' => [
            '<strong>Columns:</strong> the various stages a lead moves through',
            '<strong>Cards:</strong> each individual customer, with a brief summary',
            '<strong>Drag & Drop:</strong> drag a card to another column = change stage',
            '<strong>+ New Lead:</strong> add a new customer',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Sales</strong></li>
            <li>View the Kanban board — each column is a customer stage</li>
            <li>Filter by Owner or Search at the top</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Create a new Lead</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/02-new-lead',
        'alt' => 'New Lead creation form',
        'caption' => 'New Lead Form — customer info and contact channel',
        'callouts' => [
            '<strong>Customer info:</strong> name, company, contact',
            '<strong>Source:</strong> which channel they came from (referral / FB / website)',
            '<strong>Owner:</strong> which sales rep is responsible',
            '<strong>Initial stage:</strong> normally starts at "New" or "Contacted"',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click <strong>"+ New Lead"</strong></li>
            <li>Fill in the customer's info + sales owner</li>
            <li>Click Save → the card appears in the Kanban board</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Add employees + create a quotation</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/03-quotation-modal',
        'alt' => 'Quotation creation modal + manage employees',
        'caption' => 'Quotation Modal — add employees + set pricing + Generate PDF',
        'callouts' => [
            '<strong>Manage Employees:</strong> add temporary employees (don\'t need to be real employees yet)',
            '<strong>Pricing Tiers:</strong> set per-head pricing, same as in Production',
            '<strong>Generate PDF:</strong> issue a PDF quotation to send to the customer',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Open the Lead's card → click <strong>"Manage Employees"</strong></li>
            <li>Add temporary employees (the system creates them as Temp first)</li>
            <li>Open the Financial tab → set pricing</li>
            <li>Click <strong>"Quotation"</strong> → issues a PDF quotation</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">Close the sale → send into Production (Transition)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/04-transition-to-production',
        'alt' => 'Lead → Production transition modal',
        'caption' => 'Transition Modal — convert a Lead into a Production Order',
        'callouts' => [
            '<strong>Select the Work Type:</strong> the job that Production will handle',
            '<strong>Confirm transition:</strong> the system creates the Employer + Employees + Production Order automatically',
            '<strong>Temp employees → real employees:</strong> temporary employees become real ones at this point',
            '<strong>Auto-archive lead:</strong> the original Lead is archived since the sale has closed',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Customer agrees to buy → drag the card to <strong>"Closed Won"</strong></li>
            <li>Click <strong>"Transition to Production"</strong></li>
            <li>Select the Work Type → confirm</li>
            <li>The system creates the Employer/Employees/Production Order all at once → appears in the Pre-Prod menu immediately</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Tip:</strong> temporary employees entered under the Lead → are automatically converted into real employees during the transition
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Sales visibility and permissions</h2>

    @include('manuals.training._screenshot', [
        'src' => 'sales/05-visibility-permissions',
        'alt' => 'Sales menu visibility settings in Super Admin',
        'caption' => 'Sales Menu Visibility — Super Admin can turn it on/off',
        'callouts' => [
            '<strong>Default visibility:</strong> Sales can be turned on/off in Super Admin Settings',
            '<strong>Per-role:</strong> Caretaker/Employer don\'t see the Sales menu',
            '<strong>Owner-scoped:</strong> Staff only sees leads they own (if configured that way)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Super Admin Settings → Menu Visibility</li>
            <li>Turn the Sales menu on/off</li>
            <li>Set which roles can access it</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: Can I delete a "Closed Lost" lead?</dt>
        <dd>A: Yes — but archiving is recommended instead, to track sales history and future analytics</dd>

        <dt>Q: If a lead comes back later — create a new one, or revive the old one?</dt>
        <dd>A: Open the original Lead and change its stage back to New or Contacted</dd>

        <dt>Q: How are Temp employees on a Lead different from real Employees?</dt>
        <dd>A: Temp = no actual record in the employees table yet (stored as JSON), Real = created as an actual system record — this happens automatically during the transition</dd>

        <dt>Q: Can an issued quotation be edited?</dt>
        <dd>A: Yes — but the next time you generate it, it will show a new invoice number/version</dd>
    </dl>
</section>
