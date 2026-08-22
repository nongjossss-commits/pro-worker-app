{{-- Training Edition: Financial Profiles (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-person-vcard-fill"></i> {{ __('Financial Profiles') }} — {{ __('The master template for billers + customers') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Financial Profiles"</strong> menu is where <strong>master data</strong> is stored
        for Billers (issuers = our office) + Customers (regular customers).
        Every time an invoice/receipt is issued, the system lets you pick from these profiles — no need to retype the same info every time.
        Includes <strong>bank accounts</strong>, <strong>logo</strong>, and <strong>signature</strong>
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff (manage-finance)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open the Financial Profiles menu</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/01-list',
        'alt' => 'Profile list split into Biller / Customer',
        'caption' => 'Financial Profiles List — two types: Biller + Customer',
        'callouts' => [
            '<strong>Biller profiles:</strong> our office (there may be several if you bill under different names)',
            '<strong>Customer profiles:</strong> regular customers billed frequently',
            '<strong>+ Create New:</strong> add a new profile',
            '<strong>Edit / Delete:</strong> management buttons',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → Finance → <strong>Financial Profiles</strong></li>
            <li>Select the Biller or Customer type</li>
            <li>View the list of existing profiles</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Create a Biller Profile (issuer)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/02-biller-builder',
        'alt' => 'Biller Builder page with all fields',
        'caption' => 'Biller Profile Builder — the invoice issuer\'s information',
        'callouts' => [
            '<strong>Company name + tax ID:</strong> the most important part',
            '<strong>Address:</strong> the registered address',
            '<strong>Logo:</strong> upload PNG/JPG (printed on the invoice)',
            '<strong>Signature:</strong> the authorized signer\'s signature + company stamp',
            '<strong>Bank accounts:</strong> you can add multiple (KBank, SCB, BBL, etc.)',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click "+ Create New" → select type = Biller</li>
            <li>Fill in the company info + tax ID + address</li>
            <li>Upload the logo + signature + stamp</li>
            <li>Add bank accounts (multiple accounts supported)</li>
            <li>Click "Save Profile"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Add Bank Accounts to a profile</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/03-bank-accounts',
        'alt' => 'Bank account list with brand badges',
        'caption' => 'Bank Accounts — add/edit/delete accounts on a profile',
        'callouts' => [
            '<strong>Bank:</strong> select from the dropdown (KBank/SCB/BBL/Krungsri/TTB, etc.)',
            '<strong>Account number:</strong> the account number',
            '<strong>Account name:</strong> the account holder\'s name',
            '<strong>Brand badge:</strong> the bank\'s logo shows automatically on receipts',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Open a Biller Profile → the "Bank Accounts" tab</li>
            <li>Click "+ Add Account"</li>
            <li>Select the bank + fill in the account number + name</li>
            <li>Click Save</li>
        </ol>
    </div>

    <div class="slide-tip">
        💡 <strong>Tip:</strong> when issuing an invoice, selecting "payment method = transfer" lets you pick a bank account from this profile, which prints automatically on the PDF
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">Customer Profiles (regular customers)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'financial_profiles/04-customer-profiles',
        'alt' => 'Customer Profiles list',
        'caption' => 'Customer Profiles — regular customers billed frequently',
        'callouts' => [
            '<strong>Customer name + tax ID:</strong> the info that will print on the invoice',
            '<strong>Address:</strong> the document delivery address',
            '<strong>Quick fill:</strong> when issuing an invoice → select a profile → all the info fills in instantly',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Click "+ Create New" → select type = Customer</li>
            <li>Fill in the customer's name + tax ID + address</li>
            <li>Click Save</li>
            <li>Next time you issue an invoice → select this profile → the data fills in automatically</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: Why don't I see a bank account when issuing an invoice?</dt>
        <dd>A: You must create a bank account on a Financial Profile (Biller Profile) first — then it will be available under Finance → Tax Invoice</dd>

        <dt>Q: Can I delete a profile that's used on old invoices?</dt>
        <dd>A: <strong>You shouldn't</strong> — old invoices will lose their reference; use archiving instead</dd>

        <dt>Q: Can I have multiple Biller profiles?</dt>
        <dd>A: Yes — e.g. if you bill under different company names (like "ABC Co., Ltd." and "ABC Service")</dd>

        <dt>Q: Where do I put the signature + stamp?</dt>
        <dd>A: In the Biller Profile → "Signature/Stamp" tab → upload as a PNG file (transparent background recommended)</dd>
    </dl>
</section>
