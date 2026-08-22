{{-- User Manual: Financial Profiles (English) --}}

<h4><i class="bi bi-person-vcard me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Financial Profiles"</strong> menu is used to store information about
    <strong>Billers</strong> and <strong>Customers</strong> that appear on financial documents
    such as quotations, tax invoices, and receipts
</p>
<p>
    One office may have several biller profiles (e.g. "Bangkok Office", "Chiang Mai Office").
    Each profile has its own logo, signature, stamp, and <strong>bank accounts</strong>
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — can access</li>
    <li><span class="manual-role">Staff</span> — can access (depends on the <code>manage-finance</code> permission)</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>What the page looks like</h4>
<ol>
    <li><strong>Profile list</strong> (left side) — shows all profiles, split into Biller / Customer types</li>
    <li><strong>Edit form</strong> (right side) — edit the selected profile's information</li>
    <li><strong>"Bank Accounts" panel</strong> — appears after saving a profile — add/edit/delete that profile's accounts</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>How to use it</h4>

<h5>1. Create a Biller profile</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Click "+ Add New Profile"</li>
        <li>Select the "Biller" type</li>
        <li>Fill in the company name, tax ID, address, phone number, email</li>
        <li>Upload the <strong>logo, signature, and stamp</strong></li>
        <li>Position the <strong>signature/stamp placement</strong> by dragging on the PDF preview</li>
        <li>Click "Save"</li>
    </ol>
</div>

<h5>2. Add a bank account to a profile</h5>
<div class="manual-step">
    After saving the profile, the "Bank Accounts" panel appears:
    <ol class="mb-0 mt-2">
        <li>Click "+ Add Bank"</li>
        <li>Choose the type: <strong>Thai Bank / PromptPay / Custom</strong></li>
        <li>If "Thai Bank" is selected — pick a bank from the list (17 banks with color logos)</li>
        <li>Fill in the account name + account number + branch</li>
        <li>Click "Save"</li>
    </ol>
</div>

<h5>3. Edit a profile</h5>
<div class="manual-step">
    Click a profile in the left-hand list → edit in the right-hand form → click "Save"
</div>

<h5>4. Delete a profile</h5>
<div class="manual-step">
    Click the trash icon on the profile — the system will ask for confirmation
    <div class="manual-warn mt-2 mb-0">
        <i class="bi bi-exclamation-triangle-fill"></i>
        If the profile is used on an already-issued tax invoice — <strong>do not delete it</strong>, as this would break that old PDF's ability to find its profile
    </div>
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>Biller vs Customer profile:</strong> Biller = us (the issuer), Customer = frequent customers
    (so their details auto-fill on tax invoices without retyping)
</div>

<div class="manual-tip">
    <strong>Multiple Biller profiles:</strong> useful for offices with multiple branches/legal entities
</div>

<div class="manual-tip">
    <strong>Bank logos:</strong> the system automatically applies the brand color + abbreviation on the PDF
    (e.g. KBANK = green K, SCB = purple S)
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: I can't add a bank account?</dt>
    <dd>A: You must <strong>save the profile first</strong> — the Bank Accounts panel only appears after saving</dd>

    <dt>Q: The signature isn't showing on the PDF?</dt>
    <dd>A: Check that you uploaded the signature + positioned it on the preview — if it wasn't placed, the system doesn't know where to put it</dd>

    <dt>Q: The dropdown stays open after I select a bank?</dt>
    <dd>A: After you select one, the system collapses it into a chip immediately — click "Change" to pick a different one</dd>
</dl>
