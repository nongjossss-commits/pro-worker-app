{{-- Training Edition: Finance (Add-on Module) (English) --}}

<div class="training-intro">
    <h3 class="training-intro-title">
        <i class="bi bi-cash-coin"></i> {{ __('Finance') }} — {{ __('The office\'s accounting + tax + audit log system') }}
    </h3>
    <p class="training-intro-desc">
        The <strong>"Finance"</strong> menu is an <strong>add-on</strong> module for offices that need a built-in accounting system.
        Includes the Ledger, Tax Invoices, WHT (Withholding Tax),
        PP.30 / PND.3/53, Bank Reconciliation, Monthly Bundle, and the Audit Log
    </p>
    <div class="training-role-row">
        <span class="role-pill role-admin">Super Admin</span>
        <span class="role-pill role-admin">Admin</span>
        <span class="role-pill role-admin">Staff (depends on the manage-finance permission)</span>
    </div>
</div>

<section class="training-slide">
    <div class="slide-number">STEP 1</div>
    <h2 class="slide-title">Open the Finance menu</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/01-main-dashboard',
        'alt' => 'Finance main page with summary cards + sub-menus',
        'caption' => 'Finance Dashboard — Summary cards + sub-menu links',
        'callouts' => [
            '<strong>Summary cards:</strong> this month\'s total, income, expenses, VAT, WHT',
            '<strong>Sub-menus:</strong> Ledger / Tax Invoices / WHT / Reports / Bank / Audit Log',
            '<strong>Monthly bundle:</strong> a 1-click button that generates a ZIP of month-end documents',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Sidebar → <strong>Finance</strong></li>
            <li>Check the summary cards to understand the overall status</li>
            <li>Select a sub-menu for the task you need</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 2</div>
    <h2 class="slide-title">Record a Ledger entry</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/02-ledger-entry',
        'alt' => 'Income/expense entry form',
        'caption' => 'Ledger Entry — Income / Expense with VAT + WHT',
        'callouts' => [
            '<strong>Type:</strong> Income / Expense',
            '<strong>Date:</strong> the entry date (defaults to today)',
            '<strong>Counterparty:</strong> the customer or vendor',
            '<strong>VAT:</strong> 7% (default) — Excl. or Incl. VAT',
            '<strong>WHT:</strong> 3% (services) / 5% (property rental)',
            '<strong>Slip image:</strong> you can attach a slip photo',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Finance → Ledger → "+ Record Entry"</li>
            <li>Select the type (Income/Expense)</li>
            <li>Fill in: date, counterparty, amount, VAT</li>
            <li>Attach a slip (optional) → click "Save"</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 3</div>
    <h2 class="slide-title">Create a Tax Invoice</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/03-tax-invoice',
        'alt' => 'Tax invoice creation form',
        'caption' => 'Tax Invoice Form — select a profile + fill in the customer + payment method',
        'callouts' => [
            '<strong>Issuer profile:</strong> our office (from Financial Profiles)',
            '<strong>Customer info:</strong> name + tax ID + address',
            '<strong>VAT 7%:</strong> Thailand\'s default, rounded to 2 decimal places',
            '<strong>Payment method:</strong> cash / transfer / PromptPay',
            '<strong>Bank account:</strong> if "transfer" is selected → choose a bank account',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Tax Invoices → "+ Create New"</li>
            <li>Select the <strong>Biller Profile</strong></li>
            <li>Fill in the customer info + amount + VAT</li>
            <li>Check the payment method</li>
            <li>Click "Save & Issue" → the system locks the number + generates a PDF</li>
        </ol>
    </div>

    <div class="slide-warn">
        ⚠️ <strong>Careful:</strong> an Issued invoice cannot be edited — it must be voided and a new one issued
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 4</div>
    <h2 class="slide-title">Monthly tax reports (PP.30 / PND.3/53)</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/04-tax-reports',
        'alt' => 'Tax Reports page — select month + download',
        'caption' => 'Tax Reports — monthly summaries for tax filing',
        'callouts' => [
            '<strong>Select a month:</strong> a month dropdown',
            '<strong>PP.30:</strong> VAT for that month (income - expense VAT)',
            '<strong>PND.3:</strong> WHT for individuals',
            '<strong>PND.53:</strong> WHT for juristic persons',
            '<strong>Export Excel:</strong> for use when filing taxes',
        ],
    ])

    <div class="slide-instructions">
        <ol>
            <li>Finance → Tax Reports</li>
            <li>Select the month you want</li>
            <li>Click download for each report</li>
            <li>Use the resulting file to file your taxes</li>
        </ol>
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">STEP 5</div>
    <h2 class="slide-title">Monthly Bundle + Bank Reconciliation + Audit Log</h2>

    @include('manuals.training._screenshot', [
        'src' => 'finance/05-monthly-bundle',
        'alt' => 'Monthly Bundle + Bank Reconciliation + Audit Log',
        'caption' => 'A complete set of month-end closing features',
        'callouts' => [
            '<strong>Monthly Bundle:</strong> a ZIP with the whole month\'s documents (income + expenses + invoices + WHT)',
            '<strong>Bank Reconciliation:</strong> upload a statement → the system matches it against your entries',
            '<strong>Audit Log:</strong> the history of every change to financial data — who changed what, and when',
        ],
    ])

    <div class="slide-tip">
        💡 <strong>Tip:</strong> at month-end → Generate Monthly Bundle → Bank Reconcile → check the Audit Log = a complete month-end close in one workflow
    </div>
</section>

<section class="training-slide">
    <div class="slide-number">FAQ</div>
    <h2 class="slide-title">Frequently Asked Questions</h2>

    <dl class="slide-faq">
        <dt>Q: Are tax invoice numbers sequential?</dt>
        <dd>A: Yes — the system continues numbering from the last invoice in the same tax year, with no gaps</dd>

        <dt>Q: Can I delete an invoice that was issued by mistake?</dt>
        <dd>A: You can <strong>void</strong> it, but not truly delete it — the invoice number stays in the system to preserve the sequence</dd>

        <dt>Q: What's the difference between WHT 3% and 5%?</dt>
        <dd>A: 3% = general services / 5% = property rental, individual labor fees</dd>

        <dt>Q: I can't see the Finance menu?</dt>
        <dd>A: You need the manage-finance role or higher + a subscription that includes the Finance Module</dd>
    </dl>
</section>
