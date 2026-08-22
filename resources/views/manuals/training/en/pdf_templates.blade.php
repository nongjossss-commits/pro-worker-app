{{-- Training Edition: PDF Templates (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-file-earmark-pdf-fill"></i> {{ __('PDF Templates') }} — {{ __('Create document templates with auto-fill fields') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"PDF Templates"</strong> menu is used to create <strong>PDF templates</strong> for various documents
        (employment contracts, certificates, government forms) by <strong>dragging and dropping</strong> fields.
        The system fills in the data automatically when generating the real document
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Create a new template — 2 ways</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/01-create-mode',
        'alt' => 'Template creation page with 2 radio options',
        'caption' => 'Create Template — Upload a new PDF, or Clone an existing one',
        'callouts' => [
            '<strong>📤 Upload new PDF:</strong> start from a blank PDF from your computer',
            '<strong>📋 Copy from existing:</strong> clone an existing template + adjust it slightly',
            '<strong>Searchable dropdown:</strong> search for the template to clone',
            '<strong>Field count:</strong> shows how many fields will be copied',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>PDF Templates</strong> → "+ Create New Template"</li>
            <li>Select a mode: Upload a new PDF, or Clone an existing one</li>
            <li>Name it + select the type (Global / Employer)</li>
            <li>Click "Upload & Go to Builder"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Drag-and-drop fields in the Builder</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/02-builder-drag',
        'alt' => 'PDF Builder — drag fields onto PDF',
        'caption' => 'Template Builder — drag fields onto the PDF',
        'callouts' => [
            '<strong>Field panel (left):</strong> fields you can place (employer name / passport / signature)',
            '<strong>PDF preview (center):</strong> drag a field to the position you want',
            '<strong>Properties (right):</strong> adjust size / font / alignment',
            '<strong>Save:</strong> saves the field map → ready to use with employees',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Smart Quick Print — print blank + fill in data</h2>

    @include('manuals.training._screenshot', [
        'src' => 'pdf_templates/03-quick-print',
        'alt' => 'Quick Print modal showing field analysis',
        'caption' => 'Quick Print — analyzes fields before printing',
        'callouts' => [
            '<strong>Select a template:</strong> the system immediately analyzes its fields',
            '<strong>Field analysis:</strong> how many fields for employee/employer/Delegate/Importer/witness',
            '<strong>If there are employee fields:</strong> blocked + suggests selecting an employee first',
            '<strong>If there aren\'t:</strong> select a Target Employer/Delegate/Importer and print right away',
        ],
    ])
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: If I clone a template, does deleting the original affect it?</dt>
        <dd>A: No — the system copies both the file + the field map entirely, so they're independent of each other</dd>

        <dt>Q: The uploaded PDF is a scan (an image)?</dt>
        <dd>A: It can still be used as a background — drag fields on top of the blank areas</dd>

        <dt>Q: What about the Thai font?</dt>
        <dd>A: The system uses THSarabunNew + CP874 encoding — full Thai language support</dd>
    </dl>
</section>
