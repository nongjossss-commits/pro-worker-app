{{-- Training Edition: Employers (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-building-fill"></i> {{ __('Employers') }} — {{ __('The master record of client companies hiring migrant workers') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Employers"</strong> menu stores data on <strong>employers</strong> (client companies) that hire migrant workers.
        This data is used across employees, document generation, tax invoices, and contracts — it's the core foundation of the system
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
        <span class="role-pill role-readonly">Caretaker (only their own)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open the menu + view the employer list</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/01-list',
        'alt' => 'Employer list with filter bar + sequence numbers',
        'caption' => 'Employers List — shown as Card + Table view',
        'callouts' => [
            '<strong>+ Add Employer:</strong> create a new employer',
            '<strong>Filter:</strong> search, filter by province, filter by JobOwner',
            '<strong>Sequence number:</strong> the number in the top-right corner of the card (CSS counter)',
            '<strong>Card / Table toggle:</strong> switch views',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Employers</strong></li>
            <li>Filter or search for the employer you need</li>
            <li>Click a card to go to its edit page</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Add a new employer</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/02-create-form',
        'alt' => 'New employer creation form',
        'caption' => 'New Employer Form — fill in basic info + tax ID',
        'callouts' => [
            '<strong>Company name (TH/EN):</strong> both languages',
            '<strong>Tax ID:</strong> 13 digits',
            '<strong>Address:</strong> multiple addresses supported (registered / document delivery)',
            '<strong>JobOwner:</strong> the actual client manager (e.g. Kung)',
            '<strong>Caretakers:</strong> assign the Caretaker users who manage this employer',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click <strong>"+ Add Employer"</strong></li>
            <li>Fill in the company details + tax ID + address</li>
            <li>Select the JobOwner (the actual manager)</li>
            <li>Click Save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Edit employer data + add a delegate</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/03-edit-detail',
        'alt' => 'Employer edit page — tabs for details, addresses, signatures, delegates',
        'caption' => 'Edit Employer — multiple tabs: Details / Addresses / Signature / Delegates',
        'callouts' => [
            '<strong>General Info tab:</strong> name + tax ID + contact info',
            '<strong>Addresses tab:</strong> add multiple addresses',
            '<strong>Signature/Stamp tab:</strong> upload a signature + company stamp',
            '<strong>Delegates tab:</strong> add Delegates who sign on the employer\'s behalf',
            '<strong>Other Documents tab:</strong> 3 slots (default labels set by Super Admin)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click the employer card → the pencil ✏️ button</li>
            <li>Select the tab you want to edit</li>
            <li>Click Save to save</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">Preview + Quick Actions</h2>

    @include('manuals.training._screenshot', [
        'src' => 'employers/04-preview-modal',
        'alt' => 'Employer preview popup',
        'caption' => 'Preview Popup — quickly view all details without opening the edit page',
        'callouts' => [
            '<strong>Preview 🔍 button:</strong> view read-only data',
            '<strong>Stats:</strong> number of active + terminated employees, broken down by nationality',
            '<strong>Active employee list:</strong> the first 10, paginated',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Employer card → the magnifying glass 🔍 button</li>
            <li>View the data + employee counts</li>
            <li>Click "View All" to go to this employer's employee list</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: Can I delete an employer that still has employees?</dt>
        <dd>A: You can delete it — but the employees become orphaned; use the archive instead, or transfer the employees to another employer first</dd>

        <dt>Q: What's the difference between JobOwner and Caretakers?</dt>
        <dd>A: JobOwner = the actual client manager (e.g. Kung manages several companies), Caretaker = a system role assigned to give a user visibility into the data</dd>

        <dt>Q: Which employers does a Caretaker see?</dt>
        <dd>A: Only employers assigned to them under that employer's Caretakers tab</dd>
    </dl>
</section>
