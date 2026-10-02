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

<h5>6. Overview and stat cards (click to filter)</h5>
<div class="manual-step">
    The 4 cards: <strong>Income today / this month</strong> = money actually received on that day/month, counted by the date of each payment (a bill part-paid last month and settled this month is split correctly, never double-counted); <strong>Pending amount</strong> = outstanding on unpaid/partly paid bills; <strong>Overdue amount</strong> = outstanding on bills marked overdue. <strong>Click a card</strong> to list exactly the bills behind that number (click again to clear). Income cards show each payment under "Paid" — bold = in the period, faded = another period — and the filtered summary shows "Received in this period", equal to the card. Quotations are never counted.
</div>

<h5>7. Where a bill came from — source badge and filter</h5>
<div class="manual-step">
    Under the employer/project name a badge shows the source, e.g. <code>Workflow › MOU Renewal</code>, <code>Renewal Resolution › Tab X</code>, <code>Manual bill</code>. If the source tab has been deleted the badge is <strong>red</strong> ("tab deleted" + date) — the bill is still real, keep collecting or issue a credit note. The <strong>Source</strong> filter: all / from active tabs / from deleted tabs. A red banner appears when unpaid bills come from deleted tabs.
</div>

<h5>8. Tabs per work menu</h5>
<div class="manual-step">
    <strong>Workflow & Pre-Production</strong>, <strong>Registration</strong>, <strong>Renewal</strong> tabs show total / billed / outstanding per employer; <strong>Manage Finance</strong> opens pricing, billing and payments. <strong>Manual bills</strong> and <strong>Quotations</strong> are separate tabs — quotation history never mixes with real bills.
</div>

<h5>9. Receiving payments (Payment History)</h5>
<div class="manual-step">
    Open the bill → record the <strong>amount</strong>, <strong>date received</strong>, <strong>receiving account</strong> and slip. Several instalments are allowed; the status becomes "partial"/"paid" automatically (credit notes included). With no account selected the system asks for confirmation because the payment will <strong>not</strong> post to any bank balance. Payments with an account are posted to the Ledger on the date received.
</div>

<h5>10. Credit notes</h5>
<div class="manual-step">
    Reduce a bill that has been issued (discount, or writing off the rest): <strong>Credit Notes</strong> → <strong>New</strong> → pick the bill, amount and reason (saved as draft) → <strong>Issue</strong> locks the number and reduces the outstanding amount at once. Issued by mistake → <strong>Void</strong>; drafts can be deleted. Credit notes are included in the monthly bundle.
</div>

<h5>11. Create a manual bill</h5>
<div class="manual-step">
    Choose the <strong>document type</strong> first: <strong>Quotation</strong> (employer optional) or <strong>Invoice</strong> (employer required). You can add <strong>draft employees</strong> (name/nationality/passport/photo without creating a real employee — manual bills only). Quotations can show the <strong>grand total</strong> or unit prices only. Every document carries the configured VAT/WHT note.
</div>

<h5>12. Monthly report</h5>
<div class="manual-step">
    <strong>Monthly Report</strong> → month/biller → ZIP (CSV + slips + WHT documents). Income is <strong>one row per payment</strong> on the date received — a bill paid over several months appears in each month's report. A bill's WHT is shown once, on its final payment row. Quotations are excluded.
</div>

<h4><i class="bi bi-lightbulb me-2"></i>Tips</h4>

<div class="manual-tip">
    <strong>VAT 7%:</strong> is Thailand's default rate, rounded to 2 decimal places
</div>

<div class="manual-tip">
    <strong>WHT 3% vs 5%:</strong> 3% = general service fees, 5% = property/personal rental
</div>
<div class="manual-tip">
    <strong>Receipt for a bill that is not fully paid:</strong> the items and totals follow the bill, plus <strong>Paid</strong> and <strong>Balance Due</strong> lines — the large amount and the amount in words are the money actually received (fully paid bills look the same as before). For a receipt of one payment, issue it from that payment.
</div>
<div class="manual-tip">
    <strong>Date on receipts / tax invoices / advance receipts:</strong> set it yourself (back-dating allowed, not later than today) in the "Document date" field of the Receipt / Tax Invoice menus and the instalment selection window — for when the customer paid before the receipt is issued, so it matches the slip · per-payment receipts (each payment's Doc button) default to the payment date and can be changed · invoices and quotations always use today's date
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
