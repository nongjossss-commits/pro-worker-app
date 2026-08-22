{{-- User Manual: PDF Templates (English) --}}

<h4><i class="bi bi-file-earmark-pdf-fill me-2"></i>What is this menu?</h4>
<p>
    The <strong>"PDF Templates"</strong> menu is used to create <strong>PDF templates</strong> for various documents,
    such as employment contracts, work certificates, etc.
    By <strong>dragging and dropping</strong> data fields onto an uploaded PDF template,
    the system fills in the data automatically when generating the real document
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — full access</li>
    <li><span class="manual-role">Staff</span> — can view + use templates (the <code>view-pdf-templates</code> permission)</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Template list</strong> — shows every template created so far</li>
    <li><strong>"+ New Template"</strong> button</li>
    <li><strong>Editor page</strong> (when opening a template) — PDF preview + a list of draggable fields</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. Create a new template — 2 ways</h5>
<div class="manual-step">
    <strong>Method 1: Upload a new PDF</strong>
    <ol class="mb-2">
        <li>Click "+ New Template"</li>
        <li>Select <strong>"Upload new PDF"</strong></li>
        <li>Name the template (e.g. "MOU Employment Contract - Myanmar")</li>
        <li>Upload the source PDF (e.g. a blank contract with empty fields)</li>
        <li>Click "Upload &amp; Go to Builder" → enters the Editor page</li>
    </ol>
    <strong>Method 2: Copy from an existing template (Clone)</strong>
    <ol class="mb-0">
        <li>Click "+ New Template"</li>
        <li>Select <strong>"Copy from existing template"</strong></li>
        <li>Choose the source template from the list (searchable)</li>
        <li>Give it a new name (e.g. add "(Copy)" or "v2") + select the type/employer</li>
        <li>Click "Clone &amp; Go to Builder" → the system copies the PDF file + all field positions for you → adjust only what's needed, then save</li>
    </ol>
</div>

<h5>2. Drag and drop fields</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>On the Editor page — the field list is on the left (employer name, tax ID, etc.)</li>
        <li>Drag the field you want onto the PDF preview at the desired position</li>
        <li>You can resize/change the font of each field</li>
        <li>Click "Save"</li>
    </ol>
</div>

<h5>3. Use a template to generate a document</h5>
<div class="manual-step">
    In the employee/employer menu — click "Generate Document" → select a template → the system fills in the data automatically
</div>

<h5>4. Print a document via Quick Print (for templates that don't need employee data)</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Click the <strong>"Print Document"</strong> button (green) at the top of the PDF Templates page</li>
        <li>Select a template — the system will <strong>analyze the fields</strong> to show you whose data is needed:
            <ul>
                <li><span class="badge bg-warning text-dark">Employee</span> = you must select an employee first — the system will warn you and won't let you print blank</li>
                <li><span class="badge bg-primary">Employer</span> / <span class="badge bg-info">Delegate</span> / <span class="badge bg-success">Importer</span> = select a target from the dropdown, then print</li>
            </ul>
        </li>
        <li>If the template has an employee field → click <strong>"Go select an employee"</strong> → pick the person you need on the employee management page → click "Generate Automated PDF"</li>
        <li>If there's no employee field → select the Target Employer/Delegate/Importer (whichever the template uses) → click <strong>"Download PDF"</strong> or <strong>"Print / Preview"</strong></li>
    </ol>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Thai font:</strong> the system uses THSarabunNew + CP874 encoding — full Thai language support
</div>

<div class="manual-tip">
    <strong>Signature + stamp:</strong> you can insert a signature/stamp from a Financial Profile, or add one procedurally (drawn directly on the PDF)
</div>

<div class="manual-tip">
    <strong>Clone saves time when:</strong> you need a template using the same form but with a few fields changed — e.g. reusing the same contract but changing the company name or a small condition
</div>

<div class="manual-warn">
    <strong>Quick Print and employee fields:</strong> if a template has employee-data fields (employee name, passport, etc.) <strong>it cannot be printed blank</strong> — you must select an employee first, since printing it blank would leave those fields empty and unusable
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: The uploaded PDF has scanned text (an image)?</dt>
    <dd>A: The system can still use it as a background — just drag-and-drop fields on top of the blank areas yourself</dd>

    <dt>Q: I placed a field in the wrong position?</dt>
    <dd>A: Open the template → drag the field to the correct position → save — the system applies the new position immediately</dd>

    <dt>Q: If I clone a template and then delete the original, does it affect the cloned one?</dt>
    <dd>A: No effect — the system copies the PDF file to a completely new path, so both templates are entirely independent of each other</dd>

    <dt>Q: I clicked "Print Document" but the "Download" button is disabled?</dt>
    <dd>A: That's because the selected template has employee-data fields — Quick Print doesn't support that; instead, select the employee from "Manage Employees" and use "Generate Automated PDF"</dd>
</dl>
