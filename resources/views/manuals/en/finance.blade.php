{{-- User Manual: Finance (English) --}}

<h4><i class="bi bi-cash-coin me-2"></i>What is this menu?</h4>
<p>
    The <strong>"Finance"</strong> menu is the office's central accounting/finance hub —
    including the Ledger, Tax Invoices, Withholding Tax (WHT) issuance,
    Tax Reports (PP.30, PND.3/53), Bank Reconciliation, and the Audit Log
</p>

<h4><i class="bi bi-person-check me-2"></i>Who can access this menu?</h4>
<ul>
    <li><span class="manual-role">Super Admin</span> <span class="manual-role">Admin</span> — full access</li>
    <li><span class="manual-role">Staff</span> — partial access (depends on the <code>manage-finance</code> permission)</li>
    <li><span class="manual-role">Caretaker</span> <span class="manual-role">Employer</span> — no access</li>
</ul>

<h4><i class="bi bi-layout-text-window me-2"></i>Sections of the Finance menu</h4>
<ol>
    <li><strong>Ledger</strong> — records all income/expenses, organized by date</li>
    <li><strong>Tax Invoices</strong> — create/view/print tax invoices + payment methods</li>
    <li><strong>WHT (Withholding Tax)</strong> — records 3%/5% WHT received + issues documents</li>
    <li><strong>Tax Reports</strong> — PP.30 + PND.3/53 — monthly summaries for tax filing</li>
    <li><strong>Bank Reconciliation</strong> — reconciles bank account balances with system records</li>
    <li><strong>Audit Log</strong> — view the full history of changes to financial data</li>
    <li><strong>Monthly Bundle</strong> — download a ZIP of all month-end closing documents</li>
</ol>

<h4><i class="bi bi-list-check me-2"></i>Common tasks</h4>

<h5>1. Record new income</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Go to Ledger → click "+ Record Entry"</li>
        <li>Select "Income"</li>
        <li>Fill in the date, customer, amount, VAT type</li>
        <li>Attach a slip image (if any)</li>
        <li>Click "Save"</li>
    </ol>
</div>

<h5>2. Create a tax invoice</h5>
<div class="manual-step">
    <ol class="mb-0">
        <li>Go to Tax Invoices → click "+ Create New"</li>
        <li>Select the <strong>issuer profile</strong> (our office)</li>
        <li>Fill in the customer name + tax ID + address</li>
        <li>Fill in the amount + VAT rate (normally 7%)</li>
        <li>Check the payment method (cash, transfer, PromptPay)</li>
        <li>If "transfer" is selected — choose a bank account from the profile</li>
        <li>Click "Save & Issue" — the system locks the invoice number + generates a PDF</li>
    </ol>
</div>

<h5>3. Generate a monthly tax report</h5>
<div class="manual-step">
    Go to Tax Reports → select a month → download PP.30 or PND.3/53
</div>

<h5>4. Reconcile the bank</h5>
<div class="manual-step">
    Go to Bank Reconciliation → upload the bank statement → the system matches it against system records automatically
</div>

<h5>5. Close out the month (Monthly Bundle)</h5>
<div class="manual-step">
    Go to Monthly Bundle → select a month → click "Generate" → download a ZIP with all of that month's documents
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>VAT 7%:</strong> is Thailand's default rate, rounded to 2 decimal places
</div>

<div class="manual-tip">
    <strong>WHT 3% vs 5%:</strong> 3% = general service fees, 5% = property/personal rental
</div>

<div class="manual-warn">
    <strong>An Issued tax invoice cannot be edited:</strong> by law it cannot be changed — it must be voided and a new one issued
</div>

<h4><i class="bi bi-question-circle me-2"></i>Frequently Asked Questions</h4>
<dl>
    <dt>Q: I don't see a bank account option?</dt>
    <dd>A: You must create a bank account in the financial profile first — go to Financial Profiles → select a profile → add an account</dd>

    <dt>Q: Are tax invoice numbers sequential?</dt>
    <dd>A: Yes — the system always continues numbering from the last invoice in the same tax year, with no gaps</dd>

    <dt>Q: Can I delete a tax invoice that was issued by mistake?</dt>
    <dd>A: You can <strong>void</strong> it, but not truly delete it — the invoice number stays in the system to preserve the sequence</dd>
</dl>
